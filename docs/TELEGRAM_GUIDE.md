# Panduan Setup Notifikasi Telegram Bot - Pesu Peluh

Dokumen ini berisi panduan langkah-demi-langkah untuk membuat Telegram Bot mandiri dan mengonfigurasi variabel `.env` agar sistem dapat mengirimkan notifikasi persetujuan pendaftaran pengguna baru kepada Super Admin.

---

## Langkah 1: Membuat Telegram Bot Baru

Anda perlu mendaftarkan bot baru di Telegram untuk mendapatkan **Bot Token**:

1. Buka aplikasi Telegram, lalu cari **@BotFather** (pastikan akun memiliki logo centang biru verifikasi).
2. Kirim pesan `/newbot` ke BotFather.
3. Masukkan **Nama Bot** Anda (bebas, contoh: `Pesu Peluh Notif Bot`).
4. Masukkan **Username Bot** Anda (harus unik dan diakhiri dengan kata `bot`, contoh: `pesupeluh_notif_bot`).
5. BotFather akan mengirimkan pesan balasan berisi token HTTP API. Salin token tersebut.
   * *Contoh token:* `123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ`

---

## Langkah 2: Mendapatkan Chat ID Pribadi Penerima Notifikasi

Karena notifikasi dikirim langsung secara personal (pribadi) ke masing-masing pengguna, setiap admin/teknisi/kepala ruangan perlu mendapatkan Chat ID pribadi mereka:

1. Buka Telegram dan cari **@userinfobot**.
2. Kirim pesan `/start` ke bot tersebut, maka ia akan membalas dengan ID akun pribadi Anda (contoh: `987654321`).
3. Salin angka ID tersebut.
4. **PENTING:** Anda harus mencari **username bot mandiri** Anda sendiri di Telegram (yang dibuat di Langkah 1), lalu klik **Start** agar bot tersebut memiliki izin untuk mengirimkan pesan ke akun Anda secara pribadi.

---

## Langkah 3: Konfigurasi File `.env` (Hanya Token Bot)

Buka file [`.env`](file:///c:/project/pesupeluh/.env) pada root project aplikasi Pesu Peluh, lalu tempelkan nilai token bot Anda pada bagian paling bawah:

```env
# Konfigurasi Telegram Bot
TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
```

---

## Langkah 4: Masukkan Chat ID ke Profil Pengguna di Aplikasi

Agar notifikasi masuk secara personal:
1. Login sebagai **Administrator** di aplikasi Pesu Peluh.
2. Buka menu **Daftar Pengguna** (User Management).
3. Klik tombol **Edit** pada akun pengguna yang ingin dikirimi notifikasi Telegram.
4. Isi kolom **Telegram Chat ID (Personal)** dengan angka Chat ID yang didapatkan pada Langkah 2 (contoh: `987654321`).
5. Klik **Simpan**.

Sekarang, setiap kali ada pendaftaran pengguna baru, sistem akan otomatis mengirimkan notifikasi pribadi hanya kepada para Administrator yang telah mengisi kolom Telegram Chat ID-nya!
Ini juga berfungsi sebagai pondasi untuk mengirimkan notifikasi penugasan ke Teknisi secara personal di masa depan.
