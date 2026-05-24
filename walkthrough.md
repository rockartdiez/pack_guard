# Walkthrough - PackGuard AI (Packing Video Recorder)

PackGuard AI has been successfully initialized and built on Laravel 13 & PHP 8.5 inside WSL Ubuntu.

---

## 🚀 Cara Menjalankan Aplikasi

Untuk menjalankan aplikasi di local machine Anda, buka 2 terminal di folder proyek (`/data/labs/pack-guard`):

### 1. Terminal 1: Backend Laravel Server
Jalankan dev server PHP:
```bash
wsl php artisan serve
```
Aplikasi akan tersedia di: **`http://127.0.0.1:8000`**

### 2. Terminal 2: Frontend Asset Server (Vite)
Jalankan Vite server untuk reload otomatis jika ada perubahan asset:
```bash
wsl npm run dev
```

---

## 🛠️ Fitur yang Telah Diimplementasikan

### 1. Project Initialization & Database (Phase 1 & 2)
- Diinisialisasi menggunakan **Laravel 13** dan dikonfigurasi untuk menggunakan **SQLite** (`database/database.sqlite`) secara otomatis.
- Migrasi tabel `packing_logs` berhasil dibuat dan dimigrasikan untuk mencatat log packing secara lengkap (Order ID, nama file, ukuran file, durasi, staf, dll.).

### 2. Backend API & Control Logic (Phase 3)
- [PackingLogController.php](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\app\Http/Controllers/PackingLogController.php): Berisi logika backend lengkap:
  - `dashboard()`: Memuat statistik utama (Total packing, space terpakai, rata-rata durasi).
  - `index()`: Menyajikan log pencarian real-time via AJAX dengan pagination.
  - `store()`: Menangani upload file video dari browser dan menyimpan metadatanya.
  - `destroy()`: Menghapus data log di DB sekaligus file fisiknya di disk storage.
  - `cleanup()`: Menghapus otomatis video & log lama berbasis retensi hari (default 30 hari).
- [routes/web.php](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\routes\web.php): Terdaftar rute HTTP & API lengkap untuk dashboard dan fungsi log.

### 3. High-Fidelity UI/UX (Phase 4)
- [dashboard.blade.php](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\resources\views\dashboard.blade.php): Menggunakan desain Dark Mode premium (Tailwind CSS v4) dengan struktur Single-Page App (SPA):
  - **Packing Station Tab**: Pratinjau kamera webcam, visualizer overlay, indikator status, tombol kontrol, dan daftar sesi terbaru.
  - **Video Logs Tab**: Berisi pencarian resi instan, metrik log, tabel dinamis, opsi download, dan hapus log.
  - **Settings Tab**: Konfigurasi pilihan hardware kamera, operator, dan hari retensi pembersihan.
  - **Integrated Video Player**: Modal overlay pop-up untuk memutar video rekaman secara langsung tanpa perlu download.

### 4. Smart Recorder & Barcode Logic (Phase 5)
- [app.js](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\resources\js\app.js): Logika frontend vanilla JS:
  - **Webcam & Canvas Compositing**: Mengambil stream webcam dan menggabungkannya dengan watermark teks (Order ID, Waktu Live, Operator) sebelum direkam.
  - **MediaRecorder API**: Merekam hasil komposisi canvas ke dalam WebM file, lalu mengunggahnya secara asinkron (multipart data) ke Laravel.
  - **Global Keypress Barcode Buffer**: Mendeteksi input barcode scanner global di browser dengan filter interval waktu (<30ms).
  - **Virtual Barcode Scanner Simulator**:
    - *Manual Input:* Input resi manual dan tombol "Scan" untuk simulasi.
    - *Hardware Emulator:* Meniru persis ketukan hardware berkecepatan tinggi (jeda 5ms antar karakter) untuk mempermudah testing bagi developer tanpa alat fisik.

---

## 🧪 Hasil Pengujian (Verification Plan)

Kami telah menulis unit dan feature tests di [PackingLogTest.php](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\tests\Feature\PackingLogTest.php) dan semuanya berhasil lulus pengujian (`PASS`):

```bash
Tests:    6 passed (24 assertions)
Duration: 0.26s
```

### Detail Pengujian:
1. **dashboard page loads**: Memastikan rute utama (`/`) memuat tampilan dashboard dengan benar.
2. **logs api lists logs**: Memastikan JSON log API berfungsi dan struktur data sesuai.
3. **video upload endpoint**: Memastikan API upload menerima file mockup video, menyimpan file ke public disk, dan mencatat data di database.
4. **log deletion**: Memastikan data log dan file video di storage benar-benar terhapus ketika aksi hapus dijalankan.
5. **retention cleanup**: Memverifikasi bahwa data packing yang berusia di atas batas retensi hari (misal 30 hari) berhasil dibersihkan otomatis sementara data baru tetap aman.
