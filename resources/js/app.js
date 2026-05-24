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
const BARCODE_INTERVAL_THRESHOLD = 30; // ms

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
let quickStatsSize = null;
let statsTotalLogs = null;
let statsTotalSize = null;
let statsAvgDuration = null;

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
    
    // Load staff name from storage
    if (staffNameInput) {
        staffNameInput.value = staffName;
    }
});

function initTheme() {
    const theme = localStorage.getItem('packguard_theme') || 'dark';
    if (theme === 'light') {
        document.documentElement.classList.remove('bg-slate-950', 'text-slate-100');
        document.documentElement.classList.add('bg-white', 'text-slate-900', 'light');
    } else {
        document.documentElement.classList.add('bg-slate-950', 'text-slate-100');
        document.documentElement.classList.remove('bg-white', 'text-slate-900', 'light');
    }
}

window.toggleTheme = function(theme) {
    localStorage.setItem('packguard_theme', theme);
    initTheme();
    // Force a small refresh of classes on specific elements if needed
    location.reload(); // Simplest way to ensure all Tailwind colors re-apply correctly
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
        cameraStatusIndicator.className = 'flex items-center space-x-2 px-3 py-1.5 rounded-full bg-emerald-950/40 border border-emerald-900/50 text-xs font-semibold text-emerald-400';
        cameraStatusIndicator.querySelector('span').className = 'h-2 w-2 rounded-full bg-emerald-400';
    } else {
        cameraStatusIndicator.className = 'flex items-center space-x-2 px-3 py-1.5 rounded-full bg-red-950/40 border border-red-900/50 text-xs font-semibold text-red-400';
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

        // Overlay text panel background (Larger & More Opaque)
        ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
        ctx.fillRect(30, canvasHeight - 160, 500, 130);
        ctx.strokeStyle = 'rgba(99, 102, 241, 0.6)';
        ctx.lineWidth = 3;
        ctx.strokeRect(30, canvasHeight - 160, 500, 130);

        // Render Watermark Texts (Larger Fonts)
        ctx.font = 'bold 20px "Plus Jakarta Sans", sans-serif';
        ctx.fillStyle = '#818cf8'; // Indigo-400
        ctx.fillText('PACKGUARD AI - SECURITY PROOF', 50, canvasHeight - 130);

        ctx.font = 'bold 18px "Courier New", monospace';
        ctx.fillStyle = '#ffffff'; // White for better contrast
        ctx.fillText(`ORDER ID  : ${currentOrderId || 'IDLE (NO SCAN)'}`, 50, canvasHeight - 100);
        
        ctx.font = '16px "Courier New", monospace';
        ctx.fillStyle = '#e2e8f0'; // Slate-200
        ctx.fillText(`TIMESTAMP : ${dateStr}`, 50, canvasHeight - 75);
        ctx.fillText(`OPERATOR  : ${staffName.toUpperCase()}`, 50, canvasHeight - 50);

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
        // If recording active, stop previous and auto queue new
        stopRecording(() => {
            setTimeout(() => startRecording(orderId), 300);
        });
        return;
    }

    if (!webcamStream || !webcamStream.active) {
        alert('Kamera tidak aktif/tidak tersambung! Harap sambungkan kamera dulu.');
        return;
    }

    // Clean order id
    currentOrderId = orderId.trim().toUpperCase();
    isRecording = true;
    recordingStartTime = Date.now();
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

    mediaRecorder.onstop = () => {
        const durationSeconds = Math.round((Date.now() - recordingStartTime) / 1000);
        const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/webm' });
        uploadRecordedVideo(blob, currentOrderId, durationSeconds);
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

    isRecording = false;
    
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }

    // Reset UI Status
    updateUIToIdle();
    
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

async function uploadRecordedVideo(blob, orderId, durationSeconds) {
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
            // Append record to local packing station logs table instantly
            addLogToRecentTable(data.log);
            // Refresh stats & general lists
            refreshStats();
        } else {
            console.error('Upload failed:', data.message);
            alert('Upload gagal: ' + data.message);
        }
    } catch (e) {
        console.error('Upload network error:', e);
        alert('Terjadi kesalahan jaringan saat mengunggah video.');
    }
}

function addLogToRecentTable(log) {
    if (!recentPackingLogsBody) return;
    
    // Remove "No records" row if present
    if (recentPackingLogsBody.innerText.includes('Belum ada sesi packing')) {
        recentPackingLogsBody.innerHTML = '';
    }

    const row = document.createElement('tr');
    row.className = 'border-b border-slate-900/60 hover:bg-slate-900/20 text-slate-300 transition-colors';
    
    const timeFormatted = new Date(log.created_at).toLocaleString();
    const sizeMb = (log.file_size / (1024 * 1024)).toFixed(2);
    const videoUrl = `/storage/${log.file_path}`;

    row.innerHTML = `
        <td class="py-3 font-medium">${timeFormatted}</td>
        <td class="py-3 font-bold text-slate-100">${log.order_id}</td>
        <td class="py-3">${log.duration_seconds} Detik</td>
        <td class="py-3">${sizeMb} MB</td>
        <td class="py-3 text-slate-400">${log.staff_name}</td>
        <td class="py-3 text-right">
            <button onclick="playVideo('${videoUrl}', '${log.order_id}')" class="text-indigo-400 hover:text-indigo-300 font-semibold inline-flex items-center space-x-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Play</span>
            </button>
        </td>
    `;
    
    recentPackingLogsBody.insertBefore(row, recentPackingLogsBody.firstChild);
}

