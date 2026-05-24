# PRODUCT REQUIREMENT DOCUMENT (PRD)
## Aplikasi Perekam Proses Packing Paket (PackGuard AI)

---

### 1. Overview

*   **Nama Produk:** PackGuard AI (Packing Video Recorder System)
*   **Konteks & Masalah:** Seller e-commerce dengan volume order tinggi sering mengalami kerugian finansial akibat komplain palsu dari pembeli (misal: barang kurang atau rusak). Proses dokumentasi manual menggunakan HP sangat tidak efisien untuk 1–10 staf packing.
*   **Solusi:** Aplikasi berbasis web lokal (Self-hosted Local Server) menggunakan Laravel dan JavaScript Browser API untuk mengotomatisasi perekaman video packing menggunakan hardware USB Barcode Scanner dan Webcam.
*   **Tujuan Utama:** Menghilangkan intervensi tangan staf pada keyboard/mouse selama proses packing, menekan angka human error, dan menyediakan pencarian bukti video yang instan saat terjadi komplain di marketplace (Shopee, Tokopedia, TikTok Shop, Lazada).

---

### 2. Requirements

#### 2.1 Hardware Requirements (Sisi User/Staf)
*   **PC/Laptop Minimal:** OS Windows/Linux, RAM 8GB (untuk kelancaran render video browser).
*   **USB Barcode Scanner:** Mendukung Keyboard Wedge Mode (mengirim input barcode sebagai ketukan keyboard otomatis).
*   **USB Webcam:** Minimal resolusi 720p (1280x720) dengan sudut pandang lebar (wide angle) diarahkan ke meja packing.

#### 2.2 Software & Stack Requirements
*   **Backend Framework:** Laravel 11 / 12 / 13 (PHP 8.2+)
*   **Frontend:** Blade Templating, Tailwind CSS, Vanilla JavaScript (MediaRecorder API).
*   **Database:** SQLite (untuk instalasi lokal mandiri per PC) **ATAU** PostgreSQL/MySQL (jika menggunakan sistem terpusat 1 Server Lokal untuk 10 PC Staf).
*   **Process Manager (Opsional):** Supervisor (untuk menangani Laravel Queue jika ada proses kompresi/upload video ke cloud).

---

### 3. Core Features

#### A. Fitur Staf Gudang (Halaman Utama / Packing Station)
*   **Global Barcode Listener:** Sistem mendeteksi input dari barcode scanner secara otomatis tanpa mengharuskan kursor aktif di kotak teks (input field).
*   **Hands-Free Auto Recorder:** Kamera otomatis mulai merekam saat resi di-scan, dan otomatis berhenti/menyimpan saat resi baru berikutnya di-scan atau mendeteksi barcode perintah khusus (STOP_PACKING).
*   **Live Camera View & Status Indicator:** Menampilkan feed kamera secara real-time disertai indikator visual yang jelas (IDLE berwarna hijau, RECORDING berwarna merah berkedip).

#### B. Fitur Admin (Dashboard & Log Bukti)
*   **Instant Video Search Log:** Pencarian video instan berbasis Nomor Resi / Order ID.
*   **Integrated Video Player:** Memutar video bukti langsung di dalam aplikasi tanpa perlu mengunduhnya terlebih dahulu.
*   **Storage Management:** Fitur untuk menghapus otomatis video yang sudah lewat dari 30 hari untuk menghemat kapasitas harddisk.

---

### 4. User Flow

```text
[Staf dalam Posisi Idle]
          │
          ▼
[Scan Barcode Resi/Order ID]
          │
          ├──> (Sistem Validasi String Barcode)
          │
          ▼
[Otomatis Mulai Rekam (Webcam ON)] ──> Indikator Layar Berubah "RECORDING" (Merah)
          │
          ▼
[Staf Melakukan Proses Packing] (Tanpa menyentuh PC)
          │
          ▼
[Scan Barcode Baru / Barcode STOP]
          │
          ├──> 1. Stop Rekaman & Generate File Video (.mp4 / .webm)
          ├──> 2. Kirim (Upload) File ke Laravel Backend via Axios
          ├──> 3. Laravel Simpan File ke Storage & Catat ke Database Log
          │
          ▼
[Kembali ke Status IDLE / Mulai Resi Baru]
```

---

### 5. Architecture

Aplikasi ini menggunakan pendekatan **Hybrid Architecture**: Proses hardware-heavy dilakukan di client (browser), sedangkan manajemen data dan file dilakukan di server (Laravel).

