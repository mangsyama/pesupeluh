# TECHNICAL CONTEXT & CODING RULES

## 1. Environment & Tech Stack Specifications
Aplikasi dibangun dengan tumpukan teknologi modern berikut:
- **Backend Framework**: Laravel 13 (PHP ^8.3)
- **Database Engine**: Microsoft SQL Server 2022 Express Edition (T-SQL)
- **Database Driver**: SQLSRV / PDO_SQLSRV
- **Frontend Framework**: Vue 3 (^3.4) + Inertia.js 2.0 (Inertia-Laravel ^2.0)
- **Build Tool & Assets**: Vite 8 (^8.0) + PostCSS
- **Styling Engine**: Vanilla CSS & Tailwind CSS (^3.2)
- **Libraries & Plugins**:
  - `sweetalert2` (^11.26) - Untuk popup konfirmasi dan notifikasi interaktif.
  - `@lucide/vue` (^1.18) - Library ikon grafis Lucide.
  - `@vladmandic/face-api` (^1.7) - Library lokal untuk Face Login/Recognition (biometrik wajah).
  - `vite-plugin-pwa` (^1.3) - Plugin PWA (Progressive Web App) untuk kemampuan offline client.
  - `barryvdh/laravel-dompdf` (^3.1) - Untuk cetak laporan PDF server-side.
  - `maatwebsite/excel` (^3.1) - Untuk ekspor laporan tabular Excel/CSV.
- **Architecture Style**: Monolith Hybrid (Inertia.js + Server-side rendering untuk web client, dan API-ready untuk mobile client).

---

