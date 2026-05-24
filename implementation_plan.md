# Implementation Plan - PackGuard AI (Packing Video Recorder)

This plan details the steps to initialize, configure, and build **PackGuard AI** on a local WSL environment using Laravel 13, PHP 8.5, SQLite, and vanilla Javascript with Tailwind CSS.

---

## User Review Required

> [!IMPORTANT]
> **Single-Page Application (SPA) Approach for Webcam Persistence**
> To prevent the browser from asking for webcam permissions repeatedly and to avoid stream disconnection, the application will be built as a single-page interface with client-side tab switching (Packing Station, Admin Logs, and Settings).

> [!WARNING]
> **HTTPS/Local Security Constraint**
> Browsers restrict camera access (`getUserMedia`) to secure contexts (`https://` or `localhost`). Since this is a local server, when accessed from other PCs in the local network, you will need to either configure HTTPS (Self-Signed) or enable the `unsafely-treat-insecure-origin-as-secure` flag in Chrome/Edge on the client machines.

> [!TIP]
> **Simulasi Barcode Scanner (Pengujian Tanpa Hardware)**
> Karena Anda belum memiliki scanner fisik, kita akan membuat fitur **Virtual Scanner Simulator** langsung di halaman Packing Station:
> 1. **Manual Input Field:** Kolom input tersembunyi/opsional untuk mengetik/paste nomor resi dan mengklik tombol "Simulate Scan".
> 2. **Auto-Simulation Script:** Tombol demo yang ketika diklik akan menembakkan rentetan key event dengan jeda < 10ms secara otomatis, meniru persis kecepatan hardware barcode scanner agar algoritma *Time-Interval Buffer* kita tetap bisa diuji.


---

## Proposed Changes

We will initialize a new Laravel 13 project directly in the workspace folder. Since the directory currently contains `PACKGUARD_PRD.md`, we will create the Laravel structure around it.

### Phase 1: Project Initialization

#### [NEW] Laravel 13 Workspace
- Initialize Laravel 13 using Composer: `composer create-project laravel/laravel .` (temporarily moving the PRD file if needed, then restoring it).
- Configure `.env` to use **SQLite** as the database (default in modern Laravel).
- Run basic migrations to verify the setup.

### Phase 2: Database Schema & Backend

#### [NEW] Database Migration for `packing_logs`
Create a migration `create_packing_logs_table` with the following columns:
- `id` (Primary Key)
- `order_id` (String, indexed)
- `file_name` (String)
- `file_path` (String)
- `file_size` (Integer, nullable)
- `duration_seconds` (Integer, nullable)
- `staff_name` (String, nullable)
- `timestamps` (`created_at` and `updated_at`)

#### [NEW] `PackingLog` Eloquent Model
- Set up mass-assignable attributes (`fillable`).
- Define utility scopes (e.g., search by `order_id`).

#### [NEW] `PackingLogController`
Implement the following endpoint methods:
- `index(Request $request)`: Retrieve log items with pagination and filters (search `order_id`).
- `store(Request $request)`: Validate incoming file upload (WebM/MP4, max 20MB) and log metadata, save video to `storage/app/public/videos`, and create a database entry.
- `destroy($id)`: Remove database entry and delete the corresponding file from disk.
- `cleanup(Request $request)`: Automatically delete logs and video files older than 30 days.

#### [MODIFY] `routes/web.php`
Define Web routes for:
- Main dashboard interface: `/`
- Log listing & search API: `/api/logs`
- Log store (upload) API: `/api/logs/upload`
- Delete log API: `/api/logs/{id}`
- Storage cleanup API: `/api/logs/cleanup`

---

### Phase 3: Frontend Interface (Vibrant Dark Mode Theme)

We will use Tailwind CSS with a modern, dark-mode, glassmorphic aesthetic (glowing borders, dark slate cards, smooth transitions).

#### [MODIFY] [resources/views/dashboard.blade.php](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\resources\views\dashboard.blade.php)
Create a single Blade view containing:
- **Sidebar / Header Navigation**: Quick switching between "Packing Station", "Video Logs", and "Settings" without page refresh.
- **Tab 1: Packing Station**:
  - Live Webcam Preview: Container with glowing status borders (Green for IDLE, pulsing Red for RECORDING).
  - Overlay Visualizer: Renders active Resi ID, time, and logo directly onto the canvas.
  - Manual controls for testing (Start, Stop, manual Resi ID input).
  - Status panel showing system state and latest packed items.
- **Tab 2: Video Logs**:
  - Stat cards (Total logs, space consumed, average duration).
  - Search box with instantaneous keyup filter.
  - Table of logs showing details, size, and buttons to view or download.
  - Built-in video player modal for inline playback.
- **Tab 3: Settings**:
  - Storage retention settings (e.g., auto-clean after X days).
  - Camera device selector dropdown (utilizing `navigator.mediaDevices.enumerateDevices`).

#### [NEW] [resources/js/app.js](file:///\\wsl.localhost\Ubuntu\data\labs\pack-guard\resources\js\app.js)
Vanilla JS module containing:
1. **Global Keyboard Listener with Time-Interval Buffer**:
   - Measures time between keystrokes (`keydown` event).
   - If interval is consistently `< 30ms`, compiles characters as a scanned barcode.
   - If interval is `> 30ms`, ignores inputs (manual keyboard input).
   - Triggers `startRecording(orderId)` or `stopRecording()` based on inputs.
2. **Camera Streaming & Canvas Compositing**:
   - Accesses selected camera stream.
   - Uses a hidden/visible `<canvas>` rendering loop using `requestAnimationFrame`.
   - Draws camera stream frame-by-frame and overlays:
     - Text: `ORDER: [Resi ID]`
     - Timestamp: `YYYY-MM-DD HH:mm:ss`
     - Watermark logo.
3. **MediaRecorder Logic**:
   - Capture stream directly from the canvas: `canvas.captureStream(24)`.
   - Record with MIME type `video/webm;codecs=vp9` or fallbacks.
   - Collect data chunks. On stop, create a `Blob` and send it via `Axios` to `/api/logs/upload`.
4. **State Management**:
   - Manage tabs visibility, database queries, and modal control.

---

## Verification Plan

### Automated / Browser Tests
1. **Unit & Feature Tests**:
   - Write standard Laravel tests (`tests/Feature/PackingLogTest.php`) verifying `/api/logs/upload` endpoint, storage saves, DB writes, search validation, and auto-cleanup.
   - Run via `wsl php artisan test`.
2. **Manual Hardware Emulation**:
   - Emulate scanner input using keyboard inputs to ensure scanner buffer rejects manual typing and successfully captures fast inputs.
   - Record webcam streams and verify that the output video files:
     - Contain the watermark/text overlays.
     - Are playable directly inside the log section.
     - Fall within the 10-15MB budget size.
