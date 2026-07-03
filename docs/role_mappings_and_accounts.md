# Panduan Pemetaan Tampilan & Akun Uji Coba

Dokumen ini berisi pemetaan file tampilan frontend berdasarkan role akses, serta daftar akun uji coba (*seeded accounts*) untuk mempermudah navigasi pengembangan.

---

## 1. Tabel Pemetaan File Tampilan Berdasarkan Role User

| Role Akses                 | Jenis    | Route URL Utama             | File Halaman Induk (Parent)                                                     | File Komponen Tampilan (Partial)                                                       | Keterangan Fungsi Utama                                               |
| :------------------------- | :------- | :-------------------------- | :------------------------------------------------------------------------------ | :------------------------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| **STAFF / REPORTER (8)**   | List     | `/reports`                  | [Report/Index.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Index.vue)            | [ReporterIndex.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Partials/ReporterIndex.vue)   | Riwayat semua laporan pribadi yang dia buat sendiri.                  |
|                            | Detail   | `/reports/{uuid}`           | [Report/Show.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Show.vue)              | [ReporterShow.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Partials/ReporterShow.vue)     | Tampilan kronologi status tiket (Read-Only / Pemantauan).             |
| **KEPALA RUANGAN (7)**     | List     | `/reports-management`       | [ReportManagement/Index.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Index.vue) | [ReporterIndex.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Partials/ReporterIndex.vue)   | Memantau daftar semua laporan aktif di ruangan yang ia pimpin.        |
|                            | Detail   | `/reports-management/{uuid}`| [ReportManagement/Show.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Show.vue)   | [ReporterShow.vue](file:///c:/project/pesupeluh/resources/js/Pages/Report/Partials/ReporterShow.vue)     | Memantau detail perkembangan tiket ruangan bersangkutan (Read-Only).  |
| **KEPALA LAYANAN (5)**     | List     | `/reports-management`       | [ReportManagement/Index.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Index.vue) | [UnitHeadIndex.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Partials/UnitHeadIndex.vue) | Papan validasi masuk, penentuan prioritas, dan antrean penugasan.     |
|                            | Detail   | `/reports-management/{uuid}`| [ReportManagement/Show.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Show.vue)   | [UnitHeadShow.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Partials/UnitHeadShow.vue)   | Aksi menunjuk teknisi kerja, validasi hasil kerja teknisi, dll.       |
| **TEKNISI (6)**            | List     | `/reports-management`       | [ReportManagement/Index.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Index.vue) | [TechnicianIndex.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Partials/TechnicianIndex.vue) | Daftar antrean semua tugas perbaikan yang didelegasikan ke dirinya.   |
|                            | Detail   | `/reports-management/{uuid}`| [ReportManagement/Show.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Show.vue)   | [TechnicianShow.vue](file:///c:/project/pesupeluh/resources/js/Pages/ReportManagement/Partials/TechnicianShow.vue)   | Merespon kedatangan (Arrive), menunda, dan menyelesaikan tugas.       |

---

## 2. Daftar Akun Uji Coba (Database Seeder)

Semua akun di bawah menggunakan password default yang sama: **`12345678`**.

| Nama Lengkap                 | Username       | Email                       | NIP                  | Role Akses                 | Keterangan Tambahan                                   |
| :--------------------------- | :------------- | :-------------------------- | :------------------- | :------------------------- | :---------------------------------------------------- |
| **Administrator**            | `admin`        | `admin@example.com`         | `197001011995011001` | `ADMINISTRATOR` (Role 1)   | Akses penuh ke seluruh menu dan manajemen user.       |
| **Dr. Hermawan (Direktur)**  | `direktur`     | `direktur@example.com`      | `197102021996021002` | `DIRECTOR` (Role 2)        | Akses monitoring laporan eksekutif di dashboard.      |
| **Budi Santoso (Kabid)**     | `kabid`        | `kabid@example.com`         | `197203031997031003` | `DIVISION_HEAD` (Role 3)   | Kepala Bidang Penunjang (Monitoring Divisi).          |
| **Rina Amelia (Kasi)**       | `kasi`         | `kasi@example.com`          | `197304041998042004` | `SECTION_HEAD` (Role 4)    | Kepala Seksi Fasilitas.                               |
| **Hendra Wijaya (Ka. Unit)** | `kanit_ipsrs`  | `kanit_ipsrs@example.com`   | `197405051999051005` | `UNIT_HEAD` (Role 5)       | Kepala Unit IPSRS (Validasi & Disposisi Tiket).       |
| **Joko Prasetyo (Teknisi)**  | `teknisi1`     | `teknisi1@example.com`      | `197506062000061006` | `TECHNICIAN` (Role 6)      | Teknisi 1 IPSRS (Operasional perbaikan).              |
| **Agus Setiawan (Teknisi)**  | `teknisi2`     | `teknisi2@example.com`      | `197607072001071007` | `TECHNICIAN` (Role 6)      | Teknisi 2 IPSRS (Operasional perbaikan).              |
| **Siti Rahmah (Karu IGD)**   | `karu_igd`     | `karu_igd@example.com`      | `197708082002082008` | `ROOM_HEAD` (Role 7)       | Kepala Ruangan IGD (Memantau ruangannya).             |
| **Dian Lestari (Staff)**     | `staf_dian`    | `staf_dian@example.com`     | `197809092003092009` | `REPORTER` (Role 8)        | Staff Perawat IGD (Membuat laporan baru).             |