## 2. Universal Coding Standards
- **Language & Development**: Semua penamaan tabel, kolom, variabel, method, dan file wajib menggunakan **Bahasa Inggris standar profesional** dalam penulisan kodenya.
- **Internationalization (i18n) / Multi-Language Standard**:
  - Semua string teks yang dirender pada antarmuka pengguna (UI) **wajib menggunakan helper lokalisasi** `__(...)` atau `proxy.__('...')` baik di sisi Vue maupun Laravel, agar mendukung multi-bahasa secara dinamis (Bahasa Indonesia & Inggris).
  - **Dilarang keras menulis hardcoded string** teks bahasa di dalam file komponen Vue atau file Controller PHP.
  - Setiap kali menambahkan fitur baru atau mengubah antarmuka, developer **wajib menyelaraskan** file kamus bahasa pada:
    - [id.json](file:///c:/project/pesupeluh/lang/id.json) (`lang/id.json`) untuk terjemahan Bahasa Indonesia.
    - [en.json](file:///c:/project/pesupeluh/lang/en.json) (`lang/en.json`) untuk terjemahan Bahasa Inggris.
- **Naming Convention**:
  - Database: `lowercase_with_underscores` (snake_case) untuk nama tabel dan kolom. Nama tabel wajib berbentuk jamak (*plural*), contoh: `users`, `service_tickets`.
  - PHP/Laravel: Mengikuti standar PSR-12. Menggunakan `camelCase` untuk variabel/method, dan `StudlyCaps` untuk nama Class/Controller/Model/Seeder/Factory.

---

## 3. Database & Security Architecture Rules
1. **Security - IDOR Prevention (Hybrid ID Pattern)**
   - Secara internal (untuk relasi `JOIN`), gunakan `id (INT IDENTITY/Auto-increment)` sebagai *Primary Key* demi kecepatan performa indexing database.
   - Secara eksternal (untuk Route URL, Parameter API, Request Body), **wajib** menggunakan `uuid (UNIQUEIDENTIFIER)` agar ID tidak dapat diprediksi/ditebak oleh pihak luar.

2. **Timezone Accuracy Rule**
   - Untuk seluruh pencatatan log waktu (`created_at`, `updated_at`, `validated_at`, `responded_at`, `resolved_at`, `deleted_at`, dll), tipe data wajib menggunakan **`DATETIMEOFFSET`** pada SQL Server agar informasi jam terkunci akurat beserta offset zona waktu lokal (WITA +08:00), bukan sekadar string jam biasa.

3. **Database Timestamps & Soft Delete Policy**
   - **Tabel Referensi Statis** (`roles`, `divisions`, `supporting_units`, `unit_features`): Data diisi via seeder/migration oleh developer dan tidak dimodifikasi oleh user biasa. Tabel-tabel ini **tidak memerlukan** kolom `deleted_at` maupun timestamps (`created_at`/`updated_at`).
   - **Tabel Master Dinamis** (`rooms`, `feature_categories`): Dikelola oleh admin via aplikasi. Tabel ini wajib memiliki `created_at`, `updated_at`, dan `deleted_at` untuk soft delete demi menjaga integritas data relasional masa lalu.
   - **Tabel Transaksi Utama** (`users`, `service_tickets`): Wajib memiliki timestamps lengkap (`created_at`, `updated_at`, `deleted_at`).
   - **Tabel Pivot/Log** (`ticket_assignments`, `ticket_attachments`): Hanya memerlukan satu timestamp pembuatan (`assigned_at`/`uploaded_at`).

---

## 4. Master Database Definition (T-SQL Script)
*AI Agent coding dapat menggunakan skrip di bawah ini sebagai acuan skema migrasi SQL Server:*

```sql
-- =========================================================================
-- UPDATE VERSION: ADDED NIP, USER PHOTO, DESCRIPTIONS, & OPERATIONAL STATUS
-- =========================================================================

-- 1. Tabel Master: Divisions (Statis) - Diperbarui dengan Deskripsi
CREATE TABLE divisions (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description NVARCHAR(MAX) NULL -- Tambahan Deskripsi Divisi
);

-- 2. Tabel Master: Supporting Units (Statis) - Diperbarui dengan Deskripsi & Status
CREATE TABLE supporting_units (
    id INT IDENTITY(1,1) PRIMARY KEY,
    division_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description NVARCHAR(MAX) NULL, -- Tambahan Deskripsi Unit
    status VARCHAR(30) DEFAULT 'IN_DEVELOPMENT' NOT NULL, -- Status Kontrol Menu: ACTIVE, IN_DEVELOPMENT, INACTIVE
    CONSTRAINT FK_supporting_units_divisions FOREIGN KEY (division_id) 
        REFERENCES divisions(id) ON DELETE NO ACTION,
    CONSTRAINT CHK_supporting_unit_status CHECK (status IN ('ACTIVE', 'IN_DEVELOPMENT', 'INACTIVE')) -- Pengunci Status Modul
);

-- 3. Tabel Master: Unit Features (Statis)
CREATE TABLE unit_features (
    id INT IDENTITY(1,1) PRIMARY KEY,
    supporting_unit_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    CONSTRAINT FK_unit_features_supporting_units FOREIGN KEY (supporting_unit_id) 
        REFERENCES supporting_units(id) ON DELETE NO ACTION
);

-- 4. Tabel Master: Feature Categories (Dinamis)
CREATE TABLE feature_categories (
    id INT IDENTITY(1,1) PRIMARY KEY,
    feature_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description NVARCHAR(MAX) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,
    CONSTRAINT FK_feature_categories_unit_features FOREIGN KEY (feature_id) 
        REFERENCES unit_features(id) ON DELETE NO ACTION
);

-- 5. Tabel Master: Rooms (Dinamis)
CREATE TABLE rooms (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location_floor VARCHAR(50) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL
);

-- 6. Tabel Master: Roles (Statis)
CREATE TABLE roles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL -- ADMINISTRATOR, MANAGEMENT, UNIT_HEAD, TECHNICIAN, ROOM_HEAD, REPORTER
);

-- 7. Tabel Inti: Users - Diperbarui dengan NIP dan Foto Profil Pelamar/Pegawai
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    role_id INT NOT NULL,
    room_id INT NULL,
    supporting_unit_id INT NULL,
    
    -- Identitas Kepegawaian Rumah Sakit
    nip VARCHAR(50) NOT NULL, -- Tambahan NIP Pegawai (Unique Key via filtered index)
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL, -- Unique Key via filtered index
    email_verified_at DATETIMEOFFSET NULL,
    password VARCHAR(255) NOT NULL,
    
    -- Autentikasi Modern & Validasi Berkas Admin
    face_descriptor NVARCHAR(MAX) NULL, -- Data koordinat biometrik wajah (128-float array)
    profile_photo_path VARCHAR(255) NULL, -- Tambahan path/URL pasfoto fisik untuk validasi manual oleh Admin
    
    remember_token VARCHAR(100) NULL,
    phone_number VARCHAR(20) NULL,
    is_active BIT DEFAULT 0 NOT NULL, -- Default 0 (Mati) sebelum divalidasi dan diaktifkan oleh Admin
    approved_by INT NULL, -- FK ke users.id (Administrator)
    approved_at DATETIMEOFFSET NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,
    
    CONSTRAINT FK_users_roles FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE NO ACTION,
    CONSTRAINT FK_users_rooms FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT FK_users_supporting_units FOREIGN KEY (supporting_unit_id) REFERENCES supporting_units(id) ON DELETE SET NULL,
    CONSTRAINT FK_users_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE NO ACTION
);

-- 8. Tabel Transaksi Utama: Service Tickets
CREATE TABLE service_tickets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    reporter_id INT NOT NULL,
    room_id INT NOT NULL,
    category_id INT NOT NULL,
    problem_description NVARCHAR(MAX) NOT NULL,
    priority VARCHAR(20) NULL,
    validated_by INT NULL,
    validated_at DATETIMEOFFSET NULL,
    status VARCHAR(30) DEFAULT 'PENDING_VALIDATION' NOT NULL,
    responded_at DATETIMEOFFSET NULL,
    resolved_at DATETIMEOFFSET NULL,
    pending_reason NVARCHAR(MAX) NULL,
    paused_duration_seconds INT DEFAULT 0 NOT NULL, -- Durasi tiket ditangguhkan (detik)
    last_paused_at DATETIMEOFFSET NULL, -- Waktu terakhir tiket ditangguhkan
    completion_notes NVARCHAR(MAX) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,
    CONSTRAINT CHK_ticket_priority CHECK (priority IN ('URGENT', 'ROUTINE')),
    CONSTRAINT CHK_ticket_status CHECK (status IN ('PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'COMPLETED', 'CANCEL')),
    CONSTRAINT FK_service_tickets_users_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_service_tickets_users_validator FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_service_tickets_rooms FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE NO ACTION,
    CONSTRAINT FK_service_tickets_feature_categories FOREIGN KEY (category_id) REFERENCES feature_categories(id) ON DELETE NO ACTION
);

-- 9. Tabel Transaksi: Ticket Assignments
CREATE TABLE ticket_assignments (
    id INT IDENTITY(1,1) PRIMARY KEY,
    ticket_id INT NOT NULL,
    technician_id INT NOT NULL,
    assigned_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    CONSTRAINT FK_ticket_assignments_service_tickets FOREIGN KEY (ticket_id) REFERENCES service_tickets(id) ON DELETE CASCADE,
    CONSTRAINT FK_ticket_assignments_users FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE NO ACTION
);

-- 10. Tabel Transaksi: Ticket Attachments
CREATE TABLE ticket_attachments (
    id INT IDENTITY(1,1) PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    CONSTRAINT FK_ticket_attachments_service_tickets FOREIGN KEY (ticket_id) REFERENCES service_tickets(id) ON DELETE CASCADE,
    CONSTRAINT FK_ticket_attachments_users FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE NO ACTION
);

-- Indexing Tambahan untuk Pencarian NIP Cepat
CREATE INDEX idx_tickets_status_uuid ON service_tickets(status, uuid);
CREATE INDEX idx_tickets_created_at ON service_tickets(created_at);
CREATE INDEX idx_users_email_uuid ON users(email, uuid);
CREATE INDEX idx_users_nip ON users(nip); -- Index baru untuk optimasi pencarian NIP saat login/validasi
CREATE UNIQUE INDEX uq_users_nip ON users(nip) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX uq_users_email ON users(email) WHERE deleted_at IS NULL;
```

---

## 5. Image & File Upload Compression Rules
1. **Client-Side Image Compression**
   - Untuk menghemat kapasitas penyimpanan server dan bandwidth pengguna, semua pengunggahan foto/gambar (termasuk unggah berkas manual maupun jepretan kamera pada registrasi/profil) **wajib dikompresi di sisi client (frontend)** sebelum dikirim ke server.
   
2. **Lokasi & Konfigurasi Kode**
   - Seluruh logika kompresi dipusatkan di berkas utilitas: [imageCompressor.js](file:///c:/project/pesupeluh/resources/js/Utils/imageCompressor.js) (`resources/js/Utils/imageCompressor.js`).
   - Modul lain wajib mengimpor dan menggunakan fungsi ini: `import { compressImage } from '@/Utils/imageCompressor';`.
   
3. **Spesifikasi Default Kompresi**
   - Kualitas kompresi: **0.8 (80%)** dengan format `image/jpeg`.
   - Batas resolusi maksimum: Lebar atau tinggi maksimal **1200px** dengan mempertahankan rasio aspek asli (aspect ratio).
   - Format data kirim: Base64 Data URL (`data:image/jpeg;base64,...`) agar kompatibel dengan sistem penyimpanan database/file server.