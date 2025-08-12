# Letter Management System (LMS)

A modern web-based platform for uploading, managing, and searching official letters within organizations. Built with Laravel 12, Bootstrap 5, and intelligent document processing.

## ✨ Features

- **Smart Document Processing** - Automatic text extraction from PDF, Word, and images (OCR)
- **Intelligent Numbering** - Auto-generated letter numbers with duplicate detection
- **Access Control** - Private/public letters with role-based permissions
- **Multi-Division Support** - Organize by departments with custom letter types
- **Admin Dashboard** - Complete user, division, and document management
- **Modern UI** - Responsive design with Bootstrap 5

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

# Setup application
php artisan key:generate
php artisan migrate
php artisan db:seed

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
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Bootstrap 5, Vanilla JS, Vite |
| **Database** | MySQL with Eloquent ORM |
| **Document Processing** | PDF Parser, PHPWord, Tesseract OCR |
| **Authentication** | Laravel UI with custom admin controls |

## 📁 Key Structure

```
LMS/
├── app/Http/Controllers/    # Main application logic
├── app/Models/             # Database models
├── resources/views/        # Blade templates
├── routes/web.php         # Application routes
├── database/migrations/   # Database schema
└── storage/app/letters/   # Uploaded documents
```

## 🔐 Authentication & Roles

- **Users**: Upload and manage letters within their division
- **Admins**: Full system access including user management
- **Registration**: Admin-only (public registration disabled)

## 📄 Document Management

- **Supported Formats**: PDF, DOC/DOCX, Images (JPG, PNG)
- **Auto-numbering**: Format: `{number}/{division}/{type}/INTENS/{year}`
- **Privacy Controls**: Public (division-wide) or Private (selected users)
- **Duplicate Detection**: Prevents conflicting letter numbers

## 🛡️ Admin Features

- **Dashboard**: System overview and statistics
- **User Management**: Create, edit, manage user accounts
- **Division Management**: Organize departments and access
- **Document Types**: Configure letter categories
- **System Monitoring**: Track uploads and user activity

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
| `GET` | `/surat` | List user letters |
| `POST` | `/surat/upload` | Upload new letter |
| `GET` | `/admin` | Admin dashboard |
| `GET` | `/admin/users` | Manage users |

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
