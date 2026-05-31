// PackGuard AI - Core Javascript Logic

// State Variables
let webcamStream = null;
let mediaRecorder = null;
let recordedChunks = [];
let isRecording = false;
let currentOrderId = '';
let recordingStartTime = 0;
let timerInterval = null;
let selectedCameraId = localStorage.getItem('packguard_camera_id') || '';
let staffName = localStorage.getItem('packguard_staff_name') || 'Default Staff';

// Canvas Elements
let canvas = null;
let ctx = null;
let canvasWidth = 1280;
let canvasHeight = 720;
let animationFrameId = null;

// Barcode Keyboard Wedge Buffer
let barcodeBuffer = '';
let lastKeyPressTime = 0;
const BARCODE_INTERVAL_THRESHOLD = 100; // ms

// DOM Elements
let webcamElement = null;
let canvasElement = null;
let cameraStatusIndicator = null;
let screenStatusIndicator = null;
let recordingTimer = null;
let recordingTimerText = null;
let overlayOrderId = null;
let overlayTimestamp = null;
let logsTableBody = null;
let logsPagination = null;
let recentPackingLogsBody = null;
let logSearchInput = null;
let cameraSelect = null;
let simulatedResiInput = null;
let staffNameInput = null;
let quickStatsTotal = null;
let quickStatsToday = null;
let quickStatsSize = null;
let statsTotalLogs = null;
let statsTotalSize = null;
let statsAvgDuration = null;

// IndexedDB for offline backup
const DB_NAME = 'PackGuardDB';
const DB_VERSION = 1;
const STORE_NAME = 'video_backups';
let localDB = null;

async function initLocalDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onerror = (event) => reject('IndexedDB error: ' + event.target.error);
        request.onsuccess = (event) => {
            localDB = event.target.result;
            updatePendingUploadsUI();
            resolve();
        };
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
        };
    });
}

// CSRF Token for HTTP Requests
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// Initialize Everything on Load
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initDOMElements();
    setupGlobalBarcodeListener();
    initCamera();
    initLocalDB();
    
    // Load staff name from storage
    if (staffNameInput) {
        staffNameInput.value = staffName;
    }
});

function initTheme() {
    const theme = localStorage.getItem('packguard_theme') || 'dark';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    
    // Highlight active theme button if they exist
    const btnDark = document.getElementById('theme-dark-btn');
    const btnLight = document.getElementById('theme-light-btn');
    if (btnDark && btnLight) {
        if (theme === 'dark') {
            btnDark.classList.add('pg-btn-active');
            btnDark.classList.remove('pg-btn-primary');
            btnLight.classList.add('pg-btn-primary');
            btnLight.classList.remove('pg-btn-active');
        } else {
            btnLight.classList.add('pg-btn-active');
            btnLight.classList.remove('pg-btn-primary');
            btnDark.classList.add('pg-btn-primary');
            btnDark.classList.remove('pg-btn-active');
        }
    }
}

window.toggleTheme = function(theme) {
    localStorage.setItem('packguard_theme', theme);
    initTheme();
};

function initDOMElements() {
    webcamElement = document.getElementById('webcam');
    canvasElement = document.getElementById('recorderCanvas');
    cameraStatusIndicator = document.getElementById('cameraStatusIndicator');
    screenStatusIndicator = document.getElementById('screenStatusIndicator');
    recordingTimer = document.getElementById('recordingTimer');
    recordingTimerText = document.getElementById('recordingTimerText');
    overlayOrderId = document.getElementById('overlayOrderId');
    overlayTimestamp = document.getElementById('overlayTimestamp');
    logsTableBody = document.getElementById('logsTableBody');
    logsPagination = document.getElementById('logsPagination');
    recentPackingLogsBody = document.getElementById('recentPackingLogsBody');
    logSearchInput = document.getElementById('logSearchInput');
    cameraSelect = document.getElementById('cameraSelect');
    simulatedResiInput = document.getElementById('simulatedResiInput');
    staffNameInput = document.getElementById('staffNameInput');
    quickStatsTotal = document.getElementById('quickStatsTotal');
    quickStatsToday = document.getElementById('quickStatsToday');
    quickStatsSize = document.getElementById('quickStatsSize');
    statsTotalLogs = document.getElementById('statsTotalLogs');
    statsTotalSize = document.getElementById('statsTotalSize');
    statsAvgDuration = document.getElementById('statsAvgDuration');

    // Configure Canvas Context
    if (canvasElement) {
        canvas = canvasElement;
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
        ctx = canvas.getContext('2d');
    }
}

