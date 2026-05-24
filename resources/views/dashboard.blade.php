<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PackGuard - Packing Video Recorder</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        // Apply theme immediately to prevent flash
        const theme = localStorage.getItem('packguard_theme') || 'dark';
        if (theme === 'light') {
            document.documentElement.classList.add('bg-white', 'text-slate-900', 'light');
        } else {
            document.documentElement.classList.add('bg-slate-950', 'text-slate-100', 'dark');
        }
    </script>
    
    <style>
        /* Light mode overrides */
        .light body { background-color: #ffffff; color: #0f172a; }
        .light header { background-color: #ffffff; border-color: #e2e8f0; }
        .light aside { background-color: #f8fafc; border-color: #e2e8f0; }
        .light main { background-image: none; background-color: #f1f5f9; }
        .light section > div, .light section { color: #1e293b; }
        .light .rounded-2xl, .light .rounded-xl { background-color: #ffffff; border-color: #e2e8f0; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); }
        .light input, .light select { background-color: #f8fafc; border-color: #e2e8f0; color: #0f172a; }
        .light th { color: #64748b; border-color: #e2e8f0; }
        .light td { color: #334155; border-color: #f1f5f9; }
        .light h1, .light h3, .light h4, .light .text-slate-200, .light .text-slate-300 { color: #0f172a !important; }
        .light .text-slate-400, .light .text-slate-500 { color: #64748b !important; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        /* Glowing animations */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
                border-color: rgba(239, 68, 68, 0.8);
            }
            50% {
                box-shadow: 0 0 30px rgba(239, 68, 68, 0.8);
                border-color: rgba(239, 68, 68, 1);
            }
        }
        .recording-glow {
            animation: pulse-glow 2s infinite;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.3);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.2);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.4);
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden flex flex-col antialiased">

    <!-- Top Header -->
    <header class="border-b border-slate-900 bg-slate-950/80 backdrop-blur-md px-6 py-4 flex items-center justify-between z-10">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <!-- App Logo Shield & Record -->
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">PackGuard <span class="text-cyan-400">BETA</span></h1>
                <p class="text-xs text-slate-500 font-medium">Packing Video Documentation System</p>
            </div>
        </div>
        
        <!-- Live Status Hub -->
        <div class="flex items-center space-x-4">
            <!-- Active Camera State -->
            <div id="cameraStatusIndicator" class="flex items-center space-x-2 px-3 py-1.5 rounded-full bg-slate-900/60 border border-slate-800 text-xs font-semibold text-slate-400">
                <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                <span>Camera Disconnected</span>
            </div>
            
            <!-- App Version -->
            <div class="text-xs px-3 py-1.5 rounded-full bg-indigo-950/40 border border-indigo-900/50 text-indigo-300 font-semibold">
                v1.0.0-Beta
            </div>
        </div>
    </header>

    <!-- App Body Layout -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="w-64 border-r border-slate-900 bg-slate-950/50 flex flex-col justify-between py-6 px-4">
            <nav class="space-y-1.5">
                <button onclick="switchTab('packing')" id="nav-packing" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 bg-gradient-to-r from-indigo-950 to-indigo-900/40 border border-indigo-800/40 text-white shadow-md shadow-indigo-950/20">
                    <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Packing Station</span>
                </button>
                
                <button onclick="switchTab('logs')" id="nav-logs" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border border-transparent">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Video Logs</span>
                </button>
                
                <button onclick="switchTab('settings')" id="nav-settings" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/60 border border-transparent">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                </button>
            </nav>
            
            <!-- Quick System Stats Summary -->
            <div class="rounded-2xl bg-slate-900/40 border border-slate-900 p-4 space-y-3">
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Quick Status</h4>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Total Packing</span>
                        <span id="quickStatsTotal" class="font-bold text-slate-200">{{ $total_count }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Disk Used</span>
                        <span id="quickStatsSize" class="font-bold text-slate-200">{{ $total_size_mb }} MB</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-8 bg-gradient-to-b from-slate-900/30 to-slate-950">
            
            <!-- ================= TAB: PACKING STATION ================= -->
            <section id="tab-packing" class="space-y-6">
                <!-- Grid: Live Preview & Simulator -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Camera Viewer Card -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="rounded-2xl border border-slate-900 bg-slate-950 p-4 relative overflow-hidden flex flex-col">
                            <!-- Header Info -->
                            <div class="flex items-center justify-between mb-4 z-10">
                                <div class="flex items-center space-x-2">
                                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                    <h3 class="text-sm font-semibold text-slate-300">Live Camera Feed</h3>
                                </div>
                                <span id="cameraResolutionLabel" class="text-xs text-slate-500 font-medium">Resolution: Checking...</span>
                            </div>
                            
                            <!-- Video Viewport Container -->
                            <div id="videoViewport" class="relative rounded-xl overflow-hidden aspect-video bg-slate-900/60 border border-slate-800/80 flex items-center justify-center">
                                <!-- Actual Live Video (Hidden from display directly, painted into canvas) -->
                                <video id="webcam" autoplay playsinline muted class="absolute w-0 h-0 opacity-0 pointer-events-none"></video>
                                
                                <!-- Canvas Drawing WebCam + Watermark -->
                                <canvas id="recorderCanvas" class="w-full h-full object-cover"></canvas>
                                
                                <!-- Video Overlay Over Canvas (Controls & Status indicators) -->
                                <div class="absolute inset-0 p-6 flex flex-col justify-between pointer-events-none select-none">
                                    <!-- Recording Overlay Indicator -->
                                    <div class="flex justify-between items-start">
                                        <div id="screenStatusIndicator" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-950/80 backdrop-blur-md border border-slate-800 text-xs font-semibold text-emerald-400">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span class="uppercase tracking-wider">IDLE</span>
                                        </div>
                                        <div id="recordingTimer" class="hidden flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-red-950/80 backdrop-blur-md border border-red-900 text-xs font-mono font-semibold text-red-400">
                                            <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                                            <span id="recordingTimerText">00:00</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Watermark Overlay details -->
                                    <div class="flex justify-between items-end">
                                        <div class="text-left bg-slate-950/70 backdrop-blur-sm p-3 rounded-lg border border-slate-900/80 text-[10px] text-slate-400 font-mono">
                                            <div class="font-bold text-slate-200">PACKGUARD AUTOMATION</div>
                                            <div id="overlayOrderId">ORDER ID: NONE</div>
                                            <div id="overlayTimestamp">YYYY-MM-DD HH:MM:SS</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs bg-slate-950/80 backdrop-blur-md border border-slate-800 px-2 py-1 rounded-md text-slate-500 font-medium">CAM PREVIEW</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mini Feed Info / Instructions -->
                        <div class="rounded-xl border border-slate-900/60 bg-slate-950/30 p-4 text-xs text-slate-400 flex items-center space-x-3">
                            <svg class="h-5 w-5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p><strong>Panduan Kerja:</strong> Barcode scanner mendeteksi ketukan di browser secara global. Cukup arahkan kursor ke browser (tidak perlu aktif di kolom ketik) dan scan barcode resi Anda untuk memulai perekaman otomatis. Scan barcode resi baru atau scan kode <code class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-indigo-300 font-mono">STOP_PACKING</code> untuk menyelesaikan sesi.</p>
                        </div>
                    </div>
                    
                    <!-- Right: Controls & Simulator Card -->
                    <div class="space-y-6">
                        <!-- Simulated Scanner Card (For testing) -->
                        <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-4">
                            <div class="flex items-center space-x-2">
                                <svg class="h-5 w-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 14h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-200">Scanner Simulator (Pengujian)</h3>
                            </div>
                            
                            <p class="text-xs text-slate-400">Gunakan simulator di bawah ini jika Anda belum menghubungkan USB Barcode Scanner fisik.</p>
                            
                            <!-- Manual Input Simulation -->
                            <div class="space-y-2">
                                <label class="text-xs text-slate-400 font-medium">Nomor Resi / Order ID</label>
                                <div class="flex space-x-2">
                                    <input type="text" id="simulatedResiInput" placeholder="Contoh: RESI987654321" class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-indigo-500">
                                    <button onclick="triggerManualSimulatedScan()" class="bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs transition-all duration-200 flex items-center space-x-1 shadow-md shadow-cyan-950/20">
                                        <span>Scan</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="border-t border-slate-900 my-4"></div>
                            
                            <!-- Hardware Emulation -->
                            <div class="space-y-2.5">
                                <label class="text-xs text-slate-400 font-medium flex items-center justify-between">
                                    <span>Hardware Keystroke Emulator</span>
                                    <span class="px-1.5 py-0.2 rounded bg-cyan-950/60 border border-cyan-800/40 text-[9px] text-cyan-300 font-mono">Dev Only</span>
                                </label>
                                <p class="text-[11px] text-slate-500 leading-relaxed">Mensimulasikan input keyboard berkecepatan tinggi (&lt; 10ms antar karakter) untuk menguji validitas pendeteksian buffer scanner internal browser.</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="emulateHardwareScan('ORDER998877')" class="bg-slate-900 border border-slate-800 hover:bg-slate-800/60 text-slate-300 px-3 py-2 rounded-xl text-xs font-semibold transition-all">
                                        Simulasi Scan Resi
                                    </button>
                                    <button onclick="emulateHardwareScan('STOP_PACKING')" class="bg-slate-900 border border-slate-800 hover:bg-slate-800/60 text-slate-300 px-3 py-2 rounded-xl text-xs font-semibold transition-all">
                                        Simulasi Scan STOP
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Recording Controls -->
                        <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-4">
                            <div class="flex items-center space-x-2">
                                <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                                <h3 class="text-sm font-semibold text-slate-200">Manual Controls</h3>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <button id="manualStartBtn" onclick="manualStartRecording()" class="bg-slate-900 border border-slate-800 hover:border-indigo-800/50 hover:bg-indigo-950/20 text-slate-300 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Manual Start</span>
                                </button>
                                <button id="manualStopBtn" disabled onclick="manualStopRecording()" class="bg-slate-900 border border-slate-800 hover:bg-slate-900/50 text-slate-500 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-1.5 cursor-not-allowed">
                                    <span class="h-2 w-2 rounded-full bg-red-600/40"></span>
                                    <span>Manual Stop</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Packing Session Logs (Local updates) -->
                <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-200">Recent Packing Sessions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-900 text-slate-500 font-semibold uppercase tracking-wider">
                                    <th class="pb-3">Waktu</th>
                                    <th class="pb-3">Nomor Resi / Order ID</th>
                                    <th class="pb-3">Durasi</th>
                                    <th class="pb-3">Ukuran File</th>
                                    <th class="pb-3">Staf</th>
                                    <th class="pb-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="recentPackingLogsBody">
                                @forelse ($recent_logs as $log)
                                <tr class="border-b border-slate-900/60 hover:bg-slate-900/20 text-slate-300 transition-colors">
                                    <td class="py-3 font-medium">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="py-3 font-bold text-slate-100">{{ $log->order_id }}</td>
                                    <td class="py-3">{{ $log->duration_seconds }} Detik</td>
                                    <td class="py-3">{{ round(($log->file_size ?? 0) / (1024 * 1024), 2) }} MB</td>
                                    <td class="py-3 text-slate-400">{{ $log->staff_name }}</td>
                                    <td class="py-3 text-right">
                                        <button onclick="playVideo('{{ asset('storage/' . $log->file_path) }}', '{{ $log->order_id }}')" class="text-indigo-400 hover:text-indigo-300 font-semibold inline-flex items-center space-x-1">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>Play</span>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-600 font-medium">Belum ada sesi packing terdaftar.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- ================= TAB: VIDEO LOGS ================= -->
            <section id="tab-logs" class="space-y-6 hidden">
                <!-- Grid Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 flex flex-col justify-between shadow-md">
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Video Bukti</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsTotalLogs" class="text-3xl font-bold tracking-tight text-white">{{ $total_count }}</span>
                            <span class="ml-1.5 text-xs font-semibold text-slate-500">Video</span>
                        </div>
                    </div>
                    
                    <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 flex flex-col justify-between shadow-md">
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Ukuran Penyimpanan</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsTotalSize" class="text-3xl font-bold tracking-tight text-indigo-400">{{ $total_size_mb }}</span>
                            <span class="ml-1.5 text-xs font-semibold text-slate-500">MB</span>
                        </div>
                    </div>
                    
                    <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 flex flex-col justify-between shadow-md">
                        <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Rata-rata Durasi</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsAvgDuration" class="text-3xl font-bold tracking-tight text-cyan-400">{{ $avg_duration }}</span>
                            <span class="ml-1.5 text-xs font-semibold text-slate-500">Detik / Order</span>
                        </div>
                    </div>
                </div>
                
                <!-- Filter and Search Log Card -->
                <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold text-slate-200">Log & Video Finder</h3>
                        
                        <!-- Search Form -->
                        <div class="w-full md:w-80 relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" id="logSearchInput" onkeyup="handleSearchLogs(this.value)" placeholder="Cari Order ID / Resi..." class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <!-- Table Logs -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-900 text-slate-500 font-semibold uppercase tracking-wider">
                                    <th class="pb-3">Waktu Record</th>
                                    <th class="pb-3">Nomor Resi / Order ID</th>
                                    <th class="pb-3">Durasi</th>
                                    <th class="pb-3">Ukuran File</th>
                                    <th class="pb-3">Nama Staf</th>
                                    <th class="pb-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <!-- Dynamically loaded via Ajax in app.js -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination container -->
                    <div id="logsPagination" class="flex justify-between items-center text-xs text-slate-400 pt-4 border-t border-slate-900/60">
                        <!-- Dynamic pagination buttons -->
                    </div>
                </div>
            </section>
            
            <!-- ================= TAB: SETTINGS ================= -->
            <section id="tab-settings" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column: Camera Settings -->
                    <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-6">
                        <div class="flex items-center space-x-2.5">
                            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-slate-200">Hardware & Camera Settings</h3>
                        </div>
                        
                        <!-- Camera selector -->
                        <div class="space-y-2">
                            <label for="cameraSelect" class="text-xs text-slate-400 font-medium">Select Camera Device</label>
                            <select id="cameraSelect" onchange="handleCameraDeviceChange(this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 focus:outline-none focus:border-indigo-500">
                                <option value="">Loading cameras...</option>
                            </select>
                            <p class="text-[10px] text-slate-500">Pilih kamera yang dipasang di atas meja packing. Perangkat akan disimpan di memori browser.</p>
                        </div>
                        
                        <!-- Staff Name config -->
                        <div class="space-y-2">
                            <label for="staffNameInput" class="text-xs text-slate-400 font-medium">Operator / Staff Name</label>
                            <input type="text" id="staffNameInput" value="Default Staff" onchange="localStorage.setItem('packguard_staff_name', this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 focus:outline-none focus:border-indigo-500">
                            <p class="text-[10px] text-slate-500">Nama staf yang sedang aktif di workstation ini (dicatat ke metadata log).</p>
                        </div>
                    </div>
                    
                    <!-- Right Column: Storage & Retention -->
                    <div class="space-y-6">
                        <!-- Theme Settings -->
                        <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-6">
                            <div class="flex items-center space-x-2.5">
                                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-200">Appearance Settings</h3>
                            </div>
                            
                            <div class="space-y-3">
                                <label class="text-xs text-slate-400 font-medium">Application Theme</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="toggleTheme('dark')" class="flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-900 text-xs font-semibold hover:bg-slate-800 transition-all text-slate-200" id="theme-dark-btn">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                        <span>Dark Mode</span>
                                    </button>
                                    <button onclick="toggleTheme('light')" class="flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold hover:bg-slate-50 transition-all text-slate-900" id="theme-light-btn">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.728 12.728L5.657 5.657" /></svg>
                                        <span>Light Mode</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-900 bg-slate-950 p-6 space-y-6">
                            <div class="flex items-center space-x-2.5">
                                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-200">Storage & Auto Retention</h3>
                            </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="retentionDaysInput" class="text-xs text-slate-400 font-medium">Auto Delete Older Than (Days)</label>
                                <div class="flex space-x-3">
                                    <input type="number" id="retentionDaysInput" value="30" min="1" class="w-32 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-100 focus:outline-none focus:border-indigo-500">
                                    <button onclick="triggerRetentionCleanup()" class="bg-red-950/80 hover:bg-red-900 border border-red-800 text-red-300 font-semibold px-4 py-2 rounded-xl text-xs transition-all duration-200">
                                        Jalankan Pembersihan Manual
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-500">Sistem akan menghapus log dan file video yang usianya melebihi hari yang ditentukan untuk menghemat kapasitas HDD lokal.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- VIDEO PLAYER INLINE MODAL -->
    <div id="videoModal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-md flex items-center justify-center z-50 hidden transition-opacity duration-300 opacity-0">
        <!-- Close overlay -->
        <div class="absolute inset-0 cursor-pointer" onclick="closeVideoModal()"></div>
        
        <div class="relative w-full max-w-3xl mx-4 bg-slate-950 border border-slate-900 rounded-2xl overflow-hidden shadow-2xl z-10 flex flex-col">
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-slate-900 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-200" id="videoModalTitle">Video Playback</h3>
                    <p class="text-[10px] text-slate-500 font-medium" id="videoModalSubtitle">Viewing proof file</p>
                </div>
                <button onclick="closeVideoModal()" class="text-slate-400 hover:text-white transition-all bg-slate-900 border border-slate-800/80 rounded-lg p-1.5">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Video Player -->
            <div class="relative aspect-video bg-black flex items-center justify-center">
                <video id="modalVideoPlayer" controls autoplay class="w-full h-full object-contain"></video>
            </div>
        </div>
    </div>

</body>
</html>
