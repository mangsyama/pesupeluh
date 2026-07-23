# Production Docker Deployment Guide — Pesu Peluh

Dokumen ini berisi panduan lengkap deployment aplikasi Pesu Peluh menggunakan Docker di **Windows Server**.

---

## 🛠️ Service Architecture (Port Mapping)

| Service Name | Container Name | Port Host (Server) | Keterangan |
| :--- | :--- | :--- | :--- |
| **Nginx** | `pesupeluh-nginx` | **`8081`** (-> 80) | Web Server Utama |
| **Reverb** | `pesupeluh-reverb` | **`8080`** | WebSocket Realtime Notification |
| **WA Gateway** | `pesupeluh-wa-gateway` | **`3000`** | WhatsApp Gateway Microservice |
| **PHP-FPM** | `pesupeluh-php` | `9000` (internal) | Backend PHP |
| **Redis** | `pesupeluh-redis` | Internal | Cache & Session Store |
| **Queue Worker** | `pesupeluh-queue-worker` | Internal | Asynchronous Job Worker |

---

## 🔍 Cara Cek Bentrok Port 3000 di Windows Server

Sebelum menjalankan Docker, Anda bisa mengecek apakah **Port 3000** (atau Port 8081 & 8080) sudah dipakai oleh aplikasi lain di Windows Server:

### Opsi A: Menggunakan PowerShell (Rekomendasi)
Buka PowerShell sebagai Administrator, lalu jalankan:
```powershell
Get-NetTCPConnection -LocalPort 3000 -ErrorAction SilentlyContinue
```
- Jika **kosong (tidak ada output)** &rarr; Port 3000 **100% AMAN** & bisa dipakai!
- Jika ada output LISTENING &rarr; Port 3000 sedang dipakai aplikasi lain.

### Opsi B: Menggunakan Command Prompt (CMD)
```cmd
netstat -ano | findstr :3000
```

> 💡 **Jika Port 3000 Bentrok dengan Aplikasi Lain:**
> Anda cukup mengubah port di `docker-compose.yml` (misal dari `"3000:3000"` menjadi `"3005:3000"`) dan sesuaikan `WA_LOCAL_URL=http://wa-gateway:3000/send` di file `.env`.

---

## 🚀 Langkah Deploy & Build di Windows Server

### 1. Salin Konfigurasi `.env`
Pastikan file `.env` di server memiliki pengaturan berikut:
```env
WA_GATEWAY_DRIVER=local
WA_LOCAL_URL=http://wa-gateway:3000/send
```

### 2. Jalankan Build & Container via Docker Compose
Buka Terminal/PowerShell di folder proyek:
```bash
docker compose up -d --build
```

### 3. Cek Status Container
```bash
docker compose ps
```
Pastikan `pesupeluh-nginx`, `pesupeluh-php`, `pesupeluh-reverb`, `pesupeluh-queue-worker`, dan `pesupeluh-wa-gateway` berstatus **`Up / Running`**.

---

## 📱 Cara Menghubungkan WhatsApp di Production

1. Buka aplikasi web di browser: `http://<IP-SERVER>:8081/wa-gateway`
2. Login sebagai **Admin**.
3. Scan **QR Code** yang tampil di halaman tersebut menggunakan HP WhatsApp kantor.
4. Selesai! WhatsApp otomatis terhubung dan siap mengirimkan notifikasi 24/7.