// ================= CAMERA & CANVAS RENDERING LOOP =================

async function initCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        updateCameraIndicator(false, 'Media API Not Supported');
        return;
    }

    try {
        // Enumerate video devices
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        
        // Populate selection dropdown
        if (cameraSelect) {
            cameraSelect.innerHTML = '';
            if (videoDevices.length === 0) {
                cameraSelect.innerHTML = '<option value="">No cameras detected</option>';
            } else {
                videoDevices.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || `Camera ${cameraSelect.options.length + 1}`;
                    if (selectedCameraId === device.deviceId) {
                        option.selected = true;
                    }
                    cameraSelect.appendChild(option);
                });
                
                // Set default if none selected
                if (!selectedCameraId && videoDevices.length > 0) {
                    selectedCameraId = videoDevices[0].deviceId;
                    localStorage.setItem('packguard_camera_id', selectedCameraId);
                }
            }
        }

        // Stop current stream if running
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
        }

        // Configure constraints
        const constraints = {
            audio: false, // We only need video as per PRD
            video: {
                deviceId: selectedCameraId ? { exact: selectedCameraId } : undefined,
                width: { ideal: canvasWidth },
                height: { ideal: canvasHeight }
            }
        };

        webcamStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        if (webcamElement) {
            webcamElement.srcObject = webcamStream;
            webcamElement.play().catch(e => console.warn("Webcam play interrupted:", e));
            // When video is ready, start rendering loop
            webcamElement.onloadedmetadata = () => {
                const settings = webcamStream.getVideoTracks()[0].getSettings();
                document.getElementById('cameraResolutionLabel').innerText = `Resolution: ${settings.width}x${settings.height} @ ${Math.round(settings.frameRate || 24)}fps`;
                updateCameraIndicator(true, 'Camera Connected');
                startCanvasLoop();
            };
        }

    } catch (err) {
        console.error('Camera connection failed:', err);
        updateCameraIndicator(false, 'Camera Error: ' + err.name);
    }
}

function updateCameraIndicator(connected, message) {
    if (!cameraStatusIndicator) return;
    
    if (connected) {
        cameraStatusIndicator.className = 'pg-badge-emerald flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-semibold';
        cameraStatusIndicator.querySelector('span').className = 'h-2 w-2 rounded-full bg-emerald-500';
    } else {
        cameraStatusIndicator.className = 'pg-badge-red flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-semibold';
        cameraStatusIndicator.querySelector('span').className = 'h-2 w-2 rounded-full bg-red-500 animate-pulse';
    }
    cameraStatusIndicator.querySelector('span').nextElementSibling.innerText = message;
}

function startCanvasLoop() {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    
    function draw() {
        if (!webcamElement || webcamElement.paused || webcamElement.ended) {
            animationFrameId = requestAnimationFrame(draw);
            return;
        }

        // 1. Draw raw video feed onto Canvas
        ctx.drawImage(webcamElement, 0, 0, canvasWidth, canvasHeight);

        // 2. Draw watermark overlays
        const now = new Date();
        const dateStr = now.toISOString().replace('T', ' ').substring(0, 19);

        // Overlay text panel background (Top-Left Position)
        ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
        ctx.fillRect(30, 20, 500, 130);
        ctx.strokeStyle = 'rgba(99, 102, 241, 0.6)';
        ctx.lineWidth = 3;
        ctx.strokeRect(30, 20, 500, 130);

        // Render Watermark Texts (Larger Fonts)
        ctx.font = 'bold 20px "Plus Jakarta Sans", sans-serif';
        ctx.fillStyle = '#818cf8'; // Indigo-400
        ctx.fillText('PACKGUARD AI - SECURITY PROOF', 50, 50);

        ctx.font = 'bold 18px "Courier New", monospace';
        ctx.fillStyle = '#ffffff'; // White for better contrast
        ctx.fillText(`ORDER ID  : ${currentOrderId || 'IDLE (NO SCAN)'}`, 50, 80);
        
        ctx.font = '16px "Courier New", monospace';
        ctx.fillStyle = '#e2e8f0'; // Slate-200
        ctx.fillText(`TIMESTAMP : ${dateStr}`, 50, 105);
        ctx.fillText(`OPERATOR  : ${staffName.toUpperCase()}`, 50, 130);

        // Render pulsing red recording indicator overlay on canvas when recording
        if (isRecording) {
            const pulseRate = Date.now() % 2000;
            const opacity = pulseRate < 1000 ? (pulseRate / 1000) : (2 - (pulseRate / 1000));
            
            ctx.fillStyle = `rgba(239, 68, 68, ${0.2 + opacity * 0.6})`;
            ctx.beginPath();
            ctx.arc(canvasWidth - 40, 40, 8, 0, 2 * Math.PI);
            ctx.fill();
            
            ctx.font = 'bold 11px "Plus Jakarta Sans", sans-serif';
            ctx.fillStyle = '#ef4444';
            ctx.fillText('• REC', canvasWidth - 85, 43);
        }

        // Keep loop going
        animationFrameId = requestAnimationFrame(draw);
    }
    
    animationFrameId = requestAnimationFrame(draw);
}

