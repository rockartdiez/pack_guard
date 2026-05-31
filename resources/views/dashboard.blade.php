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
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    <style>
        /* ====== CSS Custom Properties Theme System ====== */
        :root {
            /* Light mode (default) */
            --pg-bg-base: #f1f5f9;
            --pg-bg-surface: #ffffff;
            --pg-bg-surface-alt: #f8fafc;
            --pg-bg-inset: #f1f5f9;
            --pg-bg-overlay: rgba(255,255,255,0.8);
            --pg-border: #e2e8f0;
            --pg-border-subtle: #f1f5f9;
            --pg-text-primary: #0f172a;
            --pg-text-secondary: #334155;
            --pg-text-muted: #64748b;
            --pg-text-faint: #94a3b8;
            --pg-shadow: 0 1px 3px 0 rgba(0,0,0,0.08), 0 1px 2px -1px rgba(0,0,0,0.08);
            --pg-shadow-lg: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.05);
            --pg-input-bg: #f8fafc;
            --pg-input-border: #e2e8f0;
            --pg-header-bg: rgba(255,255,255,0.85);
            --pg-sidebar-bg: #f8fafc;
            --pg-nav-active-from: #eef2ff;
            --pg-nav-active-to: #e0e7ff;
            --pg-nav-active-border: #c7d2fe;
            --pg-nav-active-text: #3730a3;
            --pg-nav-hover-bg: #f1f5f9;
            --pg-card-hover: #f8fafc;
            --pg-badge-bg: #f1f5f9;
            --pg-scrollbar-track: rgba(241,245,249,0.5);
            --pg-scrollbar-thumb: rgba(148,163,184,0.3);
            --pg-scrollbar-thumb-hover: rgba(148,163,184,0.5);
            --pg-modal-backdrop: rgba(248,250,252,0.9);

            /* Semantic Badges */
            --pg-badge-indigo-bg: #e0e7ff;
            --pg-badge-indigo-text: #4338ca;
            --pg-badge-indigo-border: #c7d2fe;
            
            --pg-badge-emerald-bg: #d1fae5;
            --pg-badge-emerald-text: #059669;
            --pg-badge-emerald-border: #a7f3d0;
            
            --pg-badge-red-bg: #fee2e2;
            --pg-badge-red-text: #dc2626;
            --pg-badge-red-border: #fecaca;
            
            /* Buttons */
            --pg-btn-primary-bg: #f1f5f9;
            --pg-btn-primary-border: #e2e8f0;
            --pg-btn-primary-text: #334155;
            --pg-btn-primary-hover: #e2e8f0;
            
            --pg-btn-danger-bg: #fef2f2;
            --pg-btn-danger-border: #fecaca;
            --pg-btn-danger-text: #ef4444;
            --pg-btn-danger-hover: #fee2e2;

            --pg-btn-active-bg: #eef2ff;
            --pg-btn-active-border: #c7d2fe;
            --pg-btn-active-text: #3730a3;
        }

        .dark {
            --pg-bg-base: #020617;
            --pg-bg-surface: #0f172a;
            --pg-bg-surface-alt: rgba(15,23,42,0.5);
            --pg-bg-inset: rgba(15,23,42,0.4);
            --pg-bg-overlay: rgba(2,6,23,0.8);
            --pg-border: #1e293b;
            --pg-border-subtle: rgba(30,41,59,0.6);
            --pg-text-primary: #f1f5f9;
            --pg-text-secondary: #cbd5e1;
            --pg-text-muted: #64748b;
            --pg-text-faint: #475569;
            --pg-shadow: 0 1px 3px 0 rgba(0,0,0,0.3);
            --pg-shadow-lg: 0 4px 6px -1px rgba(0,0,0,0.4), 0 2px 4px -2px rgba(0,0,0,0.3);
            --pg-input-bg: #0f172a;
            --pg-input-border: #1e293b;
            --pg-header-bg: rgba(2,6,23,0.8);
            --pg-sidebar-bg: rgba(2,6,23,0.5);
            --pg-nav-active-from: #1e1b4b;
            --pg-nav-active-to: rgba(30,27,75,0.4);
            --pg-nav-active-border: rgba(67,56,202,0.4);
            --pg-nav-active-text: #ffffff;
            --pg-nav-hover-bg: rgba(15,23,42,0.6);
            --pg-card-hover: rgba(30,41,59,0.4);
            --pg-badge-bg: rgba(30,41,59,0.8);
            --pg-scrollbar-track: rgba(15,23,42,0.3);
            --pg-scrollbar-thumb: rgba(148,163,184,0.2);
            --pg-scrollbar-thumb-hover: rgba(148,163,184,0.4);
            --pg-modal-backdrop: rgba(2,6,23,0.9);

            /* Semantic Badges */
            --pg-badge-indigo-bg: rgba(49, 46, 129, 0.4);
            --pg-badge-indigo-text: #818cf8;
            --pg-badge-indigo-border: rgba(67, 56, 202, 0.5);
            
            --pg-badge-emerald-bg: rgba(6, 78, 59, 0.4);
            --pg-badge-emerald-text: #34d399;
            --pg-badge-emerald-border: rgba(5, 150, 105, 0.5);
            
            --pg-badge-red-bg: rgba(127, 29, 29, 0.4);
            --pg-badge-red-text: #f87171;
            --pg-badge-red-border: rgba(153, 27, 27, 0.5);
            
            /* Buttons */
            --pg-btn-primary-bg: #0f172a;
            --pg-btn-primary-border: #1e293b;
            --pg-btn-primary-text: #cbd5e1;
            --pg-btn-primary-hover: rgba(30, 41, 59, 0.8);
            
            --pg-btn-danger-bg: rgba(69, 10, 10, 0.4);
            --pg-btn-danger-border: rgba(153, 27, 27, 0.4);
            --pg-btn-danger-text: #f87171;
            --pg-btn-danger-hover: rgba(127, 29, 29, 0.6);

            --pg-btn-active-bg: rgba(49, 46, 129, 0.4);
            --pg-btn-active-border: rgba(67, 56, 202, 0.5);
            --pg-btn-active-text: #818cf8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--pg-bg-base);
            color: var(--pg-text-primary);
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }

        /* ====== Component Styles ====== */
        .pg-header {
            background-color: var(--pg-header-bg);
            border-color: var(--pg-border);
        }
        .pg-sidebar {
            background-color: var(--pg-sidebar-bg);
            border-color: var(--pg-border);
        }
        .pg-main {
            background-color: var(--pg-bg-base);
        }
        .pg-card {
            background-color: var(--pg-bg-surface);
            border-color: var(--pg-border);
            box-shadow: var(--pg-shadow);
        }
        .pg-card-inset {
            background-color: var(--pg-bg-inset);
            border-color: var(--pg-border);
        }
        .pg-input {
            background-color: var(--pg-input-bg);
            border-color: var(--pg-input-border);
            color: var(--pg-text-primary);
        }
        .pg-input::placeholder {
            color: var(--pg-text-faint);
        }
        .pg-input:focus {
            border-color: #6366f1;
            outline: none;
        }

        /* Text Hierarchy */
        .pg-text-primary { color: var(--pg-text-primary); }
        .pg-text-secondary { color: var(--pg-text-secondary); }
        .pg-text-muted { color: var(--pg-text-muted); }
        .pg-text-faint { color: var(--pg-text-faint); }

        /* Badges */
        .pg-badge-indigo { background-color: var(--pg-badge-indigo-bg); color: var(--pg-badge-indigo-text); border: 1px solid var(--pg-badge-indigo-border); }
        .pg-badge-emerald { background-color: var(--pg-badge-emerald-bg); color: var(--pg-badge-emerald-text); border: 1px solid var(--pg-badge-emerald-border); }
        .pg-badge-red { background-color: var(--pg-badge-red-bg); color: var(--pg-badge-red-text); border: 1px solid var(--pg-badge-red-border); }
        
        /* Buttons */
        .pg-btn-primary { background-color: var(--pg-btn-primary-bg); color: var(--pg-btn-primary-text); border: 1px solid var(--pg-btn-primary-border); transition: all 0.2s; }
        .pg-btn-primary:hover { background-color: var(--pg-btn-primary-hover); }
        .pg-btn-danger { background-color: var(--pg-btn-danger-bg); color: var(--pg-btn-danger-text); border: 1px solid var(--pg-btn-danger-border); transition: all 0.2s; }
        .pg-btn-danger:hover { background-color: var(--pg-btn-danger-hover); }
        .pg-btn-active { background-color: var(--pg-btn-active-bg); color: var(--pg-btn-active-text); border: 1px solid var(--pg-btn-active-border); }

        /* Navigation */
        .pg-nav-item {
            color: var(--pg-text-muted);
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .pg-nav-item:hover {
            color: var(--pg-text-primary);
            background-color: var(--pg-nav-hover-bg);
        }
        .pg-nav-item.active {
            background: linear-gradient(to right, var(--pg-nav-active-from), var(--pg-nav-active-to));
            border-color: var(--pg-nav-active-border);
            color: var(--pg-nav-active-text);
            box-shadow: var(--pg-shadow);
        }
        .pg-nav-item.active svg {
            color: #818cf8;
        }

        /* Stats card */
        .pg-stat-label { color: var(--pg-text-muted); }
        .pg-stat-value { color: var(--pg-text-primary); }

        /* Table */
        .pg-table th {
            color: var(--pg-text-muted);
            border-bottom: 1px solid var(--pg-border);
        }
        .pg-table td {
            color: var(--pg-text-secondary);
            border-bottom: 1px solid var(--pg-border-subtle);
        }
        .pg-table tr:hover td {
            background-color: var(--pg-card-hover);
        }

        /* Session cards */
        .pg-session-card {
            background-color: var(--pg-bg-inset);
            border: 1px solid var(--pg-border-subtle);
            transition: all 0.15s;
        }
        .pg-session-card:hover {
            background-color: var(--pg-card-hover);
        }
        .pg-session-badge {
            background-color: var(--pg-badge-bg);
            color: var(--pg-text-secondary);
        }
        .pg-session-meta {
            color: var(--pg-text-muted);
            border-top: 1px solid var(--pg-border-subtle);
        }

        /* Pagination */
        .pg-pagination { color: var(--pg-text-muted); border-top: 1px solid var(--pg-border-subtle); }

        /* Modal */
        .pg-modal-bg { background-color: var(--pg-modal-backdrop); }
        .pg-modal-card {
            background-color: var(--pg-bg-surface);
            border-color: var(--pg-border);
        }
        .pg-modal-header { border-color: var(--pg-border); }

        /* Dividers */
        .pg-divider { border-color: var(--pg-border); }

        /* Hint / Info box */
        .pg-info-box {
            background-color: var(--pg-bg-inset);
            border-color: var(--pg-border-subtle);
            color: var(--pg-text-muted);
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
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--pg-scrollbar-track); }
        ::-webkit-scrollbar-thumb { background: var(--pg-scrollbar-thumb); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--pg-scrollbar-thumb-hover); }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden flex flex-col antialiased">

    <!-- Top Header -->
    <header class="pg-header border-b backdrop-blur-md px-6 py-4 flex items-center justify-between z-10">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <!-- App Logo Shield & Record -->
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight pg-text-primary">PackGuard <span class="text-cyan-500">BETA</span></h1>
                <p class="text-xs pg-text-muted font-medium">Packing Video Documentation System</p>
            </div>
        </div>
        
        <!-- Live Status Hub -->
        <div class="flex items-center space-x-4">
            <!-- Active Camera State -->
            <div id="cameraStatusIndicator" class="pg-badge-red flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-semibold">
                <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                <span>Camera Disconnected</span>
            </div>
            
            <!-- App Version -->
            <div class="pg-badge-indigo text-xs px-3 py-1.5 rounded-full font-semibold">
                v1.0.0-Beta
            </div>
        </div>
    </header>

    <!-- App Body Layout -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="pg-sidebar w-64 border-r flex flex-col justify-between py-6 px-4">
            <nav class="space-y-1.5">
                <button onclick="switchTab('packing')" id="nav-packing" class="pg-nav-item active w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Packing Station</span>
                </button>
                
                <button onclick="switchTab('logs')" id="nav-logs" class="pg-nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Video Logs</span>
                </button>
                
                <button onclick="switchTab('settings')" id="nav-settings" class="pg-nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                </button>
            </nav>
            
            <!-- Quick System Stats Summary -->
            <div class="pg-card-inset rounded-2xl p-4 space-y-3">
                <h4 class="text-xs font-semibold pg-text-faint uppercase tracking-wider">Quick Status</h4>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="pg-text-muted">Total Packing</span>
                        <span id="quickStatsTotal" class="font-bold pg-text-primary">{{ $total_count }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="pg-text-muted">Packing Hari Ini</span>
                        <span id="quickStatsToday" class="font-bold text-cyan-500">{{ $today_count }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="pg-text-muted">Disk Used</span>
                        <span id="quickStatsSize" class="font-bold pg-text-primary">{{ $total_size_mb }} MB</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="pg-main flex-1 overflow-y-auto p-8">
            
            <!-- ================= TAB: PACKING STATION ================= -->
            <section id="tab-packing" class="space-y-6">
                <!-- Grid: Live Preview & Simulator -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Camera Viewer Card -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="pg-card rounded-2xl border p-4 relative overflow-hidden flex flex-col">
                            <!-- Header Info -->
                            <div class="flex items-center justify-between mb-4 z-10">
                                <div class="flex items-center space-x-2">
                                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                    <h3 class="text-sm font-semibold pg-text-secondary">Live Camera Feed</h3>
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
                                    <!-- Top Row -->
                                    <div class="flex justify-between items-start">
                                        <!-- Top-Left: Watermark Only -->
                                        <div class="text-left bg-slate-950/70 backdrop-blur-sm p-3 rounded-lg border border-slate-900/80 text-[10px] text-slate-400 font-mono">
                                            <div class="font-bold text-slate-200">PACKGUARD AUTOMATION</div>
                                            <div id="overlayOrderId">ORDER ID: NONE</div>
                                            <div id="overlayTimestamp">YYYY-MM-DD HH:MM:SS</div>
                                        </div>
                                        <!-- Top-Right: Status + Timer -->
                                        <div class="flex items-center space-x-2">
                                            <div id="screenStatusIndicator" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-950/80 backdrop-blur-md border border-slate-800 text-xs font-semibold text-emerald-400">
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                <span class="uppercase tracking-wider">IDLE</span>
                                            </div>
                                            <div id="recordingTimer" class="hidden flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-red-950/80 backdrop-blur-md border border-red-900 text-xs font-mono font-semibold text-red-400">
                                                <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                                                <span id="recordingTimerText">00:00</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Bottom Row -->
                                    <div class="flex justify-between items-end pointer-events-auto">
                                        <button onclick="toggleFullscreen()" class="bg-slate-950/80 hover:bg-slate-900 backdrop-blur-md border border-slate-800 text-slate-400 hover:text-white p-1.5 rounded-md transition-all shadow-md" title="Toggle Fullscreen">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                            </svg>
                                        </button>
                                        <span class="text-xs bg-slate-950/80 backdrop-blur-md border border-slate-800 px-2 py-1 rounded-md text-slate-500 font-medium">CAM PREVIEW</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mini Feed Info / Instructions -->
                        <div class="pg-info-box rounded-xl border p-4 text-xs flex items-center space-x-3">
                            <svg class="h-5 w-5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p><strong>Panduan Kerja:</strong> Barcode scanner mendeteksi ketukan di browser secara global. Cukup arahkan kursor ke browser (tidak perlu aktif di kolom ketik) dan <strong>scan barcode resi</strong> Anda untuk <strong>memulai</strong> perekaman otomatis. Setelah selesai packing, <strong>scan barcode resi yang sama</strong> untuk <strong>menghentikan</strong> dan menyimpan rekaman.</p>
                        </div>
                    </div>
                    
                    <!-- Right: Feed / Recent Logs -->
                    <div class="space-y-4 h-full flex flex-col">
                        <div class="pg-card rounded-2xl border flex-1 p-5 flex flex-col h-full max-h-[calc(100vh-14rem)]">
                            <div class="flex items-center space-x-2 mb-5">
                                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-sm font-semibold pg-text-primary">Sesi Packing Terakhir</h3>
                            </div>
                            
                            <div id="recentPackingLogsBody" class="space-y-3 overflow-y-auto pr-1 flex-1 custom-scrollbar">
                                @forelse ($recent_logs as $log)
                                <div class="pg-session-card rounded-xl p-3.5 flex flex-col space-y-2.5">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <span class="pg-session-badge text-[10px] px-1.5 py-0.5 rounded font-medium">{{ $log->created_at->format('H:i') }}</span>
                                                <span class="text-xs pg-text-muted font-medium truncate max-w-[80px]">{{ $log->staff_name }}</span>
                                            </div>
                                            <div class="font-bold pg-text-primary text-sm mt-1.5">{{ $log->order_id }}</div>
                                        </div>
                                        <button onclick="playVideo('{{ asset('storage/' . $log->file_path) }}', '{{ $log->order_id }}')" class="bg-indigo-950/40 border border-indigo-900/30 hover:bg-indigo-900/60 hover:border-indigo-700/50 text-indigo-400 p-2 rounded-lg transition-colors shadow-sm" title="Putar Rekaman">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="pg-session-meta flex justify-between text-[10px] font-medium pt-2.5">
                                        <span class="flex items-center space-x-1">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>{{ $log->duration_seconds }}s</span>
                                        </span>
                                        <span class="flex items-center space-x-1">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                                            <span>{{ round(($log->file_size ?? 0) / (1024 * 1024), 2) }} MB</span>
                                        </span>
                                    </div>
                                </div>
                                @empty
                                <div class="flex flex-col items-center justify-center py-10 space-y-2 opacity-40">
                                    <svg class="h-8 w-8 pg-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <div class="text-center pg-text-muted text-xs font-medium">Belum ada riwayat hari ini.</div>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- ================= TAB: VIDEO LOGS ================= -->
            <section id="tab-logs" class="space-y-6 hidden">
                <!-- Grid Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="pg-card rounded-2xl border p-6 flex flex-col justify-between">
                        <span class="text-xs pg-stat-label font-semibold uppercase tracking-wider">Total Video Bukti</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsTotalLogs" class="text-3xl font-bold tracking-tight pg-text-primary">{{ $total_count }}</span>
                            <span class="ml-1.5 text-xs font-semibold pg-text-faint">Video</span>
                        </div>
                    </div>
                    
                    <div class="pg-card rounded-2xl border p-6 flex flex-col justify-between">
                        <span class="text-xs pg-stat-label font-semibold uppercase tracking-wider">Ukuran Penyimpanan</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsTotalSize" class="text-3xl font-bold tracking-tight text-indigo-400">{{ $total_size_mb }}</span>
                            <span class="ml-1.5 text-xs font-semibold pg-text-faint">MB</span>
                        </div>
                    </div>
                    
                    <div class="pg-card rounded-2xl border p-6 flex flex-col justify-between">
                        <span class="text-xs pg-stat-label font-semibold uppercase tracking-wider">Rata-rata Durasi</span>
                        <div class="mt-4 flex items-baseline">
                            <span id="statsAvgDuration" class="text-3xl font-bold tracking-tight text-cyan-400">{{ $avg_duration }}</span>
                            <span class="ml-1.5 text-xs font-semibold pg-text-faint">Detik / Order</span>
                        </div>
                    </div>
                </div>
                
                <!-- Filter and Search Log Card -->
                <div class="pg-card rounded-2xl border p-6 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold pg-text-primary">Log & Video Finder</h3>
                        
                        <!-- Actions -->
                        <div class="w-full md:w-auto flex items-center space-x-3">
                            <!-- Search Form -->
                            <div class="w-full md:w-72 relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 pg-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text" id="logSearchInput" onkeyup="handleSearchLogs(this.value)" placeholder="Cari Order ID / Resi..." class="pg-input w-full border rounded-xl pl-10 pr-4 py-2 text-xs">
                            </div>
                            
                            <!-- Export Button -->
                            <a href="{{ route('api.logs.export') }}" target="_blank" class="pg-badge-emerald flex items-center space-x-2 px-3 py-2 rounded-xl text-xs font-semibold transition-all hover:opacity-80">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Export CSV</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Table Logs -->
                    <div class="overflow-x-auto">
                        <table class="pg-table w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="font-semibold uppercase tracking-wider">
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
                    <div id="logsPagination" class="pg-pagination flex justify-between items-center text-xs pt-4">
                        <!-- Dynamic pagination buttons -->
                    </div>
                </div>
            </section>
            
            <!-- ================= TAB: SETTINGS ================= -->
            <section id="tab-settings" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column: Camera Settings -->
                    <div class="pg-card rounded-2xl border p-6 space-y-6">
                        <div class="flex items-center space-x-2.5">
                            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-sm font-semibold pg-text-primary">Hardware & Camera Settings</h3>
                        </div>
                        
                        <!-- Camera selector -->
                        <div class="space-y-2">
                            <label for="cameraSelect" class="text-xs pg-text-muted font-medium">Select Camera Device</label>
                            <select id="cameraSelect" onchange="handleCameraDeviceChange(this.value)" class="pg-input w-full border rounded-xl px-3.5 py-2.5 text-xs">
                                <option value="">Loading cameras...</option>
                            </select>
                            <p class="text-[10px] pg-text-faint">Pilih kamera yang dipasang di atas meja packing. Perangkat akan disimpan di memori browser.</p>
                        </div>
                        
                        <!-- Staff Name config -->
                        <div class="space-y-2">
                            <label for="staffNameInput" class="text-xs pg-text-muted font-medium">Operator / Staff Name</label>
                            <input type="text" id="staffNameInput" value="Default Staff" onchange="localStorage.setItem('packguard_staff_name', this.value)" class="pg-input w-full border rounded-xl px-3.5 py-2.5 text-xs">
                            <p class="text-[10px] pg-text-faint">Nama staf yang sedang aktif di workstation ini (dicatat ke metadata log).</p>
                        </div>
                    </div>
                    
                    <!-- Right Column: Storage & Retention -->
                    <div class="space-y-6">
                        <!-- Theme Settings -->
                        <div class="pg-card rounded-2xl border p-6 space-y-6">
                            <div class="flex items-center space-x-2.5">
                                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                <h3 class="text-sm font-semibold pg-text-primary">Appearance Settings</h3>
                            </div>
                            
                            <div class="space-y-3">
                                <label class="text-xs pg-text-muted font-medium">Application Theme</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="toggleTheme('dark')" class="pg-btn-primary flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-semibold" id="theme-dark-btn">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                        <span>Dark Mode</span>
                                    </button>
                                    <button onclick="toggleTheme('light')" class="pg-btn-primary flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-semibold" id="theme-light-btn">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.728 12.728L5.657 5.657" /></svg>
                                        <span>Light Mode</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pg-card rounded-2xl border p-6 space-y-6">
                            <div class="flex items-center space-x-2.5">
                                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <h3 class="text-sm font-semibold pg-text-primary">Storage & Auto Retention</h3>
                            </div>
                        
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="retentionDaysInput" class="text-xs pg-text-muted font-medium">Auto Delete Older Than (Days)</label>
                                <div class="flex space-x-3">
                                    <input type="number" id="retentionDaysInput" value="30" min="1" class="pg-input w-32 border rounded-xl px-3 py-2 text-xs">
                                    <button onclick="triggerRetentionCleanup()" class="pg-btn-danger font-semibold px-4 py-2 rounded-xl text-xs">
                                        Jalankan Pembersihan Manual
                                    </button>
                                </div>
                                <p class="text-[10px] pg-text-faint">Sistem akan menghapus log dan file video yang usianya melebihi hari yang ditentukan untuk menghemat kapasitas HDD lokal.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- VIDEO PLAYER INLINE MODAL -->
    <div id="videoModal" class="pg-modal-bg fixed inset-0 backdrop-blur-md flex items-center justify-center z-50 hidden transition-opacity duration-300 opacity-0">
        <!-- Close overlay -->
        <div class="absolute inset-0 cursor-pointer" onclick="closeVideoModal()"></div>
        
        <div class="pg-modal-card relative w-full max-w-3xl mx-4 border rounded-2xl overflow-hidden shadow-2xl z-10 flex flex-col">
            <!-- Modal Header -->
            <div class="pg-modal-header px-5 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold pg-text-primary" id="videoModalTitle">Video Playback</h3>
                    <p class="text-[10px] pg-text-faint font-medium" id="videoModalSubtitle">Viewing proof file</p>
                </div>
                <button onclick="closeVideoModal()" class="pg-text-muted hover:pg-text-primary transition-all pg-card-inset border rounded-lg p-1.5">
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