// Refresh stats panels
async function refreshStats() {
    try {
        const response = await fetch('/api/logs?q=');
        const data = await response.json();
        
        // Update stats widgets on the sidebar and stats tab
        const total = data.total;
        
        if (quickStatsTotal) quickStatsTotal.innerText = total;
        if (statsTotalLogs) statsTotalLogs.innerText = total;

        // Calculate size dynamically
        const logsResponse = await fetch('/api/logs');
        const logsData = await logsResponse.json();
        
        // Sum total size from pagination data if supported or recalculate
        // For accurate recalculation, reload the full page data
        // Let's reload dashboard metrics by triggering inline request or let them refresh on tab switch.
    } catch (e) {
        console.warn('Failed to refresh stats:', e);
    }
}

// ================= GLOBAL KEYBOARD WEDGE SCANNER LISTENER =================

function setupGlobalBarcodeListener() {
    window.addEventListener('keydown', (event) => {
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
            } else if (isRecording) {
                // If buffer is empty but we are recording, treat Enter as a manual STOP command
                console.log('Manual STOP triggered via ENTER key');
                stopRecording();
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

function handleBarcodeScanned(code) {
    console.log('Barcode Scanned:', code);
    const cleanedCode = code.trim().toUpperCase();

    if (cleanedCode === 'STOP_PACKING' || cleanedCode === 'STOP') {
        stopRecording();
    } else {
        startRecording(cleanedCode);
    }
}

// Manual Actions & Simulated Scan from Form
window.triggerManualSimulatedScan = function() {
    if (!simulatedResiInput) return;
    const value = simulatedResiInput.value.trim();
    if (!value) return;
    
    handleBarcodeScanned(value);
    simulatedResiInput.value = '';
};

// Emulate hardware speed keystroke input
window.emulateHardwareScan = function(code) {
    let index = 0;
    barcodeBuffer = '';
    lastKeyPressTime = Date.now();

    function typeNextChar() {
        if (index < code.length) {
            const char = code[index];
            const event = new KeyboardEvent('keydown', {
                key: char,
                bubbles: true,
                cancelable: true
            });
            
            // Set timestamp slightly manually
            lastKeyPressTime = Date.now();
            window.dispatchEvent(event);
            
            index++;
            setTimeout(typeNextChar, 5); // 5ms delay (mimics fast scanner wedge)
        } else {
            // Send final enter key
            setTimeout(() => {
                const enterEvent = new KeyboardEvent('keydown', {
                    key: 'Enter',
                    bubbles: true,
                    cancelable: true
                });
                lastKeyPressTime = Date.now();
                window.dispatchEvent(enterEvent);
            }, 5);
        }
    }
    
    typeNextChar();
};

window.manualStartRecording = function() {
    const simulatedInput = document.getElementById('simulatedResiInput');
    const orderId = simulatedInput?.value.trim() || 'MANUAL_' + Math.round(Math.random() * 100000);
    startRecording(orderId);
};

window.manualStopRecording = function() {
    stopRecording();
};

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
    
    const inactiveClass = "w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border border-transparent";
    const activeClass = "w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 bg-gradient-to-r from-indigo-950 to-indigo-900/40 border border-indigo-800/40 text-white shadow-md shadow-indigo-950/20";
    
    navBtnPacking.className = inactiveClass;
    navBtnPacking.querySelector('svg').className = "h-5 w-5 text-slate-500";
    navBtnLogs.className = inactiveClass;
    navBtnLogs.querySelector('svg').className = "h-5 w-5 text-slate-500";
    navBtnSettings.className = inactiveClass;
    navBtnSettings.querySelector('svg').className = "h-5 w-5 text-slate-500";
    
    const activeBtn = document.getElementById(`nav-${tabName}`);
    activeBtn.className = activeClass;
    activeBtn.querySelector('svg').className = "h-5 w-5 text-indigo-400";
    
    // Load logs if logs tab selected
    if (tabName === 'logs') {
        loadLogsTable(1);
    }
};

let currentSearchQuery = '';

window.handleSearchLogs = function(query) {
    currentSearchQuery = query;
    loadLogsTable(1);
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
        prevBtn.className = 'px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg hover:bg-slate-800 transition-all';
        prevBtn.onclick = () => loadLogsTable(data.current_page - 1);
        buttons.appendChild(prevBtn);
    }
    
    if (data.next_page_url) {
        const nextBtn = document.createElement('button');
        nextBtn.innerText = 'Berikutnya';
        nextBtn.className = 'px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg hover:bg-slate-800 transition-all';
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