// Save camera device and restart stream
window.handleCameraDeviceChange = function(deviceId) {
    selectedCameraId = deviceId;
    localStorage.setItem('packguard_camera_id', deviceId);
    initCamera();
};

// ================= RECORDING ENGINE (MediaRecorder) =================

function getSupportedMimeType() {
    const types = [
        'video/webm;codecs=vp9',
        'video/webm;codecs=vp8',
        'video/webm',
        'video/mp4;codecs=h264',
        'video/mp4'
    ];
    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            return type;
        }
    }
    return '';
}

function startRecording(orderId) {
    if (isRecording) {
        // Already recording — this shouldn't happen with the new flow, but guard anyway
        console.warn('[PackGuard] startRecording dipanggil saat sedang merekam. Diabaikan.');
        return;
    }

    if (!webcamStream || !webcamStream.active) {
        alert('Kamera tidak aktif/tidak tersambung! Harap sambungkan kamera dulu.');
        return;
    }

    // Clean order id
    currentOrderId = orderId.trim().toUpperCase();
    window.currentOrderId = currentOrderId; // Expose for emulator button
    isRecording = true;
    recordingStartTime = Date.now();
    
    if (window.playStartBeep) window.playStartBeep();
    recordedChunks = [];

    // Capture 24 FPS stream from Canvas
    const canvasStream = canvas.captureStream(24);
    const mimeType = getSupportedMimeType();
    
    try {
        mediaRecorder = new MediaRecorder(canvasStream, { mimeType: mimeType });
    } catch (e) {
        console.warn('MIME type not supported, fallback to default recorder', e);
        mediaRecorder = new MediaRecorder(canvasStream);
    }

    mediaRecorder.ondataavailable = (event) => {
        if (event.data && event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };

    const activeOrderId = currentOrderId;

    mediaRecorder.onstop = () => {
        const durationSeconds = Math.round((Date.now() - recordingStartTime) / 1000);
        const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/webm' });
        uploadRecordedVideo(blob, activeOrderId, durationSeconds);
    };

    // Start recording, slices of 1s
    mediaRecorder.start(1000);

    // Update UI Status Indicators
    updateUIForRecording();
    
    // Set up overlays
    if (overlayOrderId) overlayOrderId.innerText = `ORDER ID: ${currentOrderId}`;
    
    // Start Recording Timer
    let seconds = 0;
    if (recordingTimerText) recordingTimerText.innerText = '00:00';
    
    timerInterval = setInterval(() => {
        seconds++;
        const mins = String(Math.floor(seconds / 60)).padStart(2, '0');
        const secs = String(seconds % 60).padStart(2, '0');
        if (recordingTimerText) recordingTimerText.innerText = `${mins}:${secs}`;
    }, 1000);
}

function stopRecording(callback = null) {
    if (!isRecording) {
        if (callback) callback();
        return;
    }

    if (window.playStopBeep) window.playStopBeep();

    isRecording = false;
    
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }

    // Reset UI Status
    updateUIToIdle();
    
    currentOrderId = '';
    window.currentOrderId = '';
    
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    // If callback passed, register a run-once callback for when upload process starts
    if (callback) {
        setTimeout(callback, 200);
    }
}

