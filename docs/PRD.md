# PRODUCT REQUIREMENT DOCUMENT (PRD)

## 1. Document Control
- **Product Name**: PESU PELUH (Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan)
- **Phase**: Target Jangka Pendek (1 – 2 Bulan) - Modul Pelaporan IPSRS (Sistem Informasi Pemeliharaan Sarana Prasarana Rumah Sakit)
- **Version**: 1.0.0
- **Status**: Approved for Development
- **Release Target Date**: August 2026

---

## 2. Product Vision & Goals
- **Vision**: Menjadi platform *Service Desk* internal rumah sakit yang mengintegrasikan seluruh unit penunjang (Medik dan Non-Medik) lewat satu sentuhan aplikasi yang dinamis, berestetika premium, dan responsif.
- **Core Goal**: Mendigitalisasi alur pelaporan kerusakan dan kendala fasilitas di area IPSRS, memangkas birokrasi penunjukan petugas, serta menyajikan metrik performa pelayanan secara akurat berbasis data real-time.

---

## 3. User Roles & Access Control (RBAC Logic)
Sistem menggunakan pendekatan fungsional berbasis peran (*role*). Hak akses menu di aplikasi ditentukan oleh kombinasi peran dan lokasi unit kerja:

1. **ADMINISTRATOR (Super Admin)**
   - Memiliki hak akses mutlak (Full Access) ke seluruh sistem operasional dan pengaturan.
   - Bertanggung jawab atas manajemen user (CRUD akun, reset password, face login re-register).
   - Wajib melakukan validasi dan aktivasi akun pendaftaran user baru berdasarkan kesesuaian data NIP dan Foto Diri yang diunggah.
   - Bertanggung jawab atas pengelolaan data master (Divisi, Unit, Kategori, Ruangan).
   - Memiliki hak "Super Override" untuk mengedit atau memperbaiki data tiket laporan yang salah input di lapangan demi kebersihan data analitik.

2. **DIRECTOR (Direktur)**
   - Memiliki hak akses Read-Only ke seluruh Dashboard Analytics lintas divisi (Medik & Non-Medik).
   - Memantau performa makro rumah sakit, pencapaian SLA, dan laporan tahunan/bulanan.

3. **DIVISION_HEAD (Kabid - Kepala Bidang)**
   - Memiliki hak akses Read-Only ke Dashboard Analytics khusus divisi yang berada di bawah pengawasannya (misal: Kabid Penunjang Non-Medik hanya mengawasi IPSRS, Gizi, Laundry, Kesling).

4. **SECTION_HEAD (Kasi - Kepala Seksi)**
   - Memiliki hak akses Read-Only ke Dashboard Analytics seksi penunjang spesifik yang dibawahi secara struktural.

5. **UNIT_HEAD (Kepala IPSRS, Kepala Gizi, dll)**
   - Memiliki kendali penuh atas antrean laporan masuk yang mengarah ke unitnya.
   - Berhak melakukan validasi laporan, menentukan prioritas, dan melakukan disposisi petugas.

6. **TECHNICIAN (Teknisi / Eksekutor Lapangan)**
   - Menerima disposisi tugas kerja di halaman aplikasinya.
   - Berhak melakukan konfirmasi kedatangan (*Response Time*), mengisi log penundaan (*Pending*), dan menyelesaikan tiket (*Completed*).

7. **ROOM_HEAD (Kepala Ruangan ICU/IGD/Poliklinik/dll)**
   - Menerima notifikasi otomatis (*Carbon Copy*) jika ada staf di ruangannya yang membuat laporan kendala fasilitas sebagai bentuk pertanggungjawaban area.

8. **REPORTER (Staf Ruangan / User Umum)**
   - Dapat membuat laporan kendala dari ruangan tempatnya bertugas kapan pun tanpa batasan waktu (24/7) untuk mencegah keterlambatan penanganan.

---

## 4. Functional Requirements & Feature Tree
Aplikasi wajib merender menu secara dinamis menggunakan pola hirarki data bertingkat dari database:
`Divisions` (Medik / Non-Medik) -> `Supporting Units` (IPSRS, Gizi, Laundry, Kesling) -> `Unit Features` (Pelaporan, Kalibrasi, Usulan) -> `Feature Categories`.

### Fitur Penguncian Modul (Feature Flagging)
Akses terhadap modul di dalam aplikasi dikontrol secara dinamis berdasarkan status operasional dari masing-masing `Supporting Units`:
- **ACTIVE**: Fitur pelaporan terbuka penuh dan dapat diakses oleh seluruh user ruangan (Saat ini eksklusif untuk IPSRS).
- **IN_DEVELOPMENT**: Menu unit tetap muncul di aplikasi sebagai representasi hirarki, namun dalam kondisi terkunci (disabled) dengan tampilan visual premium bertuliskan "Dalam Pengembangan".
- **INACTIVE**: Menu unit disembunyikan sepenuhnya dari antarmuka user biasa.

