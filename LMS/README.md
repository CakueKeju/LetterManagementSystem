# Letter Management System (LMS)

Sistema pengelolaan surat berbasis Laravel dengan fitur counter otomatis per bulan dan pengolahan dokumen terintegrasi.

## Features

### 📄 Letter Management
- Upload surat dalam format PDF, DOC, DOCX
- Auto-convert Word documents ke PDF menggunakan LibreOffice
- Ekstraksi nomor surat otomatis dari dokumen
- Fill PDF dengan nomor surat yang baru
- Preview surat sebelum finalisasi

### 🔢 Smart Counter System
- **Multi-Month Support**: Counter independen untuk setiap bulan
- **Auto-Reset**: Tidak perlu reset manual, setiap bulan otomatis mulai dari 1
- **Historical Input**: Bisa input surat untuk bulan sebelumnya tanpa mengganggu counter bulan berjalan
- **Format**: `001/DIVISI/JENIS/INTENS/MM/YYYY`

### 🔒 Lock System
- Temporary lock nomor urut untuk mencegah duplicate
- Auto-cleanup expired locks
- Lock berlaku 10 menit selama proses input

### 👥 Access Control
- Surat public: Visible untuk seluruh divisi
- Surat private: Hanya untuk user yang dipilih
- Role-based access (Admin/User)

### 🔍 Advanced Search & Filter
- Filter berdasarkan divisi, jenis surat, tanggal
- Search dalam content surat
- Export data ke berbagai format

## Installation

### Requirements
- PHP 8.1+
- Laravel 11
- SQLite/MySQL/PostgreSQL
- LibreOffice (untuk konversi Word ke PDF)
- Composer

### Setup

```bash
# Clone repository
git clone https://github.com/CakueKeju/LetterManagementSystem.git
cd LetterManagementSystem/LMS

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Create storage links
php artisan storage:link

# Install LibreOffice (Windows)
# Download dan install dari https://www.libreoffice.org/download/download/
```

## Database Structure

### Core Tables

#### `counters`
Menyimpan counter per jenis surat per bulan:
```sql
id | jenis_surat_id | month_year | counter
1  | 1              | 2025-08    | 3
2  | 1              | 2025-07    | 5
3  | 2              | 2025-08    | 1
```

#### `surat`
Data surat dengan nomor urut dan file:
```sql
id | nomor_urut | nomor_surat | jenis_surat_id | divisi_id | file_path | tanggal_surat
```

#### `nomor_urut_locks`
Temporary locks untuk mencegah duplicate:
```sql
id | divisi_id | jenis_surat_id | nomor_urut | user_id | locked_until
```

## Counter System Logic

### Multi-Month Counter
Sistem counter baru mendukung input surat untuk berbagai bulan:

```php
// Input surat bulan ini
$jenisSurat->peekNextCounter(); // Auto menggunakan 2025-08

// Input surat bulan lalu  
$jenisSurat->peekNextCounter('2025-07'); // Counter Juli terpisah

// Input surat bulan depan
$jenisSurat->peekNextCounter('2025-09'); // Counter September baru
```

### Automatic Flow
1. **Upload**: User upload file surat
2. **Extract**: Sistem ekstrak tanggal surat dari file
3. **Counter**: Sistem ambil counter berdasarkan bulan dari tanggal surat
4. **Preview**: User lihat preview dengan nomor yang akan digunakan
5. **Finalize**: Sistem increment counter dan simpan surat

## Console Commands

### Maintenance
```bash
# System maintenance (scheduled hourly)
php artisan lms:maintenance

# Force cleanup
php artisan lms:cleanup --force

# Check counter status
php artisan surat:counter-status
```

### Scheduling
Di `routes/console.php`:
```php
// Maintenance setiap jam
Schedule::command('lms:maintenance')->hourly();

// Deep cleanup harian jam 2 pagi
Schedule::command('lms:cleanup --force')->dailyAt('02:00');
```

## Usage Examples

### Counter untuk Bulan Berbeda
```php
$jenisSurat = JenisSurat::find(1);

// Lihat counter bulan ini
$current = $jenisSurat->getCurrentCounter(); // 2025-08

// Lihat counter bulan lalu  
$july = $jenisSurat->getCurrentCounter('2025-07');

// Preview nomor berikutnya
$next = $jenisSurat->peekNextCounter('2025-08');

// Increment counter (saat finalize)
$final = $jenisSurat->incrementCounter('2025-08');
```

### Input Surat Historical
```php
// Controller akan auto-detect bulan dari tanggal_surat
$tanggalSurat = '2025-07-15'; // Juli 2025
$nextNumber = $this->getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat);
// Akan menggunakan counter Juli, tidak mengganggu counter Agustus
```

## Migration History

### Recent Updates (August 2025)
- ✅ Created `counters` table (simplified from `jenis_surat_counters`)
- ✅ Migrated existing counter data from old system  
- ✅ Removed old `counter` and `last_reset_month` columns from `jenis_surat`
- ✅ Updated all controllers and models to use new counter system
- ✅ Deprecated manual reset commands (auto-reset per month)
- ✅ Clean architecture with separation of concerns

## Benefits of New System

### ✅ Solved Problems
- **Multiple Month Support**: Bisa input surat bulan sebelumnya
- **Data Integrity**: Counter bulan lalu tidak berubah
- **Scalability**: Unlimited historical input
- **Performance**: Efficient queries dengan proper indexing
- **Maintenance**: No manual reset needed

### 🚀 Performance
- Index pada `(jenis_surat_id, month_year)` untuk query cepat
- Minimal database locks dengan transaction scope
- Auto-cleanup expired locks untuk mencegah bloat

## Troubleshooting

### LibreOffice Issues
```bash
# Test LibreOffice conversion
soffice --headless --convert-to pdf --outdir /tmp test.docx
```

### Counter Issues
```bash
# Check counter status
php artisan surat:counter-status

# Check database directly
php artisan db:table counters
```

### Lock Issues
```bash
# Clean expired locks manually
php artisan lms:cleanup --force
```

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