function updateUIForRecording() {
    // Canvas viewport glows red
    const viewport = document.getElementById('videoViewport');
    if (viewport) {
        viewport.className = "relative rounded-xl overflow-hidden aspect-video bg-slate-900/60 border-2 recording-glow";
    }

    // Status pill
    if (screenStatusIndicator) {
        screenStatusIndicator.className = 'flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-red-950/80 backdrop-blur-md border border-red-900 text-xs font-semibold text-red-500';
        screenStatusIndicator.querySelector('span').className = 'h-2 w-2 rounded-full bg-red-500 animate-pulse';
        screenStatusIndicator.querySelector('span').nextElementSibling.innerText = `RECORDING: ${currentOrderId}`;
    }

    // Timer display
    if (recordingTimer) recordingTimer.classList.remove('hidden');

    // Toggle Manual Buttons
    const startBtn = document.getElementById('manualStartBtn');
    const stopBtn = document.getElementById('manualStopBtn');
    if (startBtn) startBtn.disabled = true;
    if (stopBtn) {
        stopBtn.disabled = false;
        stopBtn.className = "bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white px-3 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-1.5 shadow-md shadow-red-950/20";
    }
}

function updateUIToIdle() {
    const viewport = document.getElementById('videoViewport');
    if (viewport) {
        viewport.className = "relative rounded-xl overflow-hidden aspect-video bg-slate-900/60 border border-slate-800/80";
    }

    if (screenStatusIndicator) {
        screenStatusIndicator.className = 'flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-950/80 backdrop-blur-md border border-slate-800 text-xs font-semibold text-emerald-400';
        screenStatusIndicator.querySelector('span').className = 'h-2 w-2 rounded-full bg-emerald-500';
        screenStatusIndicator.querySelector('span').nextElementSibling.innerText = 'IDLE';
    }

    if (recordingTimer) recordingTimer.classList.add('hidden');

    const startBtn = document.getElementById('manualStartBtn');
    const stopBtn = document.getElementById('manualStopBtn');
    if (startBtn) startBtn.disabled = false;
    if (stopBtn) {
        stopBtn.disabled = true;
        stopBtn.className = "bg-slate-900 border border-slate-800 hover:bg-slate-900/50 text-slate-500 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-1.5 cursor-not-allowed";
    }
}

// ================= MULTIPART UPLOAD SERVICE =================

async function saveVideoLocally(orderId, blob, durationSeconds) {
    if (!localDB) return null;
    return new Promise((resolve, reject) => {
        const transaction = localDB.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const backupId = orderId + '_' + Date.now();
        const item = {
            id: backupId,
            orderId: orderId,
            blob: blob,
            durationSeconds: durationSeconds,
            staffName: staffNameInput?.value || staffName,
            timestamp: Date.now()
        };
        const request = store.put(item);
        request.onsuccess = () => resolve(backupId);
        request.onerror = () => reject(request.error);
    });
}

