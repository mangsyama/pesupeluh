# 🛠️ Panduan Perintah Terminal — Pesu Peluh

Dokumen ini berisi kumpulan perintah terminal (*Cheat Sheet*) sehari-hari untuk proyek **Pesu Peluh** (Laravel, Storage Link, Cache, Migration, & Development), baik untuk lingkungan **Windows Local** maupun **Server**.

---

## 🔗 1. Perintah Link & Storage (Penanganan Gambar & File Upload)

Gunakan perintah ini jika gambar upload atau file di storage tidak muncul / bermasalah.

### A. Windows (Local Development)
Di Windows PowerShell / Terminal local:
```powershell
# Cara 1: Menggunakan Junction Link Windows (Paling Stabil di Windows tanpa butuh Akses Administrator)
cmd /c mklink /J "public\storage" "storage\app\public"

# Cara 2: Menggunakan Command Laravel Artisan
php artisan storage:link

# Cara Hapus Link Storage jika rusak/ingin buat ulang:
cmd /c rmdir "public\storage"
# Atau di PowerShell:
Remove-Item -Recurse -Force "public\storage"
```

### B. Docker / Server Production
```bash
# Hubungkan link storage di dalam container PHP
docker compose exec php php artisan storage:link
```

---

## ⚡ 2. Perintah Cache & Optimasi Laravel

Gunakan perintah ini saat setelah mengubah file `.env`, `routes`, `config`, atau jika ada keanehan pada tampilan/sistem.

```bash
# 🧹 Hapus SEMUA Cache (Config, Route, View, Event)
php artisan optimize:clear

# ⚡ Buat Cache Baru untuk Performa Maksimal (Gunakan di Production)
php artisan optimize

# 🔄 Regenerasi Autoload Class PHP (Jika tambah/ubah class & helper)
composer dump-autoload
```

---

## 🗄️ 3. Perintah Database & Migrasi

```bash
# Jalankan migrasi database baru
php artisan migrate

# Cek status migrasi yang sudah/belum berjalan
php artisan migrate:status

# Rollback 1 langkah migrasi terakhir
php artisan migrate:rollback

# Reset total database & jalankan ulang seeder (⚠️ Data lama akan terhapus)
php artisan migrate:fresh --seed
```

---

## 🚀 4. Perintah Development Local

```powershell
# Running Sekaligus (Laravel + Vite + Worker) via Script Project
.\run-dev.ps1

# Atau Jalankan Manual per Terminal:
# 1. Server Laravel
php artisan serve

# 2. Frontend Assets (Vite)
npm run dev

# 3. Queue Worker (Proses Notifikasi & Background Task)
php artisan queue:work

# 4. WebSocket Server (Reverb Realtime)
php artisan reverb:start
```

---

## 🐳 5. Perintah Docker Server (Ringkasan)

Untuk panduan Docker Server production yang lebih detail, silakan buka **[DOCKER.md](file:///c:/Project/pesupeluh/docs/DOCKER.md)**.

```bash
# Start & Build Container
docker compose up -d --build

# Restart PHP-FPM
docker compose restart php

# Cek Log Real-Time PHP
docker compose logs -f php
```
