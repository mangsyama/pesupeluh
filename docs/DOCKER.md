# 🐳 Panduan Lengkap Perintah Terminal Docker — Pesu Peluh

Dokumen ini berisi daftar perintah terminal Docker (*Cheat Sheet*) untuk server production **Pesu Peluh**. Anda tinggal menyalin (*copy-paste*) perintah yang dibutuhkan langsung ke terminal server.

---

## ⚡ 1. Command Performa & Optimasi Kencang (Wajib Setelah Update Code)

Jalankan perintah ini agar sistem berjalan dengan kecepatan maksimal (Cache Nginx, Route, Config & Autoload PHP):

```bash
# A. Reload Nginx (Mengaktifkan config Nginx & Gzip baru)
docker compose exec nginx nginx -s reload

# B. Optimasi Cache Framework Laravel (Config, Route, View)
docker compose exec php php artisan optimize

# C. Optimasi Autoload Class PHP (Super Fast Class Loader)
docker compose exec php composer dump-autoload --optimize --classmap-authoritative

# D. Sekali Jalan (All-in-One Optimization Command)
docker compose exec php php artisan optimize && docker compose exec php composer dump-autoload --optimize
```

---

## 🚀 2. Command Deploy & Build Pertama Kali / Update Proyek

```bash
# Build dan jalankan seluruh container di background
docker compose up -d --build

# Cek status semua container (Pastikan berstatus 'Up')
docker compose ps
```

---

## 🔄 3. Command Restart Container

Gunakan jika Anda mengubah file `.env`, `pesupeluh.conf`, atau servis tertentu perlu direstart:

```bash
# Restart Nginx saja (Tanpa matikan container lain)
docker compose restart nginx

# Restart PHP-FPM saja
docker compose restart php

# Restart Reverb WebSocket
docker compose restart reverb

# Restart Queue Worker Notifikasi
docker compose restart queue-worker

# Restart WA Gateway
docker compose restart wa-gateway

# Restart SELURUH Container Proyek
docker compose restart
```

---

## 📜 4. Command Cek Log Real-Time (Monitoring System)

Gunakan jika ingin melihat aktivitas atau melacak error di server:

```bash
# Cek log Nginx (Traffic web & error server)
docker compose logs -f nginx

# Cek log PHP (Error Laravel)
docker compose logs -f php

# Cek log Reverb WebSocket (Koneksi realtime)
docker compose logs -f reverb

# Cek log Queue Worker (Status notifikasi terkirim)
docker compose logs -f queue-worker

# Cek log WA Gateway
docker compose logs -f wa-gateway

# Cek log SELURUH Container sekaligus
docker compose logs -f --tail=100
```

---

## 🛠️ 5. Command Maintenance & Perintah Artisan / Frontend

Gunakan jika perlu menjalankan perintah Laravel Artisan atau NPM di dalam container server:

```bash
# Build / Compile Asset Frontend (Vite / NPM Build via Docker)
docker compose exec php npm run build

# Jalankan Migrasi Database
docker compose exec php php artisan migrate --force

# Hapus Semua Cache Laravel
docker compose exec php php artisan optimize:clear

# Hubungkan Storage Public Link (Jika gambar storage tidak muncul)
docker compose exec php php artisan storage:link

# Masuk ke dalam Shell Terminal Container PHP (Interactive Bash)
docker compose exec php bash
```

---

## ⏹️ 6. Command Menghentikan Proyek

```bash
# Matikan seluruh container (Data tetap aman)
docker compose down

# Matikan seluruh container + hapus volume temporary
docker compose down -v
```

---

## 🧹 7. Command Bersih-Bersih Disk Server (Docker Cleanup)

Jika penyimpanan server terasa penuh karena sisa image lama:

```bash
# Hapus image/container bekas yang tidak terpakai
docker system prune -f
```