async function removeLocalVideo(backupId) {
    if (!localDB) return;
    return new Promise((resolve, reject) => {
        const transaction = localDB.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.delete(backupId);
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

async function getPendingUploads() {
    if (!localDB) return [];
    return new Promise((resolve, reject) => {
        const transaction = localDB.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

window.retryPendingUploads = async function() {
    const pending = await getPendingUploads();
    if (pending.length === 0) return;
    
    alert(`Mencoba upload ulang ${pending.length} video...`);
    for (const item of pending) {
        await uploadRecordedVideo(item.blob, item.orderId, item.durationSeconds, item.id);
    }
};

async function updatePendingUploadsUI() {
    const pending = await getPendingUploads();
    let container = document.getElementById('pendingUploadsContainer');
    
    if (pending.length === 0) {
        if (container) container.classList.add('hidden');
        return;
    }
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'pendingUploadsContainer';
        container.className = 'fixed bottom-6 right-6 z-[9999] p-4 rounded-xl bg-amber-950/90 border border-amber-700/60 backdrop-blur-md shadow-lg flex items-center space-x-4';
        
        container.innerHTML = `
            <div>
                <div class="text-amber-200 font-bold text-sm">Upload Tertunda (<span id="pendingCount">0</span>)</div>
                <div class="text-xs text-amber-400/80">Video tersimpan lokal karena gagal upload.</div>
            </div>
            <button onclick="retryPendingUploads()" class="bg-amber-600 hover:bg-amber-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-all shadow-md">
                Coba Lagi
            </button>
        `;
        document.body.appendChild(container);
    }
    
    container.classList.remove('hidden');
    document.getElementById('pendingCount').innerText = pending.length;
}

async function uploadRecordedVideo(blob, orderId, durationSeconds, backupId = null) {
    if (window.showToast) window.showToast('Mengupload video...', 'info');
    
    if (!backupId) {
        try {
            backupId = await saveVideoLocally(orderId, blob, durationSeconds);
        } catch (e) {
            console.error('Failed to backup locally:', e);
        }
    }

    const formData = new FormData();
    formData.append('video', blob, `${orderId}.webm`);
    formData.append('order_id', orderId);
    formData.append('duration_seconds', durationSeconds);
    formData.append('staff_name', staffNameInput?.value || staffName);

    try {
        const response = await fetch('/api/logs/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('Upload success:', data.log);
            if (backupId) await removeLocalVideo(backupId);
            addLogToRecentTable(data.log);
            refreshStats();
            updatePendingUploadsUI();
            if (window.showToast) window.showToast(`✅ Rekaman disimpan: ${orderId}`, 'success');
        } else {
            console.error('Upload failed:', data.message);
            alert('Upload gagal: ' + data.message + '\nVideo disimpan sementara di perangkat Anda.');
            updatePendingUploadsUI();
        }
    } catch (e) {
        console.error('Upload network error:', e);
        alert('Terjadi kesalahan jaringan saat mengunggah video.\nVideo disimpan sementara di perangkat Anda dan dapat di-retry nanti.');
        updatePendingUploadsUI();
    }
}

function addLogToRecentTable(log) {
    if (!recentPackingLogsBody) return;
    
    // Remove "No records" row if present
    if (recentPackingLogsBody.innerText.includes('Belum ada riwayat hari ini')) {
        recentPackingLogsBody.innerHTML = '';
    }

    const card = document.createElement('div');
    card.className = 'pg-session-card rounded-xl p-3.5 flex flex-col space-y-2.5';
    
    const timeObj = new Date(log.created_at);
    const timeFormatted = String(timeObj.getHours()).padStart(2, '0') + ':' + String(timeObj.getMinutes()).padStart(2, '0');
    const sizeMb = (log.file_size / (1024 * 1024)).toFixed(2);
    const videoUrl = `/storage/${log.file_path}`;

    card.innerHTML = `
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="pg-session-badge text-[10px] px-1.5 py-0.5 rounded font-medium">${timeFormatted}</span>
                    <span class="text-xs pg-text-muted font-medium truncate max-w-[80px]">${log.staff_name}</span>
                </div>
                <div class="font-bold pg-text-primary text-sm mt-1.5">${log.order_id}</div>
            </div>
            <button onclick="playVideo('${videoUrl}', '${log.order_id}')" class="pg-btn-primary p-2 rounded-lg transition-colors shadow-sm" title="Putar Rekaman">
                <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>
        <div class="pg-session-meta flex justify-between text-[10px] font-medium pt-2.5">
            <span class="flex items-center space-x-1">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>${log.duration_seconds}s</span>
            </span>
            <span class="flex items-center space-x-1">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                <span>${sizeMb} MB</span>
            </span>
        </div>
    `;
    
    // Prepend new row
    recentPackingLogsBody.insertBefore(card, recentPackingLogsBody.firstChild);
}

// Refresh stats panels
async function refreshStats() {
    try {
        const response = await fetch('/api/stats');
        const data = await response.json();
        
        if (quickStatsTotal) quickStatsTotal.innerText = data.total_count;
        if (quickStatsToday) quickStatsToday.innerText = data.today_count;
        if (quickStatsSize) quickStatsSize.innerText = data.total_size_mb + ' MB';
        
        if (statsTotalLogs) statsTotalLogs.innerText = data.total_count;
        if (statsTotalSize) statsTotalSize.innerText = data.total_size_mb;
        if (statsAvgDuration) statsAvgDuration.innerText = data.avg_duration;
    } catch (e) {
        console.warn('Failed to refresh stats:', e);
    }
}

// ================= GLOBAL KEYBOARD WEDGE SCANNER LISTENER =================

function setupGlobalBarcodeListener() {
    window.addEventListener('keydown', (event) => {
        // Check if modal is open and key is ESC
        const modal = document.getElementById('videoModal');
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            window.closeVideoModal();
            return;
        }

        // Prevent listening if typing in inputs
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') {
            return;
        }

        const currentTime = Date.now();
        const diff = currentTime - lastKeyPressTime;
        lastKeyPressTime = currentTime;

        // Keycode checks
        const key = event.key;

        // Reset buffer if delay is too long (user typing manually)
        if (diff > BARCODE_INTERVAL_THRESHOLD) {
            barcodeBuffer = '';
        }

        // If key is Enter
        if (key === 'Enter') {
            if (barcodeBuffer.length >= 3) {
                // If there is a barcode in buffer, process it
                handleBarcodeScanned(barcodeBuffer);
            }
            
            barcodeBuffer = '';
            event.preventDefault();
            return;
        }

        // Append to buffer if it's a printable single character
        if (key.length === 1) {
            barcodeBuffer += key;
        }
    });
}

async function handleBarcodeScanned(code) {
    console.log('Barcode Scanned:', code);
    const cleanedCode = code.trim().toUpperCase();

    if (isRecording) {
        // If scanning the SAME barcode as the current recording → stop recording
        if (cleanedCode === currentOrderId) {
            console.log(`[PackGuard] Scan STOP — resi "${cleanedCode}" cocok, menghentikan rekaman.`);
            stopRecording();
        } else {
            // Different barcode while recording → ignore
            console.log(`[PackGuard] Scan DIABAIKAN — sedang merekam "${currentOrderId}", scan resi berbeda: "${cleanedCode}"`);
            showScanIgnoredNotification(cleanedCode);
        }
    } else {
        // Not recording → check if resi already exists in database before starting
        console.log(`[PackGuard] Checking if resi "${cleanedCode}" already exists...`);
        if (window.showToast) window.showToast(`Mendeteksi scan: ${cleanedCode}...`, 'info');
        
        try {
            const response = await fetch(`/api/logs/check/${encodeURIComponent(cleanedCode)}`);
            const data = await response.json();
            
            if (data.exists) {
                console.log(`[PackGuard] DITOLAK — resi "${cleanedCode}" sudah terdaftar di database.`);
                showDuplicateResiNotification(cleanedCode);
                return;
            }
        } catch (e) {
            console.warn('[PackGuard] Gagal mengecek duplikat resi, melanjutkan rekaman:', e);
            // If network check fails, allow recording to proceed (fail-open)
        }

        console.log(`[PackGuard] Scan START — memulai rekaman untuk resi "${cleanedCode}"`);
        startRecording(cleanedCode);
    }
}

// Show notification when a resi is already registered in the database (duplicate rejected)
function showDuplicateResiNotification(duplicateCode) {
    if (window.playErrorBeep) window.playErrorBeep();
    
    // Remove existing notifications
    const existing = document.getElementById('scanIgnoredNotification');
    if (existing) existing.remove();
    const existingDup = document.getElementById('scanDuplicateNotification');
    if (existingDup) existingDup.remove();

    const notification = document.createElement('div');
    notification.id = 'scanDuplicateNotification';
    notification.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[9999] px-5 py-3 rounded-xl bg-red-950/90 border border-red-700/60 backdrop-blur-md shadow-lg shadow-red-950/30 text-red-300 text-sm font-semibold flex items-center space-x-3';
    notification.innerHTML = `
        <svg class="h-5 w-5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
        <div>
            <div class="text-red-200 font-bold">Rekaman Ditolak — Resi Sudah Terdaftar</div>
            <div class="text-xs text-red-400/80 mt-0.5">Resi <strong>"${duplicateCode}"</strong> sudah memiliki rekaman video di database. Tidak dapat merekam ulang.</div>
        </div>
    `;
    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translate(-50%, -20px)';
        notification.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(() => notification.remove(), 400);
    }, 5000);
}

// Show a brief on-screen notification when a different barcode is scanned while recording
function showScanIgnoredNotification(ignoredCode) {
    // Remove existing notification if any
    const existing = document.getElementById('scanIgnoredNotification');
    if (existing) existing.remove();
    const existingDup = document.getElementById('scanDuplicateNotification');
    if (existingDup) existingDup.remove();

    const notification = document.createElement('div');
    notification.id = 'scanIgnoredNotification';
    notification.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[9999] px-5 py-3 rounded-xl bg-amber-950/90 border border-amber-700/60 backdrop-blur-md shadow-lg shadow-amber-950/30 text-amber-300 text-sm font-semibold flex items-center space-x-3';
    notification.innerHTML = `
        <svg class="h-5 w-5 text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <div class="text-amber-200">Scan Diabaikan</div>
            <div class="text-xs text-amber-400/80 mt-0.5">Resi <strong>"${ignoredCode}"</strong> diabaikan. Scan <strong>"${currentOrderId}"</strong> untuk menghentikan rekaman saat ini.</div>
        </div>
    `;
    document.body.appendChild(notification);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translate(-50%, -20px)';
        notification.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(() => notification.remove(), 400);
    }, 4000);
}

