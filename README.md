# Letter Management System (LMS)

A modern web-based platform for uploading, managing, and searching official letters within organizations. Built with Laravel 12, Bootstrap 5, and intelligent document processing with real-time notifications.

## ✨ Features

- **Dual Upload Modes** - Automatic mode for pre-numbered documents & Manual mode for custom numbering
- **Smart Document Processing** - Automatic text extraction from PDF, Word, and images (OCR)
- **Monthly Letter Numbering** - Auto-generated letter numbers that reset monthly with duplicate detection
- **Real-time Notifications** - Instant notifications for letter uploads with role-based redirects
- **Access Control** - Private/public letters with role-based permissions and advanced user search
- **Multi-Division Support** - Organize by departments with custom letter types (4 divisions)
- **Admin Dashboard** - Complete user, division, and document management with audit logging
- **Modern UI** - Responsive design with Bootstrap 5 and Font Awesome icons

## 🚀 Quick Start

```bash
# Clone and setup
git clone https://github.com/CakueKeju/LetterManagementSystem
cd LetterManagementSystem/LMS

# Install dependencies
composer install && npm install

# Configure environment
cp .env.example .env
# Edit .env with your database settings

# Setup application with sample data
php artisan key:generate
php artisan migrate --seed

# Start development
npm run dev & php artisan serve
```

## 📋 Requirements

- **PHP** ^8.2
- **Composer** & **Node.js**
- **MySQL** database
- **Tesseract OCR** (for image text extraction)
- **LibreOffice** (optional, for enhanced Word processing)

## 🏗️ Tech Stack

| Component | Technology |
|-----------|------------|
| **Backend** | Laravel 12.20.0, PHP 8.2+ |
| **Frontend** | Bootstrap 5, Font Awesome 6, Vanilla JS, Vite |
| **Database** | MySQL with Eloquent ORM |
| **Document Processing** | PDF Parser, PHPWord, Tesseract OCR |
| **Authentication** | Laravel UI with custom admin controls |
| **Notifications** | Real-time system with role-based routing |

## 📁 Key Structure

```
LMS/
├── app/Http/Controllers/    # Main application logic & notifications
├── app/Models/             # Database models with relationships
├── resources/views/        # Blade templates with notifications
├── routes/web.php         # Application routes
├── database/migrations/   # Database schema evolution
├── database/seeders/      # Sample data with 4 divisions
└── storage/app/letters/   # Uploaded documents
```

## 🔐 Authentication & Roles

- **Users**: Upload and manage letters within their division with notifications
- **Admins**: Full system access including user management and audit logs
- **Registration**: Admin-only (public registration disabled)
- **Sample Accounts**: 12 pre-configured users across 4 divisions (test data)

## 📄 Document Management

### Upload Modes

The system offers two intelligent upload modes to accommodate different document scenarios:

#### 🤖 **Automatic Mode**
- **Purpose**: For documents that already contain pre-written letter numbers
- **Process**: 
  1. Upload your document (PDF, DOC/DOCX, Images)
  2. System automatically extracts text using OCR/parsing
  3. Detects and validates existing letter numbers in the document
  4. Confirms the extracted number matches the expected format
  5. Stores the document with the detected number
- **Best For**: Official letters that already have letter numbers written in them
- **Validation**: Ensures the detected number follows the organization's format and isn't duplicated

#### ✍️ **Manual Mode**
- **Purpose**: For documents without letter numbers or when you need custom numbering
- **Process**:
  1. Fill in letter details (division, type, subject, date)
  2. System generates the next available letter number automatically
  3. Upload your document 
  4. System verifies the uploaded document matches the generated number
  5. Option to re-edit and re-upload if verification fails
- **Best For**: Draft documents, internal memos, or when you need specific numbering control
- **Features**: Real-time preview of generated number, smart collision detection

### Document Features

- **Supported Formats**: PDF, DOC/DOCX, Images (JPG, PNG)
- **Monthly Auto-numbering**: Format: `{number}/{division}/{type}/INTENS/{month}/{year}` (resets monthly)
- **Privacy Controls**: Public (division-wide) or Private (selected users with advanced search)
- **Duplicate Detection**: Prevents conflicting letter numbers within the same month
- **Smart Notifications**: Real-time alerts with role-based navigation
- **Re-upload System**: Failed verifications can be corrected through the re-edit interface

## 🛡️ Admin Features

- **Dashboard**: System overview with statistics and recent activity
- **User Management**: Create, edit, manage user accounts across 4 divisions
- **Division Management**: Organize departments and access controls
- **Document Types**: Configure letter categories and numbering
- **Notification System**: Monitor and manage system notifications
- **Audit Logging**: Track uploads, access, and user activity

## 🚦 Development

```bash
# Development mode
npm run dev
php artisan serve

# Production build
npm run build

# Run tests
php artisan test
```

## 📝 Configuration

Key environment variables in `.env`:
- `DB_*` - Database connection
- `APP_URL` - Application URL
- `MAIL_*` - Email settings (optional)

## 📖 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/surat` | List user letters with filtering |
| `GET` | `/surat/mode-selection` | Choose between automatic/manual upload modes |
| `POST` | `/surat/automatic/upload` | Upload document for automatic number detection |
| `POST` | `/surat/automatic/confirm` | Confirm and process automatically detected number |
| `GET` | `/surat/manual/form` | Manual upload form with number generation |
| `POST` | `/surat/manual/upload` | Upload document with manually generated number |
| `GET` | `/surat/manual/re-edit` | Re-edit form for failed verification |
| `GET` | `/notifications` | User notification dropdown |
| `POST` | `/notifications/{id}/view` | Mark notification as read |
| `GET` | `/admin` | Admin dashboard with statistics |
| `GET` | `/admin/users` | Manage users across divisions |

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
