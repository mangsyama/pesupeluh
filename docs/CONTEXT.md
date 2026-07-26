# TECHNICAL CONTEXT & CODING RULES — PESU PELUH

## 1. Environment & Tech Stack Specifications
Aplikasi dibangun dengan tumpukan teknologi modern berikut:
- **Backend Framework**: Laravel 11 (`^11.31`) (PHP ^8.3)
- **Database Engines**: Microsoft SQL Server 2022 / MySQL / MariaDB (Hybrid PDO Driver)
- **Frontend Framework**: Vue 3 (`^3.4`) + Inertia.js 2.0 (`@inertiajs/vue3 ^2.0`)
- **Build Tool & Assets**: Vite 8 (`^8.1`) + PostCSS
- **Styling Engine**: Tailwind CSS v3 dengan Custom Design System (Emerald & Dark Slate-900)
- **Libraries & Key Services**:
  - `@vladmandic/face-api` (`^1.7`) - Biometrik pengenalan wajah untuk Face Login.
  - `wa-gateway` (Node.js Baileys Microservice) - Pengiriman notifikasi WhatsApp otomatis via port 3000.
  - `laravel/reverb` (`^1.10`) - Server WebSocket lokal untuk notifikasi real-time via port 8080.
  - `@lucide/vue` (`^1.18`) - Library ikon grafis Lucide.
  - `vite-plugin-pwa` (`^1.3`) - Capability PWA (Progressive Web App).
  - `sweetalert2` - Di-override secara global via helper `$swal` & `$toast` dengan modal glassmorphism `rounded-2xl`.

---

## 2. Universal Coding & Multi-Language Standards
- **Naming Convention**:
  - Database: `snake_case` jamak (*plural*), contoh: `users`, `service_tickets`, `supporting_units`.
  - PHP/Laravel: PSR-12 standard, `camelCase` untuk method/variabel, `StudlyCaps` untuk Controller/Model/Class.
- **Internationalization (i18n)**:
  - Semua string antarmuka UI **wajib** dibungkus helper lokalisasi `__(...)` atau `proxy.__('...')`.
  - Terjemahan bahasa dikelola terpusat pada file:
    - [id.json](file:///c:/Project/pesupeluh/lang/id.json) (`lang/id.json`) untuk Bahasa Indonesia.
    - [en.json](file:///c:/Project/pesupeluh/lang/en.json) (`lang/en.json`) untuk Bahasa Inggris.

---

## 3. UI/UX Design System Rules (Standar Tampilan Aplikasi)
Semua komponen dan halaman baru wajib mengikuti aturan tema baku aplikasi:

1. **Wadah Ikon Header / Card Header**:
   - Class: `bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white`
2. **Tombol Utama (Primary Buttons)**:
   - Light Mode: `bg-emerald-600 hover:bg-emerald-500 text-white`
   - Dark Mode: `dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold border-0 shadow-sm`
3. **Form Input Focus Rings**:
   - Class: `focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150`
   - *Catatan*: Dilarang menggunakan ring outline hijau tebal (`focus:ring-2 focus:ring-emerald-500`).
4. **Custom Select Dropdown ([SearchableSelect.vue](file:///c:/Project/pesupeluh/resources/js/Components/SearchableSelect.vue))**:
   - Digunakan untuk seluruh dropdown pilihan di aplikasi.
   - Properti `:absolute="false"` digunakan jika dropdown harus menggeser elemen di bawahnya secara merenggang (inline flow).
   - Event listener pembacaan klik luar menggunakan fase **Capture** (`true`) agar dapat ditutup dari mana saja meskipun di dalam modal dengan `@click.stop`.
5. **Badge Mode Gelap**:
   - Class: `dark:bg-white/10 dark:text-white dark:hover:bg-white/20 dark:border-white/10`

---

## 4. Master Database Definition (SQL Server Schema)

```sql
-- 1. Supporting Units (Statis - MEDIK / NON_MEDIK)
CREATE TABLE supporting_units (
    id INT IDENTITY(1,1) PRIMARY KEY,
    type VARCHAR(20) DEFAULT 'NON_MEDIK' NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description NVARCHAR(MAX) NULL,
    status VARCHAR(30) DEFAULT 'IN_DEVELOPMENT' NOT NULL,
    CONSTRAINT CHK_supporting_unit_status CHECK (status IN ('ACTIVE', 'IN_DEVELOPMENT', 'MAINTENANCE', 'INACTIVE')),
    CONSTRAINT CHK_supporting_unit_type CHECK (type IN ('MEDIK', 'NON_MEDIK'))
);

-- 2. Issue Categories (Dinamis per Supporting Unit)
CREATE TABLE issue_categories (
    id INT IDENTITY(1,1) PRIMARY KEY,
    supporting_unit_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description NVARCHAR(MAX) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,
    CONSTRAINT FK_issue_categories_supporting_units FOREIGN KEY (supporting_unit_id) 
        REFERENCES supporting_units(id) ON DELETE CASCADE
);

-- 3. Rooms (Dinamis)
CREATE TABLE rooms (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location_floor VARCHAR(50) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL
);

-- 4. Roles (Statis: ADMINISTRATOR, MANAGEMENT, UNIT_HEAD, TECHNICIAN, ROOM_HEAD, REPORTER)
CREATE TABLE roles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- 5. Users (Lengkap dengan NIP, Biometrik Face Descriptor, dan Pasfoto)
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    role_id INT NOT NULL,
    room_id INT NULL,
    supporting_unit_id INT NULL,
    nip VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    email_verified_at DATETIMEOFFSET NULL,
    password VARCHAR(255) NOT NULL,
    face_descriptor NVARCHAR(MAX) NULL,
    profile_photo_path VARCHAR(255) NULL,
    remember_token VARCHAR(100) NULL,
    phone_number VARCHAR(20) NULL,
    is_active BIT DEFAULT 0 NOT NULL,
    approved_by INT NULL,
    approved_at DATETIMEOFFSET NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL
);

-- 6. Service Tickets (Transaksi Tiket Pelaporan)
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
    paused_duration_seconds INT DEFAULT 0 NOT NULL,
    last_paused_at DATETIMEOFFSET NULL,
    completion_notes NVARCHAR(MAX) NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL
);
```

---

## 5. Client-Side Image Compression Rules
- Seluruh pengunggahan gambar (foto profil, lampiran bukti fisik kerusakan) **wajib dikompresi di sisi client (frontend)** menggunakan utilitas [imageCompressor.js](file:///c:/Project/pesupeluh/resources/js/Utils/imageCompressor.js) (`resources/js/Utils/imageCompressor.js`).
- Batas maksimal dimensi: **1200px** dengan kualitas JPEG **0.8 (80%)**.

---

## 6. Server Infrastructure & Nginx Production Tuning
- **Docker Stack**: 5 Container Utama (`pesupeluh-nginx`, `pesupeluh-php`, `pesupeluh-redis`, `pesupeluh-reverb`, `pesupeluh-queue-worker`) + Microservice `pesupeluh-wa-gateway`.
- **Nginx Hardening**: Gzip Level 6, Browser Caching 1 Tahun untuk `/build/assets/*`, FastCGI Buffering 64k, pemblokiran eksekusi `.php` di folder `/storage`, dan perizinan HTTP `OPTIONS` untuk CORS Preflight.