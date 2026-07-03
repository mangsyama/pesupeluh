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

## Langkah 2: Mendapatkan Chat ID Penerima Notifikasi

Notifikasi dapat dikirimkan ke **Grup Telegram** (berisi semua Super Admin) atau ke **Chat Pribadi** Anda sendiri.

### Opsi A: Dikirim ke Grup Telegram (Direkomendasikan)
1. Buat grup baru di Telegram dan masukkan akun para super admin ke dalamnya.
2. Cari username bot Anda (yang dibuat di Langkah 1), lalu **tambahkan bot tersebut ke dalam grup** sebagai anggota.
3. Untuk mendapatkan Chat ID grup tersebut:
   * Cari bot pembantu bernama **@RawDataBot** di Telegram, lalu tambahkan ke grup Anda untuk sementara.
   * Setelah masuk grup, **@RawDataBot** akan langsung mengirimkan pesan berisi data JSON terperinci.
   * Cari bagian `"chat": { "id": -100xxxxxxxxxx, ... }`.
   * Salin angka ID tersebut (pastikan menyertakan tanda minus `-`, contoh: `-1002134567890`).
   * **Keluarkan kembali @RawDataBot** dari grup Anda setelah mendapatkan ID.

### Opsi B: Dikirim ke Chat Pribadi (Hanya untuk 1 Orang)
1. Buka Telegram dan cari **@userinfobot**.
2. Kirim pesan `/start` ke bot tersebut, maka ia akan membalas dengan ID akun pribadi Anda (contoh: `987654321`).
3. Salin angka ID tersebut.
4. **PENTING:** Anda harus mencari username bot mandiri Anda sendiri di Telegram, lalu klik **Start** agar bot tersebut memiliki izin untuk mengirimkan pesan ke akun Anda.

---

## Langkah 3: Konfigurasi File `.env`

Buka file [`.env`](file:///c:/project/pesupeluh/.env) pada root project aplikasi Pesu Peluh, lalu tempelkan nilai yang telah Anda dapatkan pada bagian paling bawah:

```env
# Konfigurasi Telegram Bot
TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
TELEGRAM_ADMIN_CHAT_ID="-1002134567890"
```

> [!IMPORTANT]  
> Jika menggunakan Grup Telegram, pastikan tanda minus (`-`) pada `TELEGRAM_ADMIN_CHAT_ID` tidak terhapus.

---

## Langkah 4: Uji Coba Pendaftaran
Setelah `.env` disimpan, silakan coba lakukan pendaftaran pengguna baru melalui halaman Register aplikasi. Bot Anda akan otomatis mengirimkan notifikasi beserta tombol link ke halaman persetujuan!