// ================= TABS SWITCHER & LOG TABLE LOGIC =================

window.switchTab = function(tabName) {
    // Hide all tabs
    document.getElementById('tab-packing').classList.add('hidden');
    document.getElementById('tab-logs').classList.add('hidden');
    document.getElementById('tab-settings').classList.add('hidden');
    
    // Show selected
    document.getElementById(`tab-${tabName}`).classList.remove('hidden');
    
    // Remove active styles from nav links
    const navBtnPacking = document.getElementById('nav-packing');
    const navBtnLogs = document.getElementById('nav-logs');
    const navBtnSettings = document.getElementById('nav-settings');
    
    const baseClass = "pg-nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold";
    
    navBtnPacking.className = baseClass;
    navBtnPacking.querySelector('svg').className = "h-5 w-5";
    navBtnLogs.className = baseClass;
    navBtnLogs.querySelector('svg').className = "h-5 w-5";
    navBtnSettings.className = baseClass;
    navBtnSettings.querySelector('svg').className = "h-5 w-5";
    
    const activeBtn = document.getElementById(`nav-${tabName}`);
    activeBtn.className = baseClass + " active";
    activeBtn.querySelector('svg').className = "h-5 w-5";
    
    // Load logs if logs tab selected
    if (tabName === 'logs') {
        loadLogsTable(1);
    }
};

