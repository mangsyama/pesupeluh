# PRODUCT REQUIREMENT DOCUMENT (PRD) — PESU PELUH

## 1. Document Control
- **Product Name**: PESU PELUH (Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan)
- **Framework & Stack**: Laravel 11 + Vue 3 + Inertia.js 2.0 + Tailwind CSS v3
- **Version**: 2.0.0 (Production-Ready)
- **Status**: Live / Production Deployment

---

## 2. Product Vision & Goals
- **Vision**: Menjadi platform *Service Desk* internal rumah sakit yang mengintegrasikan seluruh unit penunjang (Medik dan Non-Medik) lewat satu sentuhan aplikasi yang dinamis, berestetika premium, dan responsif.
- **Core Goal**: Mendigitalisasi alur pelaporan kerusakan dan kendala fasilitas di area IPSRS, CSSD, SIMRS, dan unit penunjang lainnya, memangkas birokrasi penunjukan petugas, serta menyajikan metrik performa pelayanan secara akurat berbasis data real-time.

---

## 3. Active User Roles & Access Control (RBAC)
Sistem menggunakan pendekatan fungsional berbasis peran (*role*) dan penempatan unit kerja yang telah disederhanakan dan disesuaikan dengan fakta aplikasi:

1. **ADMINISTRATOR (Super Admin)**
   - Memiliki hak akses mutlak (Full Access) ke seluruh sistem operasional dan pengaturan.
   - Manajemen user (CRUD akun, verifikasi pendaftaran baru, reset password, biometrik wajah).
   - Pengelolaan data master layanan (Unit Penunjang, Kategori Masalah, Ruangan).
   - Pengaturan hak akses halaman (*Page Permissions Modal*) per pengguna.

2. **MANAGEMENT (Pimpinan / Direksi / Kabid)**
   - Memiliki hak akses Read-Only & Executive Dashboard Analytics.
   - Memantau performa makro rumah sakit, pencapaian SLA, dan laporan volume aktivitas unit penunjang.

3. **UNIT_HEAD (Kepala Unit Penunjang - IPSRS, SIMRS, CSSD, dll)**
   - Memiliki kendali penuh atas antrean laporan masuk yang mengarah ke unitnya.
   - Berhak melakukan validasi laporan, menentukan prioritas (`URGENT` / `ROUTINE`), dan disposisi teknisi.

4. **TECHNICIAN (Teknisi / Eksekutor Lapangan)**
   - Menerima disposisi tugas kerja di halaman teknisi.
   - Mengonfirmasi waktu tanggap (*Response Time*), mengisi log penundaan (*Pending*), dan menyelesaikan tiket (*Completed*).

5. **ROOM_HEAD (Kepala Ruangan ICU/IGD/Poliklinik/dll)**
   - Menerima notifikasi otomatis (*Carbon Copy*) jika ada staf di ruangannya yang membuat laporan kendala fasilitas.

6. **REPORTER (Staf Ruangan / Pelapor Umum)**
   - Membuat laporan kendala fasilitas dari ruangan tempatnya bertugas kapan pun tanpa batasan waktu (24/7).

---

## 4. Core Features & Module Capabilities

### A. Service Management (Manajemen Layanan)
- **Kategori Masalah ([CategoryList.vue](file:///c:/Project/pesupeluh/resources/js/Pages/ServiceManagement/Partials/CategoryList.vue))**: Pengelolaan kategori masalah per unit penunjang.
- **Daftar Ruangan ([RoomList.vue](file:///c:/Project/pesupeluh/resources/js/Pages/ServiceManagement/Partials/RoomList.vue))**: Pengelolaan nama dan lokasi lantai ruangan rumah sakit.
- **Unit Penunjang ([SupportingUnitList.vue](file:///c:/Project/pesupeluh/resources/js/Pages/ServiceManagement/Partials/SupportingUnitList.vue))**: Pengelolaan unit Penunjang Medik (SIMRS, CSSD) & Non-Medik (IPSRS) beserta status operasional (`ACTIVE`, `IN_DEVELOPMENT`, `MAINTENANCE`, `INACTIVE`).

### B. User Management & Approval Flow
- **Pendaftaran & Verifikasi ([Detail.vue](file:///c:/Project/pesupeluh/resources/js/Pages/UserManagement/Approval/Detail.vue))**: Verifikasi pendaftar baru oleh Admin dengan penetapan Peran, Unit Penunjang, dan Ruangan.
- **Pengaturan Akses Halaman ([Index.vue](file:///c:/Project/pesupeluh/resources/js/Pages/UserManagement/Index.vue))**: Modal kustom untuk mengatur perizinan modul (*page permissions*) per pengguna.

### C. Biometric Face Login (`face-api`)
- Autentikasi biometrik wajah berbasis `@vladmandic/face-api` untuk login cepat dan verifikasi identitas fisik pengguna.

### D. WhatsApp Gateway Microservice
- Integrasi otomatis dengan microservice Node.js Baileys (`wa-gateway` di port 3000) untuk pengiriman notifikasi WhatsApp instan ke pendaftar, teknisi, dan pelapor.

---

## 5. Core Ticketing & SLA Workflow
1. **Submission**: Reporter mengisi form pelaporan (`PENDING_VALIDATION`).
2. **Validation & Dispatch**: Unit Head memeriksa laporan, menetapkan prioritas, dan mendisposisikan ke Teknisi (`ASSIGNED`). Notifikasi WhatsApp terkirim otomatis.
3. **Response Time**: Teknisi mengonfirmasi kedatangan di lokasi (`IN_PROGRESS`).
4. **Resolution**: Teknisi menyelesaikan tiket (`COMPLETED`), menunda (`PENDING`), atau membatalkan (`CANCEL`).
5. **Analytics**: Metrik Response Time & Resolution Time dicatat otomatis dengan standar timestamp `DATETIMEOFFSET` (WITA +08:00).

---

## 6. Standardized UI Design System
- **Color Palette**: Emerald-600 (`#059669`) untuk light mode dan Dark Slate-900 (`#0f172a`) + Slate-800 (`#1e293b`) untuk dark mode.
- **Header Icon Containers**: `bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white`.
- **Primary Buttons**: Light `bg-emerald-600 hover:bg-emerald-500`, Dark `dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold border-0 shadow-sm`.
- **Inputs Focus Ring**: `focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150`.
- **SearchableSelect Component**: Custom select dropdown ([SearchableSelect.vue](file:///c:/Project/pesupeluh/resources/js/Components/SearchableSelect.vue)) dengan dukungan pencarian, opsi inline expansion (`:absolute="false"`), dan capture-phase click outside handling.
- **Global Glassmorphism Dialogs**: Override SweetAlert2 ($swal & $toast) menggunakan modal kustom berdesain premium.