---

## 5. Core Ticketing Workflow (Alur Kerja Tiket)
1. **Submission**: Reporter mengisi form pelaporan sesuai kategori kerusakan. Status awal otomatis: `PENDING_VALIDATION`. Notifikasi otomatis (CC) dikirim ke akun Kepala Ruangan asal.
2. **Validation & Dispatch**: Kepala IPSRS memeriksa antrean tiket baru, menentukan prioritas (`URGENT` / `ROUTINE`), lalu mendisposisikan ke satu atau beberapa Teknisi yang keahliannya sesuai. Status berubah menjadi `ASSIGNED`.
3. **On-Site Response**: Teknisi tiba di lokasi ruangan dan menekan tombol **"Response Time"** di aplikasi. Sistem mengunci waktu respon dan status berubah menjadi `IN_PROGRESS`.
4. **Resolution**: Setelah proses penanganan di lapangan, teknisi mengubah status tiket menjadi salah satu dari opsi berikut:
   - `COMPLETED`: Masalah selesai diperbaiki. Wajib unggah foto bukti hasil kerja dan mengisi catatan kesimpulan.
   - `PENDING`: Perbaikan tertunda (misal: menunggu *sparepart* atau mati lampu). Wajib mengisi alasan penundaan secara tertulis.
   - `CANCEL`: Laporan dibatalkan karena setelah dicek di lapangan ternyata tidak ada kerusakan fisik (salah lapor).
5. **Reporting**: Seluruh riwayat perubahan status dan pencatatan waktu otomatis ditarik secara *real-time* ke dashboard manajemen.

---

## 6. Performance Metrics (SLA)
Sistem harus mengkalkulasi metrik berikut secara otomatis untuk kebutuhan rekapitulasi:
- **Response Time (Waktu Tanggap)**: Selisih waktu antara Kepala Unit melakukan disposisi (`validated_at`) hingga teknisi menekan tombol tiba di lokasi (`responded_at`).
- **Resolution Time (Waktu Penyelesaian)**: Selisih waktu keseluruhan dari tiket pertama kali dibuat oleh reporter (`created_at`) hingga tiket dinyatakan ditutup/selesai (`resolved_at`).
- Seluruh log waktu dihitung menggunakan format **`DATETIMEOFFSET`** untuk menjamin keakuratan hingga milidetik dan konsistensi zona waktu WITA (+08:00).

---

## 7. Non-Functional Requirements & Performance Constraints
- **Media & Image Handling Optimization**:
  - Setiap proses unggah berkas foto (baik berupa file gambar pendukung maupun pas foto profil pada modul registrasi) **wajib melalui proses kompresi otomatis di sisi klien (frontend)** sebelum dikirim ke server.
  - Proses kompresi ini dikelola secara terpusat pada file utilitas: [imageCompressor.js](file:///c:/project/pesupeluh/resources/js/Utils/imageCompressor.js) (`resources/js/Utils/imageCompressor.js`).
  - Ketentuan ini bertujuan untuk menghemat konsumsi bandwidth jaringan pengguna seluler serta efisiensi penyimpanan storage server tanpa mengurangi kualitas ketajaman foto secara signifikan (batas toleransi kompresi kualitas 80% dan dimensi maksimal 1200px).

- **Real-Time Notification Capabilities (WebSocket)**:
  - Aplikasi harus mengirimkan notifikasi penting (seperti registrasi user baru untuk Administrator, disposisi tiket untuk Teknisi, dan perubahan status tiket untuk Reporter) secara instan tanpa delay menggunakan server WebSocket lokal (Laravel Reverb).
  - Penerimaan notifikasi real-time wajib memperbarui angka badge notifikasi unread dan state daftar notifikasi pada navigasi/sidebar secara real-time tanpa memaksa user melakukan reload halaman manual.

- **Unified Custom Dialog & Alert System**:
  - Semua kotak dialog informasi (sukses, error, peringatan) dan kotak konfirmasi aksi di seluruh aplikasi harus menggunakan **Custom Dialog Modal** kustom dengan tema glassmorphism dan membulat `rounded-2xl`.
  - Integrasi ini di-override secara global agar otomatis menggantikan library SweetAlert2 tanpa memerlukan penulisan ulang sintaksis di setiap file Vue page, memastikan konsistensi antarmuka pengguna di seluruh aplikasi.