*   **Frontend Layer (Client Browser):**
    *   JavaScript menangkap stream kamera menggunakan `navigator.mediaDevices.getUserMedia()`.
    *   **Canvas Compositing:** Stream kamera diproyeksikan ke elemen `<canvas>` untuk digabungkan dengan overlay teks (Nomor Resi) dan gambar (Logo Aplikasi) sebelum direkam.
    *   JavaScript mendengarkan ketikan barcode menggunakan event `window.addEventListener('keydown', ...)`.
    *   Rekaman diproses menggunakan MediaRecorder API (mengambil stream dari Canvas) dan ditampung dalam bentuk Blob data di memori browser.
*   **Transport Layer:**
    *   Begitu rekaman selesai, Blob video diubah menjadi objek File dan dikirim via HTTP POST Multipart Form Data menggunakan Axios ke endpoint API Laravel.
*   **Backend Layer (Laravel Server):**
    *   Controller menerima file video, memvalidasi nama resi, dan menyimpannya ke local disk storage.
    *   Eloquent ORM mencatat lokasi file, waktu mulai-selesai, dan data resi ke database.

---

### 6. Database Schema

Untuk performa tinggi dan pencarian instan, skema database dirancang sangat ramping.

**Tabel: `packing_logs`** (Mencatat setiap sesi aktivitas perekaman packing)

| Field Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | BigInteger | Primary Key, Auto Increment | ID Unik Log |
| `order_id` | String | Index, Not Null | Nomor Resi / Nomor Pesanan dari E-commerce |
| `file_name` | String | Not Null | Nama file video yang disimpan (ex: RESI123_17164828.mp4) |
| `file_path` | String | Not Null | Path lokasi file di dalam storage Laravel |
| `file_size` | Integer | Nullable | Ukuran file dalam bytes (untuk monitoring kapasitas disk) |
| `duration_seconds` | Integer | Nullable | Durasi packing dalam detik |
| `staff_name` | String | Nullable | Nama staf yang melakukan packing (jika multi-user) |
| `created_at` | Timestamp | Default CURRENT_TIMESTAMP | Waktu mulai proses packing |
| `updated_at` | Timestamp | Nullable | Waktu selesai/update data |

---

### 7. Design & Technical Constraints

#### 7.1 Browser Security (HTTPS Constraint)
*   **Kekangan:** Browser modern (Chrome/Edge) memblokir akses ke Webcam (`getUserMedia`) jika aplikasi berjalan di jaringan non-aman (HTTP biasa).
*   **Solusi:** Jika diakses secara lokal antar PC di gudang menggunakan IP (misal `http://192.168.1.5`), jalankan aplikasi menggunakan protokol HTTPS (Self-Signed Certificate) atau daftarkan IP tersebut ke dalam flag `unsafely-treat-insecure-origin-as-secure` di Google Chrome staf.

#### 7.2 Storage & Video Compression
*   **Kekangan:** Video berdurasi 1–2 menit dengan resolusi tinggi bisa memakan puluhan Megabyte. Jika ada 1.000 order per hari, harddisk akan cepat penuh.
*   **Solusi:** Konfigurasi resolusi video dibatasi pada 720p (1280x720) dengan 24 FPS, menggunakan video codec `video/webm;codecs=vp9` atau `video/mp4;codecs=h264` langsung dari browser. Target ukuran file maksimal adalah 10MB - 15MB per video.

#### 7.3 Barcode Scanner Input Buffer
*   **Kekangan:** Barcode scanner mengetik karakter dengan kecepatan sangat tinggi (di bawah 50ms). Jika staf tidak sengaja mengetik manual di keyboard, sistem bisa salah mendeteksi sebagai barcode.
*   **Solusi:** JavaScript penangkap barcode harus menggunakan metode **Time-Interval Buffer**. Jika rentang waktu antar ketukan karakter lebih dari 30ms, abaikan input tersebut karena dianggap ketikan manual manusia, bukan hasil scan hardware.

#### 7.4 Client-Side Rendering Overhead
*   **Kekangan:** Proses merging video stream dengan watermark menggunakan Canvas API secara real-time dapat membebani CPU/GPU pada komputer spesifikasi rendah.
*   **Solusi:** Implementasi harus menggunakan `requestAnimationFrame` yang efisien dan memastikan resolusi canvas tidak melebihi resolusi input kamera untuk menjaga performa tetap stabil di 24-30 FPS.
