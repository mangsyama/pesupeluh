# DOKUMENTASI SISTEM BROADCAST & REAL-TIME (LARAVEL REVERB)

Aplikasi **Pesu Peluh** menggunakan sistem penyiaran pesan (*event broadcasting*) berbasis WebSocket lokal menggunakan **Laravel Reverb** sebagai pengganti Pusher Cloud. Ini memungkinkan pengiriman notifikasi real-time tanpa batas kuota dan tanpa biaya langganan tambahan.

---

## 1. Arsitektur Alur Kerja Real-Time

Alur pengiriman notifikasi/event dari server ke client berjalan melalui tahapan berikut:

```
[Trigger Event / Notification] (Laravel PHP)
             │
             ▼
      [Database Queue] (Tabel `jobs`)
             │
      (Proses Async oleh `php artisan queue:work`)
             ▼
    [Laravel Reverb Server] (WebSockets Port 8080)
             │
             ▼ (Proses push real-time)
  [Vue Client (Laravel Echo)] (Port browser client)
```

1. **Triggering**: Event PHP terpicu (contoh: Pendaftaran User Baru memicu `NewUserRegisteredNotification` yang implements `ShouldQueue`).
2. **Queuing**: Job broadcast dimasukkan ke tabel `jobs` database.
3. **Queue Processing**: Queue worker mengambil job dan menghubungi local Reverb server.
4. **WebSocket Push**: Reverb server mem-push pesan WebSocket ke browser client yang sedang terkoneksi dan ter-subscribe ke private channel `App.Models.User.{id}`.
5. **Client Rendering**: Vue client menangkap event via `laravel-echo` dan memperbarui state notifikasi secara real-time.

---

## 2. Cara Menjalankan untuk Pengembangan Lokal (Development)

Untuk menjalankan fitur real-time di komputer lokal Anda, pastikan Anda menjalankan 4 terminal berikut secara bersamaan:

1. **PHP Web Server**:
   ```bash
   php artisan serve
   ```
2. **Frontend Assets compiler (Vite)**:
   ```bash
   npm run dev
   ```
3. **Queue Worker** (Wajib):
   ```bash
   php artisan queue:work --tries=3 --timeout=30
   ```
4. **Laravel Reverb Server** (Wajib):
   ```bash
   php artisan reverb:start
   ```

---

## 3. Cara Menjalankan untuk Hosting / Production (Windows Server)

Di server Windows Server production, kedua proses backend (`queue:work` dan `reverb:start`) harus berjalan di background secara otomatis 24/7. Anda bisa menggunakan **PM2** (Process Manager) agar kedua script berjalan otomatis saat OS menyala:

Jalankan perintah ini sekali saja di Command Prompt (Administrator) di server Anda:

```cmd
# 1. Masuk ke folder project
cd C:\project\pesupeluh

# 2. Daftarkan Laravel Reverb Server
pm2 start artisan --name "pesupeluh-reverb" --interpreter php -- reverb:start

# 3. Daftarkan Queue Worker
pm2 start artisan --name "pesupeluh-worker" --interpreter php -- queue:work

# 4. Simpan process list PM2 agar auto-boot saat Windows restart
pm2 save
```

---

## 4. Cara Menjalankan untuk Hosting / Production (Linux Server VPS/Nginx)

Jika Anda menggunakan VPS Linux (seperti Ubuntu) dengan Nginx, gunakan **Supervisor** sebagai process manager:

1. Buat file config Supervisor:
   ```bash
   sudo nano /etc/supervisor/conf.d/pesupeluh.conf
   ```
2. Masukkan konfigurasi berikut:
   ```ini
   [program:pesupeluh-reverb]
   command=php /var/www/pesupeluh/artisan reverb:start
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_filepath=/var/www/pesupeluh/storage/logs/reverb.log

   [program:pesupeluh-worker]
   command=php /var/www/pesupeluh/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_filepath=/var/www/pesupeluh/storage/logs/worker.log
   ```
3. Reload & Aktifkan:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start all
   ```
