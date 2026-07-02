# Analisis Performa Arsitektur Genesys & Rencana Implementasi di Pesupeluh (Laravel)

Dokumen ini menganalisis konsep arsitektur yang membuat portal **Genesys** berjalan sangat cepat, ringan, dan instan, serta menyusun rencana langkah demi langkah untuk mengadopsi teknik tersebut pada proyek **Pesupeluh (Laravel)**.

---

## 1. Mengapa Genesys Sangat Cepat? (Bedah Konsep)

Genesys menggabungkan backend **CodeIgniter 4 (CI4)** dengan frontend **Vue 3 (Vite)** melalui pendekatan **Hybrid Single Page Application (SPA)**. Tiga pilar utama performanya adalah:

### A. Rendering Halaman Tanpa Reload (Hybrid SPA)
Pada aplikasi web tradisional (seperti Laravel Blade biasa), setiap klik menu memicu pemuatan ulang halaman secara penuh (*full page reload*). Browser akan menghapus DOM saat ini, menampilkan layar putih kosong (freeze), mengunduh HTML baru dari server, lalu mengunduh ulang semua aset (CSS, JS, gambar).

**Cara Genesys Mengatasinya:**
1. **Vue Router Client-Side Navigation:** Halaman utama diatur secara lokal. Pindah antarhalaman Vue tidak memerlukan request HTML ke server.
2. **Taktik `LegacyView.vue` untuk Halaman Server-Side:** Untuk halaman yang masih di-render oleh CI4, Genesys tidak melakukan reload browser. Sistem mengirimkan request asinkron (AJAX Fetch) dengan header khusus `X-SPA-Fetch: true`.
3. **Pemuatan Parsial:** Di backend, file layout (`layouts/main.php`) membaca header tersebut dan **hanya merender konten bagian `<main>`**. Layout utama, sidebar, stylesheet, dan script tidak dikirim ulang. Komponen Vue kemudian menyuntikkan konten parsial ini ke DOM secara dinamis.

### B. Pembebasan Kunci Session Lebih Awal (*Session Unlocking*)
Masalah performa yang sering tidak disadari pada localhost (Windows/Laragon) adalah penundaan antrean request (*request serialization*).

**Cara Genesys Mengatasinya:**
Di dalam `BaseController.php` pada request `GET`, sistem langsung memanggil:
```php
if ($this->request->getMethod() === 'get' && session_id()) {
    session_write_close();
}
```
PHP mengunci file session secara default saat dibaca. Jika satu halaman mengirimkan 3-5 request AJAX sekaligus (misal untuk data notifikasi, berita, dan grafik), PHP akan memprosesnya satu demi satu secara bergantian (mengantre) karena menunggu kunci session dilepas. Dengan memanggil `session_write_close()`, session langsung dilepas setelah dibaca, memungkinkan server memproses seluruh request secara **paralel (concurrent)**.

### C. Deferred Data Loading (Lazy Loading)
Di `Dashboard.php`, Genesys tidak melakukan query database berat saat render pertama.
* Server langsung mengembalikan kerangka halaman secepat mungkin (non-blocking).
* Komponen frontend Vue kemudian memicu request AJAX mandiri secara asinkron setelah halaman tampil untuk memuat data-data sekunder (seperti *Guides*, *News*, dan *Approvals*).

---

## 2. Rencana Implementasi pada Proyek Pesupeluh (Laravel)

Untuk membuat aplikasi **Pesupeluh** Anda berjalan secepat dan seringan Genesys, berikut adalah rekomendasi arsitektur yang dapat diterapkan di Laravel:

### Opsi A: Menggunakan Inertia.js (Rekomendasi Utama - Pendekatan Paling Mirip Genesys)
Inertia.js memungkinkan Anda membuat aplikasi SPA menggunakan Vue 3 atau React di frontend, namun tetap menggunakan routing dan controller bawaan Laravel tanpa perlu membangun API terpisah.

* **Cara Kerja:**
  * Saat user mengklik link, Inertia mengintersep klik tersebut dan mengirim request AJAX.
  * Laravel mendeteksi request Inertia dan mengembalikan data JSON (bukan HTML utuh).
  * Vue secara dinamis mengganti komponen halaman tanpa reload browser.
* **Langkah Implementasi:**
  1. Install Inertia server-side: `composer require inertiajs/inertia-laravel`.
  2. Buat root template (biasanya `app.blade.php`).
  3. Install Inertia client-side: `npm install @inertiajs/vue3 vue`.
  4. Sesuaikan controller untuk me-return `Inertia::render('NamaKomponen', $data)` alih-alih `view('nama_blade')`.

### Opsi B: Menggunakan Laravel Livewire dengan `wire:navigate` (Pendekatan Tercepat & Paling Praktis)
Jika Anda tidak ingin beralih menulis kode dalam format Vue dan ingin tetap menggunakan Blade, Livewire 3 memiliki fitur bawaan bernama `wire:navigate`.

* **Cara Kerja:**
  * Saat Anda menambahkan direktif `wire:navigate` pada tag `<a>`, Livewire akan mengambil halaman berikutnya di latar belakang via AJAX.
  * Livewire kemudian mengganti konten bagian `<body>` secara instan dan memperbarui URL tanpa me-reload aset script & CSS.
* **Langkah Implementasi:**
  1. Pastikan project menggunakan Livewire 3.
  2. Ubah tag link menu Anda menjadi:
     ```html
     <a href="/dashboard" wire:navigate>Dashboard</a>
     ```
  3. Sistem secara otomatis berubah menjadi SPA-like experience secara instan.

### Opsi C: Optimasi Concurrency Session di Laravel
Jika aplikasi Pesupeluh Anda sering melakukan pemanggilan AJAX secara bersamaan dan terasa ada delay beruntun, Anda dapat mengonfigurasi penanganan session di controller Laravel.

* **Langkah Implementasi:**
  Pada method Controller Laravel yang hanya membaca data (read-only / request GET) dan sering diakses via AJAX:
  ```php
  use Illuminate\Support\Facades\Session;

  public function getDashboardData()
  {
      // Ambil data session yang dibutuhkan
      $userId = Session::get('user_id');

      // Simpan session dan bebaskan lock-nya agar request lain tidak terblokir
      Session::save(); 

      // Lakukan query database dan return response
      $data = ...;
      return response()->json($data);
  }
  ```

### Opsi D: Lazy Loading Komponen Berat
Gunakan Livewire Lazy Loading atau AJAX loading di Laravel Blade:
* Tampilkan kerangka komponen card dashboard terlebih dahulu dengan *shimmer loading placeholder*.
* Load data berat melalui request AJAX terpisah atau gunakan fitur `lazy` pada Livewire 3:
  ```html
  <livewire:chart-analytics lazy />
  ```

---

## 3. Kesimpulan

| Aspek | Kondisi Saat Ini (Laravel Lambat) | Pendekatan Solusi (Seperti Genesys) |
| :--- | :--- | :--- |
| **Navigasi** | Full page refresh (aset dimuat ulang, browser berkedip/freeze). | SPA / Client-side routing (**Inertia.js** atau **Livewire `wire:navigate`**). |
| **Proses Request** | Request sinkron (menunggu semua query DB selesai sebelum kirim HTML). | Asinkron / Lazy loading (load halaman dulu, isi data via AJAX kemudian). |
| **Session Handling** | File session terkunci sepanjang proses eksekusi PHP (antrean request). | Pembebasan session lock lebih awal (`session_write_close` / `Session::save()`). |
| **Aset Bundler** | Menggunakan Webpack lama (lambat). | Menggunakan **Vite** untuk kompilasi super cepat di development & production. |
