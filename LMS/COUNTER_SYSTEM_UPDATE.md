# Update Counter System - Multiple Month Support

## Problem
Sistem counter sebelumnya hanya mendukung satu counter per jenis surat yang di-reset setiap bulan. Ini tidak memungkinkan input surat untuk bulan sebelumnya karena counter sudah di-reset untuk bulan berjalan.

## Solution
Mengubah struktur database dan logic untuk mendukung counter per jenis surat per bulan dengan menggunakan tabel terpisah.

## Database Changes

### 1. New Table: `jenis_surat_counters`
```sql
CREATE TABLE jenis_surat_counters (
    id BIGINT PRIMARY KEY,
    jenis_surat_id BIGINT FOREIGN KEY,
    month_year VARCHAR(7), -- Format: YYYY-MM
    counter INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(jenis_surat_id, month_year)
);
```

### 2. Model Changes
- **JenisSurat.php**: Updated methods to support month parameter
- **JenisSuratCounter.php**: New model untuk mengelola counter per bulan

## Code Changes

### JenisSurat Model
```php
// Old method - single counter
public function peekNextCounter(): int

// New method - supports specific month
public function peekNextCounter($monthYear = null): int

// Usage examples:
$jenisSurat->peekNextCounter(); // Current month
$jenisSurat->peekNextCounter('2024-01'); // January 2024
```

### SuratController Updates
```php
// Old method
public function getNextNomorUrut($divisiId, $jenisSuratId)

// New method - supports tanggal_surat parameter
public function getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat = null)

// Old method
public function incrementNomorUrut($jenisSuratId)

// New method - supports tanggal_surat parameter
public function incrementNomorUrut($jenisSuratId, $tanggalSurat = null)
```

## Usage Examples

### Case 1: Normal Input (Current Month)
```php
// Input surat untuk bulan saat ini
$jenisSurat = JenisSurat::find(1);
$nextNumber = $jenisSurat->peekNextCounter(); // Uses current month
$jenisSurat->incrementCounter(); // Increments current month counter
```

### Case 2: Historical Input (Previous Month)
```php
// Input surat untuk Januari 2024
$tanggalSurat = '2024-01-15';
$jenisSurat = JenisSurat::find(1);

// Preview counter untuk Januari 2024
$nextNumber = $jenisSurat->peekNextCounter('2024-01');

// Increment counter untuk Januari 2024
$jenisSurat->incrementCounter('2024-01');
```

### Case 3: Auto-detection from tanggal_surat
```php
// In SuratController
$tanggalSurat = $request->tanggal_surat; // '2024-01-15'
$nextNumber = $this->getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat);

// This will automatically use 2024-01 as the month-year
```

## Benefits

1. **Backward Compatibility**: Input surat untuk bulan sebelumnya tanpa mengganggu counter bulan berjalan
2. **Scalability**: Setiap bulan memiliki counter terpisah dan independen
3. **Data Integrity**: Counter untuk bulan yang sudah lewat tidak akan berubah
4. **Flexibility**: Bisa input surat untuk bulan manapun
5. **Performance**: Query lebih efisien dengan index pada jenis_surat_id + month_year

## Database Structure Comparison

### Before (Single Counter) - REMOVED
```
jenis_surat:
id | divisi_id | kode_jenis | counter | last_reset_month
1  | 1         | SP         | 5       | 2024-08
```

❌ **Problem**: Jika bulan berganti ke September, counter di-reset ke 0. Input surat Agustus tidak bisa dilakukan.
✅ **Solution**: Kolom `counter` dan `last_reset_month` sudah dihapus dari tabel `jenis_surat`.

### After (Multiple Counters) - CURRENT SYSTEM
```
jenis_surat:
id | divisi_id | kode_jenis | nama_jenis | is_active
1  | 1         | SP         | Surat Penting | 1

jenis_surat_counters:
id | jenis_surat_id | month_year | counter
1  | 1             | 2024-07    | 3
2  | 1             | 2024-08    | 5
3  | 1             | 2024-09    | 2
```

✅ **Benefit**: Bisa input surat untuk Agustus (counter 6), Juli (counter 4), atau September (counter 3) tanpa masalah.

## Migration Status
- ✅ Created `jenis_surat_counters` table
- ✅ Migrated existing counter data from old system
- ✅ Updated all related methods and controllers
- ✅ Removed old `counter` and `last_reset_month` columns from `jenis_surat` table
- ✅ Cleaned up deprecated methods in JenisSurat model
- ✅ Backward compatibility maintained

## Testing
Test dengan mencoba input surat untuk:
1. Bulan saat ini - seharusnya menggunakan counter bulan berjalan
2. Bulan sebelumnya - seharusnya membuat/menggunakan counter untuk bulan tersebut
3. Bulan mendatang - seharusnya membuat counter baru untuk bulan tersebut

## Notes
- Parameter `tanggalSurat` di method-method akan otomatis di-parse untuk mendapatkan `YYYY-MM`
- Jika `tanggalSurat` null, akan menggunakan bulan saat ini
- Counter tetap akan increment berdasarkan bulan dari `tanggal_surat`, bukan tanggal sistem