let currentSearchQuery = '';
let searchTimeout = null;

window.handleSearchLogs = function(query) {
    currentSearchQuery = query;
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadLogsTable(1);
    }, 500);
};

async function loadLogsTable(page = 1) {
    if (!logsTableBody) return;
    
    logsTableBody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-500 font-semibold animate-pulse">Loading data log...</td></tr>';
    
    try {
        const response = await fetch(`/api/logs?page=${page}&q=${encodeURIComponent(currentSearchQuery)}`);
        const data = await response.json();
        
        logsTableBody.innerHTML = '';
        
        // Update stats
        if (statsTotalLogs) statsTotalLogs.innerText = data.total;
        
        if (data.data.length === 0) {
            logsTableBody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-600 font-medium">Log tidak ditemukan.</td></tr>';
            if (logsPagination) logsPagination.innerHTML = '';
            return;
        }
        
        data.data.forEach(log => {
            const row = document.createElement('tr');
            row.className = 'border-b border-slate-900/60 hover:bg-slate-900/20 text-slate-300 transition-colors';
            
            const time = new Date(log.created_at).toLocaleString();
            const size = (log.file_size / (1024 * 1024)).toFixed(2);
            const videoUrl = `/storage/${log.file_path}`;
            
            row.innerHTML = `
                <td class="py-3.5 font-medium">${time}</td>
                <td class="py-3.5 font-bold text-slate-100">${log.order_id}</td>
                <td class="py-3.5">${log.duration_seconds} Detik</td>
                <td class="py-3.5">${size} MB</td>
                <td class="py-3.5 text-slate-400">${log.staff_name}</td>
                <td class="py-3.5 text-right space-x-2">
                    <button onclick="playVideo('${videoUrl}', '${log.order_id}')" class="text-indigo-400 hover:text-indigo-300 font-semibold inline-flex items-center space-x-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Putar</span>
                    </button>
                    <a href="${videoUrl}" download="${log.order_id}.webm" class="text-cyan-400 hover:text-cyan-300 font-semibold inline-flex items-center space-x-1">
                        <span>Download</span>
                    </a>
                    <button onclick="deleteLogEntry(${log.id})" class="text-red-400 hover:text-red-300 font-semibold">
                        Hapus
                    </button>
                </td>
            `;
            logsTableBody.appendChild(row);
        });
        
        renderPagination(data);

    } catch (e) {
        console.error('Failed to load logs:', e);
        logsTableBody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-red-500 font-semibold">Gagal memuat log dari database.</td></tr>';
    }
}

