# DATABASE PLANNING & DECOUPLING ARCHITECTURE

Dokumen ini menjelaskan rancangan arsitektur database untuk memisahkan fitur **Pelaporan Kerusakan (Incident Reporting)**, **Kalibrasi Aset Medik (Calibration)**, dan **Usulan (Proposal)** pada aplikasi Pesu Peluh. Pemisahan ini dirancang agar data transaksi tetap bersih, efisien, dan siap dikembangkan pada fase berikutnya tanpa mengganggu fitur pelaporan yang sudah berjalan.

---

## 1. Skema Saat Ini vs Skema Rekomendasi (Fase Mendatang)

### A. Tabel Referensi & Master
Saat ini tabel `feature_categories` terhubung secara generic ke `unit_features`. Ke depannya, tabel ini akan dikunci khusus untuk rincian klasifikasi kerusakan pelaporan.

#### [Pelaporan Kerusakan - Modul Fasilitas & Sanitasi]
Tabel referensi kerusakan dikunci hanya untuk tipe fitur `reporting`.
- Tabel **`reporting_categories`** (sebelumnya `feature_categories`): Menyimpan jenis kategori kendala (AC, Lampu, Wastafel, dll).

#### [Kalibrasi Aset Medik]
Tabel referensi alat medik dikelola secara riil berbasis inventaris rumah sakit (bukan lagi static string category).
- Tabel **`medical_assets`** (Baru): Menyimpan semua data alat kesehatan yang terdaftar di rumah sakit beserta status kelayakannya.

#### [Usulan Klien]
Tabel referensi tipe usulan/renovasi.
- Tabel **`proposal_types`** (Baru - Opsional): Menyimpan jenis usulan kustom (Renovasi, Pengadaan Baru, Mutasi Alat, dll).

---

## 2. Definisi Struktur Tabel Baru (T-SQL)

Berikut adalah rancangan script DDL SQL Server untuk di-eksekusi di fase berikutnya:

```sql
-- =========================================================================
-- FASE 2: DETAILED MEDICAL ASSET & CALIBRATION MODULE SCHEMA
-- =========================================================================

-- 1. Tabel Master: Medical Assets (Aset Alat Kesehatan Medis)
CREATE TABLE medical_assets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    asset_number VARCHAR(100) NOT NULL UNIQUE, -- Kode Inventaris/Barcode Alkes
    name VARCHAR(150) NOT NULL,                 -- Nama Alat (contoh: ECG Machine)
    brand VARCHAR(100) NULL,                   -- Merk Pabrikan (contoh: GE Healthcare)
    model VARCHAR(100) NULL,                   -- Tipe/Model (contoh: MAC 2000)
    serial_number VARCHAR(100) NULL,           -- Nomor Seri Pabrik
    room_id INT NULL,                          -- Lokasi Ruangan Terakhir
    status VARCHAR(30) DEFAULT 'ACTIVE' NOT NULL, -- ACTIVE, CALIBRATION_REQUIRED, OUT_OF_ORDER
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,
    
    CONSTRAINT FK_medical_assets_rooms FOREIGN KEY (room_id) 
        REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT CHK_medical_asset_status CHECK (status IN ('ACTIVE', 'CALIBRATION_REQUIRED', 'OUT_OF_ORDER'))
);

-- 2. Tabel Transaksi: Calibration Requests (Tiket Pengajuan Kalibrasi)
CREATE TABLE calibration_tickets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,      -- Format: CAL-YYYYMMDD-XXXX
    reporter_id INT NOT NULL,                        -- Pegawai yang mengajukan
    medical_asset_id INT NOT NULL,                  -- Alat yang diajukan kalibrasi
    urgency VARCHAR(20) DEFAULT 'ROUTINE' NOT NULL,  -- ROUTINE, URGENT
    description NVARCHAR(MAX) NULL,                 -- Keterangan kondisi alat
    status VARCHAR(30) DEFAULT 'PENDING' NOT NULL,  -- PENDING, APPROVED, IN_PROGRESS, COMPLETED, REJECTED
    calibrated_at DATETIMEOFFSET NULL,              -- Tanggal eksekusi kalibrasi
    certificate_path VARCHAR(255) NULL,             -- URL file sertifikat kalibrasi hasil akhir
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,

    CONSTRAINT FK_calibration_tickets_users_reporter FOREIGN KEY (reporter_id) 
        REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_calibration_tickets_assets FOREIGN KEY (medical_asset_id) 
        REFERENCES medical_assets(id) ON DELETE NO ACTION,
    CONSTRAINT CHK_calibration_ticket_urgency CHECK (urgency IN ('ROUTINE', 'URGENT')),
    CONSTRAINT CHK_calibration_ticket_status CHECK (status IN ('PENDING', 'APPROVED', 'IN_PROGRESS', 'COMPLETED', 'REJECTED'))
);

-- =========================================================================
-- FASE 3: DYNAMIC PROPOSAL MODULE SCHEMA
-- =========================================================================

-- 3. Tabel Transaksi: Proposal Tickets (Usulan & Justifikasi Anggaran)
CREATE TABLE proposal_tickets (
    id INT IDENTITY(1,1) PRIMARY KEY,
    uuid UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL UNIQUE,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,      -- Format: PR-YYYYMMDD-XXXX
    reporter_id INT NOT NULL,                        -- Pegawai pengusul
    title VARCHAR(200) NOT NULL,                    -- Judul Usulan (e.g. Renovasi Instalasi Gizi)
    description NVARCHAR(MAX) NOT NULL,             -- Justifikasi detail urgensi
    estimated_budget DECIMAL(18,2) NULL,            -- Estimasi Biaya (jika ada)
    status VARCHAR(30) DEFAULT 'SUBMITTED' NOT NULL, -- SUBMITTED, REVIEW, APPROVED, REJECTED, ARCHIVED
    approved_by INT NULL,                           -- Pejabat yang menyetujui (Direksi)
    approved_at DATETIMEOFFSET NULL,
    created_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    updated_at DATETIMEOFFSET DEFAULT SYSDATETIMEOFFSET() NOT NULL,
    deleted_at DATETIMEOFFSET NULL,

    CONSTRAINT FK_proposal_tickets_users_reporter FOREIGN KEY (reporter_id) 
        REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_proposal_tickets_users_approver FOREIGN KEY (approved_by) 
        REFERENCES users(id) ON DELETE NO ACTION,
    CONSTRAINT CHK_proposal_ticket_status CHECK (status IN ('SUBMITTED', 'REVIEW', 'APPROVED', 'REJECTED', 'ARCHIVED'))
);
```

---

## 3. Strategi Desain Modul Frontend (Vue 3) Tanpa Hardcoding

Untuk memanggil modul formulir secara dinamis di `Show.vue` tanpa menulis perkondisian hardcoded yang kaku (`v-if="activeFeature === 'kalibrasi'"`), arsitektur disarankan menggunakan **Dynamic Component Mapping** di Vue 3:

1. **Tambahkan tipe fitur pada database (`unit_features`)**:
   Kolom baru `feature_type` (e.g., `reporting`, `calibration`, `proposal`) ditambahkan ke seeder `unit_features` sebagai pengenal mesin.
   
2. **Pola Pemetaan di Vue (`Show.vue`)**:
   ```javascript
   import { defineAsyncComponent } from 'vue';

   // Memuat form secara dinamis (lazy loaded) agar performa loading awal tetap cepat
   const formComponents = {
       reporting: defineAsyncComponent(() => import('./Partials/ReportingForm.vue')),
       calibration: defineAsyncComponent(() => import('./Partials/CalibrationForm.vue')),
       proposal: defineAsyncComponent(() => import('./Partials/ProposalForm.vue'))
   };
   ```

3. **Render secara Dinamis di Template**:
   ```html
   <component 
       :is="formComponents[activeFeature.feature_type]" 
       :unit="unit"
       :feature="activeFeature"
       :rooms="rooms"
   />
   ```

Dengan pola ini, jika nanti ada modul keempat (contoh: `peminjaman_alat`), developer hanya perlu membuat file komponen form baru dan mendaftarkannya pada objek mapping tanpa mengganggu komponen layout induk.
