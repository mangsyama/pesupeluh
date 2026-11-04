# SPESIFIKASI PERANCANGAN ANTARMUKA & WORKFLOW MODUL KESLING (PAN-SINAR)

**Induk Sistem:** PESU PELUH *(RSUD Giri Asih)*  
**Sub-Sistem / Inovasi Modul:** PAN-SINAR *(Penunjang Non Medik - Sistematis, Integratif, Nyata, Akurat, Realtime)*  
**Fokus Modul:** Digitalisasi Pengendalian Kesehatan Lingkungan, Sanitasi Toilet, dan Respon Cepat Fasilitas  
**Penanggung Jawab:** Ns. I Nyoman Sukiman Sukawan, S.ST *(Kasi Penunjang Non Medik)*  

---

## 1. Perubahan pada Dashboard Induk (Layanan Penunjang Non-Medik)

Pada menu utama `Layanan Penunjang -> Penunjang Non-Medik`, kartu **KESLING** mengalami pembaruan status:

* **Ikon:** Mengganti ikon gembok terkunci (*locked*) menjadi ikon aktif (misal: *FaLeaf* / *FaPumpSoap* / *FaShieldAlt*).
* **Badge Status:** Berubah dari `DALAM PENGEMBANGAN` (abu-abu) menjadi `PILOT ACTIVE` atau `AKTIF` (badge hijau menyala).
* **Judul & Deskripsi Kartu:**
  * **Judul:** KESLING (PAN-SINAR)
  * **Deskripsi:** *Sistem kendali terpadu monitoring sanitasi lingkungan, respon cepat fasilitas toilet, dan pemilahan limbah berbasis data realtime.*
* **Interaksi Klik (*Action*):** Mengarahkan pengguna langsung ke sub-portal khusus: `/penunjang-non-medik/pan-sinar-kesling`.

---

## 2. Struktur Antarmuka Sub-Portal PAN-SINAR

Saat kartu KESLING terbuka, pengguna disambut dengan *workspace* mandiri yang memancarkan identitas penuh **PAN-SINAR** tanpa melepaskan keterikatan dengan induk aplikasi PESU PELUH.