function renderPagination(data) {
    if (!logsPagination) return;
    
    logsPagination.innerHTML = '';
    
    const info = document.createElement('div');
    info.innerText = `Menampilkan ${data.from || 0} - ${data.to || 0} dari ${data.total} data`;
    logsPagination.appendChild(info);
    
    const buttons = document.createElement('div');
    buttons.className = 'flex space-x-1';
    
    if (data.prev_page_url) {
        const prevBtn = document.createElement('button');
        prevBtn.innerText = 'Sebelumnya';
        prevBtn.className = 'pg-btn-primary px-3 py-1.5 rounded-lg';
        prevBtn.onclick = () => loadLogsTable(data.current_page - 1);
        buttons.appendChild(prevBtn);
    }
    
    if (data.next_page_url) {
        const nextBtn = document.createElement('button');
        nextBtn.innerText = 'Berikutnya';
        nextBtn.className = 'pg-btn-primary px-3 py-1.5 rounded-lg';
        nextBtn.onclick = () => loadLogsTable(data.current_page + 1);
        buttons.appendChild(nextBtn);
    }
    
    logsPagination.appendChild(buttons);
}

window.deleteLogEntry = async function(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus file log & video rekaman ini?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/logs/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadLogsTable(1);
            refreshStats();
        } else {
            alert('Gagal menghapus log: ' + data.message);
        }
    } catch (e) {
        console.error('Delete network error:', e);
        alert('Terjadi kesalahan jaringan saat menghapus log.');
    }
};

window.triggerRetentionCleanup = async function() {
    const daysInput = document.getElementById('retentionDaysInput');
    const days = daysInput ? daysInput.value : 30;
    
    if (!confirm(`Apakah Anda yakin ingin menghapus data & video packing yang berusia lebih dari ${days} hari?`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/logs/cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ days: parseInt(days) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            refreshStats();
        } else {
            alert('Gagal membersihkan data: ' + data.message);
        }
    } catch (e) {
        console.error('Cleanup network error:', e);
        alert('Terjadi kesalahan jaringan saat membersihkan data.');
    }
};

// ================= MODAL VIDEO PLAYER =================

window.playVideo = function(videoUrl, orderId) {
    const modal = document.getElementById('videoModal');
    const player = document.getElementById('modalVideoPlayer');
    const title = document.getElementById('videoModalTitle');
    const subtitle = document.getElementById('videoModalSubtitle');
    
    if (!modal || !player) return;
    
    title.innerText = `Bukti Perekaman: ${orderId}`;
    subtitle.innerText = `File URL: ${videoUrl}`;
    player.src = videoUrl;
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
    }, 50);
};

window.closeVideoModal = function() {
    const modal = document.getElementById('videoModal');
    const player = document.getElementById('modalVideoPlayer');
    
    if (!modal || !player) return;
    
    modal.classList.add('opacity-0');
    player.pause();
    player.src = '';
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
};

// ================= UX & AUDIO FEEDBACK =================

const beep = (freq, duration, type='sine') => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = type;
        osc.frequency.value = freq;
        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration/1000);
        setTimeout(() => { osc.stop(); ctx.close(); }, duration);
    } catch (e) { console.warn('Audio not supported', e) }
};

window.playStartBeep = () => beep(800, 200, 'sine');
window.playStopBeep = () => { beep(400, 150, 'sine'); setTimeout(() => beep(400, 200, 'sine'), 200); };
window.playErrorBeep = () => beep(200, 500, 'sawtooth');

window.showToast = function(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'fixed top-6 right-6 z-[9999] flex flex-col space-y-2';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    
    let bgClass = 'bg-slate-900 border-slate-700 text-white';
    if (type === 'success') bgClass = 'bg-emerald-950 border-emerald-800 text-emerald-400';
    if (type === 'error') bgClass = 'bg-red-950 border-red-800 text-red-400';
    if (type === 'warning') bgClass = 'bg-amber-950 border-amber-800 text-amber-400';
    
    toast.className = `px-4 py-3 rounded-xl border backdrop-blur-md shadow-lg transform transition-all duration-300 translate-x-full opacity-0 ${bgClass} text-sm font-semibold flex items-center space-x-2`;
    toast.innerHTML = `<span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 50);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// ================= FULLSCREEN =================
window.toggleFullscreen = function() {
    const elem = document.getElementById('videoViewport');
    if (!elem) return;
    
    if (!document.fullscreenElement) {
        elem.requestFullscreen().catch((err) => {
            console.warn(`Error attempting to enable fullscreen: ${err.message}`);
        });
    } else {
        document.exitFullscreen();
    }
};
