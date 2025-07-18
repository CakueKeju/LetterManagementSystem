# Letter Management System (LMS)

A web-based platform for uploading, managing, and searching official letters within your organization. Built with Laravel 12, Bootstrap 5, and modern PHP/JS tooling.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Main Packages & Dependencies](#main-packages--dependencies)
- [Build & Development](#build--development)
- [Authentication & User Management](#authentication--user-management)
- [Letter Management](#letter-management)
- [Admin Features](#admin-features)
- [Routes Overview](#routes-overview)
- [Controllers Overview](#controllers-overview)
- [License](#license)

---

## Features

- User authentication (login only; registration by admin only)
- Upload letters (PDF, Word, Image)
- Automatic text extraction (PDF, Word, OCR for images)
- Duplicate detection for letter numbers
- Private/public letter access
- Division and letter type management
- Admin dashboard for user, division, and letter type management

---

## Requirements

- PHP ^8.2
- Composer
- Node.js & npm
- MySQL or compatible database
- [Tesseract OCR](https://github.com/tesseract-ocr/tesseract) (for image text extraction)
- [LibreOffice](https://www.libreoffice.org/) (recommended for DOC/DOCX parsing, if needed)

---

## Installation

```sh
git clone <your-repo-url>
cd LetterManagementSystem/LMS

composer install
npm install

cp .env.example .env
# Edit .env for your database and mail settings

php artisan key:generate
php artisan migrate
# (Optional) Seed database:
php artisan db:seed

npm run dev
php artisan serve
```

---

## Configuration

- **.env**: Set your database, mail, and other environment variables.
- **Tesseract OCR**: Must be installed and available in your system path for image text extraction.
- **Storage**: Uploaded files are stored in `storage/app/letters`.

---

## Project Structure

- `app/Http/Controllers/` - Main controllers (SuratController, AdminController, Auth)
- `app/Models/` - Eloquent models (Surat, Division, JenisSurat, User, etc.)
- `resources/views/` - Blade templates (admin, surat, auth, layouts)
- `routes/web.php` - Main web routes
- `database/migrations/` - Database schema
- `public/` - Public assets and entry point
- `config/` - Laravel configuration files

---

## Main Packages & Dependencies

**PHP (composer.json):**
- `laravel/framework` ^12.0
- `laravel/ui` ^4.6 (UI scaffolding)
- `maatwebsite/excel` ^1.1 (Excel import/export)
- `phpoffice/phpword` ^1.3 (Word file parsing)
- `smalot/pdfparser` ^2.12 (PDF parsing)
- `thiagoalessio/tesseract_ocr` ^2.13 (OCR for images)
- `intervention/image` ^3.11 (Image handling)

**JS (package.json):**
- `bootstrap` ^5.2.3
- `axios` ^1.8.2
- `tailwindcss` ^4.0.0
- `vite` ^6.2.4
- `laravel-vite-plugin` ^1.2.0
- `@popperjs/core`, `sass`, `concurrently`

**Build (vite.config.js):**
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

---

## Build & Development

- **Development:** `npm run dev`
- **Production build:** `npm run build`
- **Run Laravel server:** `php artisan serve`
- **Run all (concurrently):** `composer run dev`

---

## Authentication & User Management

- **Login:** Only existing users can log in.
- **Registration:** Disabled for public. Only admins can add users via the admin dashboard.
- **Roles:** Users can be regular or admin (`is_admin` flag).
- **Division:** Each user belongs to a division.

---

## Letter Management

- **Upload:** Users can upload PDF, Word, or image files.
- **Text Extraction:** Automatic extraction using PDF parser, Word parser, or Tesseract OCR for images.
- **Duplicate Check:** System checks for duplicate `nomor_urut` (letter number) within the same division.
- **Private/Public:** Letters can be marked as private (access only to selected users) or public (all users in the division).
- **Letter Code:** Generated as `{nomor_urut}/{kode_divisi}/{kode_jenis}/INTENS/{year}`.

---

## Admin Features

- **Dashboard:** Overview of stats, recent letters, and users.
- **User Management:** Add, edit, delete users. Only admins can register new users.
- **Division Management:** Add, edit, delete divisions.
- **Jenis Surat Management:** Add, edit, delete letter types.
- **Letter Management:** Edit, delete any letter.

---

## Routes Overview

**Main User Routes:**
- `/` - Welcome page
- `/login` - Login page
- `/surat` - List letters
- `/surat/upload` - Upload letter
- `/surat/confirm` - Confirm extracted data
- `/surat/store` - Store letter
- `/surat/users-for-access` - AJAX: get users for private access

**Admin Routes (prefix `/admin`):**
- `/admin` - Dashboard
- `/admin/surat` - Manage all letters
- `/admin/users` - Manage users
- `/admin/divisions` - Manage divisions
- `/admin/jenis-surat` - Manage letter types

---

## Controllers Overview

### SuratController

- `showUploadForm()` - Show upload form
- `handleUpload(Request $request)` - Handle file upload, extract text, check duplicates
- `showConfirmForm(Request $request)` - Show confirmation form for extracted data
- `store(Request $request)` - Store new letter
- `getUsersForAccess(Request $request)` - AJAX: get users for private access
- `index(Request $request)` - List/filter letters

### AdminController

- `dashboard()` - Show admin dashboard
- `suratIndex()`, `suratEdit()`, `suratUpdate()`, `suratDestroy()` - Manage letters
- `usersIndex()`, `usersCreate()`, `usersStore()`, `usersEdit()`, `usersUpdate()`, `usersDestroy()` - Manage users
- `divisionsIndex()`, `divisionsCreate()`, `divisionsStore()`, `divisionsEdit()`, `divisionsUpdate()`, `divisionsDestroy()` - Manage divisions
- `jenisSuratIndex()`, `jenisSuratCreate()`, `jenisSuratStore()`, `jenisSuratEdit()`, `jenisSuratUpdate()`, `jenisSuratDestroy()` - Manage letter types

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**For more details, see the code and comments in each file. If you need further breakdown of any part, let me know!**