### A. Header & Identitas Sub-Sistem
```text
[ <- Kembali ke Layanan Penunjang ]

┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [LOGO/IKON KESLING]   PAN-SINAR : KESEHATAN LINGKUNGAN                        [ STATUS: PILOT PROJECT ] │
│                       Penunjang Non Medik - Sistematis, Integratif, Nyata, Akurat, Realtime             │
│                       "Digitalisasi Monitoring & Pengendalian Sanitasi Lingkungan RSUD Giri Asih"       │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### B. Indikator Kinerja Utama (Top Stat Cards)
Di bawah header utama, terdapat 4 kartu ringkasan metrik *real-time*:
1. **Aduan Terbuka (Open Tickets):** Jumlah laporan toilet/sarana sanitasi yang menunggu penanganan teknisi atau petugas kebersihan.
2. **Kepatuhan SLA Vendor CS (%):** Tingkat keberhasilan vendor *cleaning service* menyelesaikan tiket sebelum batas waktu yang disepakati (Target $\ge 90\%$).
3. **Ceklis Inspeksi Harian:** Progres pemeriksaan rutin kebersihan per shift oleh pengawas internal Kesling.
4. **Insiden Darurat (Panic Button):** Jumlah panggilan darurat toilet yang tercatat dan status respon tim medis/keamanan.

---

## 3. Pembagian Tab Navigasi & Modul Olah Data

Modul PAN-SINAR memiliki 4 tab kerja fungsional:

### Tab 1: Aduan Sanitasi & Toilet (Sistem Tiket)
Menampilkan seluruh tiket masuk baik dari pemindaian QR Code publik maupun pelaporan internal bangsal.

* **Struktur Kolom Data:**
  * `ID Tiket` & `Timestamp`: Nomor registrasi otomatis dan waktu aduan dikirim.
  * `Lokasi Presisi`: Gedung (A/B/C), Lantai, dan Titik Toilet/Area.
  * `Kategori Masalah`: Toilet Kotor/Bau, Air Macet, Sabun/Tisu Habis, Kerusakan Pintu/Kran, dll.
  * `Tingkat Urgensi`: Darurat (Emergency Call), Tinggi, Sedang, Rendah.
  * `Petugas / PIC`: Petugas vendor CS atau Sarpras yang bertugas.
  * `SLA Target`: Countdown waktu respon (contoh: *Sisa waktu 18 menit*).
  * `Status`: *Open*, *In Progress*, *Resolved*, *Closed*.
* **Mekanisme Validasi Penutupan Tiket:**
  * Tombol **"Selesaikan Tiket"** mewajibkan unggah bukti foto perbaikan (*Before & After*).
  * Verifikasi akhir dilakukan oleh Kasi Penunjang Non-Medik atau Admin Kesling.

### Tab 2: Monitoring Ceklis Harian (Inspeksi Proaktif)
Formulir inspeksi sanitasi berkala yang diisi oleh staf Kesling atau supervisor vendor CS tiap siklus patroli (misal: tiap 3–4 jam sekali).

* **Checklist Komponen:**
  * Ketersediaan air bersih dan fungsi *flush*.
  * Kebersihan lantai, kloset, dan cermin wastafel.
  * Ketersediaan fasilitas habis pakai (*hand soap*, tisu, tempat sampah tertutup).
  * Keharuman dan sirkulasi udara ruangan.
* **Fitur Anti-Manipulasi:** Checklist hanya dapat disimpan jika posisi GPS atau kode verifikasi fisik di lokasi cocok saat inspeksi dilakukan.

### Tab 3: Pengelolaan Sampah & Limbah B3
Pencatatan dan tata kelola alur limbah medis maupun domestik:

* **Pencatatan Timbangan Harian:** Input berat limbah (kg) per unit/ruangan setiap pergantian shift.
* **Monitoring TPS B3 & Domestik:** Pemantauan kapasitas penampungan sementara sebelum jadwal angkut oleh pihak ketiga berizin.
* **Log Manifest Limbah:** Penyimpanan data manifest pengangkutan secara digital untuk kepatuhan regulasi lingkungan hidup dan akreditasi RS.

### Tab 4: Pengaturan QR Code & Notifikasi
Menu konfigurasi dan manajemen sarana fisik:

* **Generator QR Code Lokasi:** Pembuatan kode QR otomatis yang mengunci parameter lokasi (Gedung, Lantai, ID Ruangan).
* **Fitur Cetak Stiker:** Template siap cetak untuk ditempel pada daun pintu toilet atau dinding area tunggu.
* **Konfigurasi Emergency Call (Panic Button):**
  * Pengaturan daftar 2–3 nomor WhatsApp PIC utama (Koordinator CS, Teknisi Sarpras, dan Security/Perawat Jaga).
  * Pengaturan durasi tahan tombol darurat (*Hold for 3 seconds*) guna mencegah penekanan tidak sengaja (*anti-false alarm*).

---

## 4. Alur Integrasi Data & Disposisi Cepat

```
[Pengunjung / Staf Scan QR Toilet]
                │
    ┌───────────┴───────────────────────────────┐
    ▼                                           ▼
[Laporan Kebersihan / Kerusakan]    [Emergency Button (Tahan 3 Detik)]
    │                                           │
    ▼                                           ▼
Form Cepat (Tanpa Login)            Broadcast Darurat ke WA Multi-PIC
    │                                           │
    ▼                                           ▼
Tiket Masuk ke Tab 1 PAN-SINAR      Tim Terdekat Langsung Merapat
    │
    ▼
Disposisi Otomatis ke Vendor CS / Sarpras
    │
    ▼
Pengerjaan & Upload Foto Bukti Selesai
    │
    ▼
Verifikasi Kasi Kesling -> Tiket Ditutup
```

---

## 5. Ringkasan Manfaat bagi Manajemen Rumah Sakit

1. **Eksistensi Branding Inovasi Terjaga:** Identitas PAN-SINAR tampil jelas sebagai karya inovasi kepemimpinan pengawas, sekaligus tetap terintegrasi dalam platform PESU PELUH.
2. **Eliminasi Birokrasi Berbelit:** Laporan fasilitas langsung menuju unit teknis tanpa menyasar dan mengganggu perawat ruangan.
3. **Instrumen Evaluasi Kinerja Vendor:** Manajemen rumah sakit memegang data analitik konkret terkait kedisiplinan dan respon vendor *cleaning service*.
4. **Peningkatan Standar Keselamatan:** Respon darurat di area privat seperti toilet menjadi terukur dan terlindungi secara optimal.