<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>PT. SAIR JAYA MANDIRI</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="images/logo.ico" type="image/x-icon" />
    <audio id="notification-sound" src="{{ asset('assets/sound/notify.mp3') }}" preload="auto"></audio>

    <!-- Fonts and icons -->
    <script src="{{ asset('assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["{{ asset('assets/css/fonts.min.css') }}"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.min.css') }}" />

    <!-- Bootstrap Treeview CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.css">
    <style>
        .notification-item .mark-read-btn {
            position: absolute;
            top: 0.3rem;
            right: 0.5rem;
            color: #aaa;
            border: none;
            background: transparent;
        }

        .notification-item .mark-read-btn:hover {
            color: #e3342f;
        }

        /* ✅ CSS untuk Audio Notification Effects */
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .audio-permission-prompt {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
        }

        .notification-bell-active {
            animation: shake 0.5s ease-in-out 3;
            color: #ff6b6b !important;
        }

        .notification-item {
            transition: background-color 0.3s ease;
        }

        .notification-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
</head>

<body>

    <div id="audio-status" class="audio-status">
        🔊 Audio Ready
    </div>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="dark">
                    <a href="/" class="logo">
                        <img src="{{ asset('images/logo.png') }}" alt="navbar brand" class="navbar-brand"
                            height="40" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
                <!-- End Logo Header -->
            </div>
            @include('layouts.partials.sidebar')
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">
                        <a href="/" class="logo">
                            <img src="{{ asset('images/logo.png') }}" alt="navbar brand" class="navbar-brand"
                                height="40" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <nav
                            class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                            <form id="global-search-form" class="input-group">
                                <input type="text" id="global-search-input" placeholder="Search ..."
                                    class="form-control" />
                                <div class="input-group-prepend">
                                    <button type="submit" class="btn btn-search pe-1">
                                        <i class="fa fa-search search-icon"></i>
                                    </button>
                                </div>
                            </form>
                        </nav>

                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"
                                    role="button" aria-expanded="false" aria-haspopup="true">
                                    <i class="fa fa-search"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-search animated fadeIn" style="min-width:320px">
                                    <form id="navSearchForm" class="navbar-left navbar-form nav-search"
                                        onsubmit="return false;">
                                        <div class="input-group p-2">
                                            <input id="navSearchInput" type="text"
                                                placeholder="Cari ID / username / nama..." class="form-control"
                                                autocomplete="off" />
                                        </div>
                                    </form>
                                    <li id="navSearchResults" class="px-2 pb-2"
                                        style="max-height:320px; overflow:auto;"></li>
                                </ul>
                            </li>

                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-bell"></i>
                                    <span class="notification"
                                        id="notification-count">{{ $notifications->count() }}</span>
                                </a>

                                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                                    <li>
                                        <div class="dropdown-title">
                                            Terdapat <span
                                                id="notification-text-count">{{ $notifications->count() }}</span>
                                            notifikasi baru
                                        </div>
                                    </li>

                                    <li>
                                        <div class="notif-scroll scrollbar-outer">
                                            <div class="notif-center" id="notification-list">
                                                @foreach ($notifications as $notif)
                                                    <a href="{{ $notif->url }}" class="notification-item"
                                                        data-id="{{ $notif->id }}">
                                                        <div class="notif-icon notif-primary">
                                                            <i class="fa fa-user-plus"></i>
                                                        </div>
                                                        <div class="notif-content">
                                                            <span class="block">{{ $notif->message }}</span>
                                                            <span
                                                                class="time">{{ $notif->created_at->diffForHumans() }}</span>
                                                        </div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <i class="fas fa-layer-group"></i>
                                </a>
                                <div class="dropdown-menu quick-actions animated fadeIn">
                                    <div class="quick-actions-header">
                                        <span class="title mb-1">Quick Actions</span>
                                        <span class="subtitle op-7">Shortcuts</span>
                                        <!-- ✅ Audio Debug Button (remove in production) -->
                                        <button class="btn btn-xs btn-outline-primary ms-2"
                                            onclick="window.audioManager?.getStatus() && console.table(window.audioManager.getStatus())">
                                            🔊 Audio Status
                                        </button>
                                    </div>
                                    <div class="quick-actions-scroll scrollbar-outer">
                                        <div class="quick-actions-items">
                                            <div class="row m-0">
                                                <a class="col-6 col-md-4 p-0" href="#">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-info rounded-circle">
                                                            <i class="fas fa-file-excel"></i>
                                                        </div>
                                                        <span class="text">Reports</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-success rounded-circle">
                                                            <i class="fas fa-envelope"></i>
                                                        </div>
                                                        <span class="text">Emails</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-primary rounded-circle">
                                                            <i class="fas fa-file-invoice-dollar"></i>
                                                        </div>
                                                        <span class="text">Invoice</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-secondary rounded-circle">
                                                            <i class="fas fa-credit-card"></i>
                                                        </div>
                                                        <span class="text">Payments</span>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="{{ asset('assets/img/profile.jpg') }}" alt="..."
                                            class="avatar-img rounded-circle" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi, {{ Auth::user()->name }}</span>
                                        <span class="fw-bold"></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="{{ asset('assets/img/profile.jpg') }}"
                                                        alt="image profile" class="avatar-img rounded" />
                                                </div>
                                                <div class="u-text">
                                                    <h4></h4>
                                                    <p class="text-muted"></p>
                                                    <a href="{{ route('profile.index') }}"
                                                        class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{ route('profile.index') }}">My
                                                Profile</a>

                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger"> <i
                                                            class="icon-power"></i>
                                                        Logout
                                                    </button>
                                                </form>
                                            </a>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>

            <div class="container">
                @yield('content')
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <div class="copyright">
                        2025, made with <i class="fa fa-heart heart text-danger"></i> by
                        <a href="">Art Media</a>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End Custom template -->
    </div>


    <!-- Core JS -->
    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

    <!-- Bootstrap Treeview via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>

    <!-- Chart JS -->
    <script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>

    <!-- jQuery Sparkline -->
    <script src="{{ asset('assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>

    <!-- Chart Circle -->
    <script src="{{ asset('assets/js/plugin/chart-circle/circles.min.js') }}"></script>

    <!-- Datatables -->
    <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>

    <!-- Bootstrap Notify -->
    <script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

    <!-- jQuery Vector Maps -->
    <script src="{{ asset('assets/js/plugin/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jsvectormap/world.js') }}"></script>

    <!-- Sweet Alert -->
    <script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <!-- Kaiadmin -->
    <script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/relativeTime.js"></script>
    <script>
        dayjs.extend(dayjs_plugin_relativeTime);
    </script>
    <script>
        // ✅ IMPROVED AUDIO NOTIFICATION SYSTEM
        class NotificationAudioManager {
            constructor() {
                this.audioEnabled = false;
                this.audioUnlocked = false;
                this.audioElement = null;
                this.userHasInteracted = false;
                this.debugMode = true; // Enable untuk debugging
                this.init();
            }

            init() {
                this.audioElement = document.getElementById('notification-sound');

                if (!this.audioElement) {
                    console.warn('🔇 Audio element tidak ditemukan');
                    return;
                }

                // Setup audio element dengan event listeners
                this.setupAudioElement();

                // Setup user interaction listeners
                this.setupInteractionListeners();

                this.log('🎵 NotificationAudioManager initialized');
            }

            setupAudioElement() {
                // Set initial audio properties
                this.audioElement.volume = 1;
                this.audioElement.preload = 'auto';

                // Audio event listeners untuk debugging
                this.audioElement.addEventListener('loadstart', () => {
                    this.log('📥 Audio loading started');
                });

                this.audioElement.addEventListener('loadeddata', () => {
                    this.log('📦 Audio data loaded');
                });

                this.audioElement.addEventListener('canplay', () => {
                    this.log('▶️ Audio can start playing');
                });

                this.audioElement.addEventListener('error', (e) => {
                    console.error('🔴 Audio element error:', e);
                    console.error('Error details:', {
                        error: this.audioElement.error,
                        src: this.audioElement.src,
                        networkState: this.audioElement.networkState,
                        readyState: this.audioElement.readyState
                    });
                });
            }

            setupInteractionListeners() {
                const interactionEvents = ['click', 'touchstart', 'keydown', 'mousedown'];

                const unlockAudio = (event) => {
                    if (!this.userHasInteracted) {
                        this.log(`🎯 User interaction detected: ${event.type}`);
                        this.userHasInteracted = true;
                        this.unlockAudio();
                    }
                };

                interactionEvents.forEach(event => {
                    document.addEventListener(event, unlockAudio, {
                        once: true,
                        passive: true
                    });
                });

                // Special handler for notification bell
                const notifBell = document.getElementById('notifDropdown');
                if (notifBell) {
                    notifBell.addEventListener('click', () => {
                        this.log('🔔 Notification bell clicked');
                        this.enableAudioForSession();
                    });
                }

                
            }

            async unlockAudio() {
                if (this.audioUnlocked || !this.audioElement) {
                    this.log('🔓 Audio already unlocked or element missing');
                    return;
                }

                this.log('🔓 Starting audio unlock process...');

                try {
                    // Check audio element state
                    this.log('📊 Audio element state:', {
                        src: this.audioElement.src,
                        readyState: this.audioElement.readyState,
                        networkState: this.audioElement.networkState,
                        duration: this.audioElement.duration,
                        paused: this.audioElement.paused
                    });

                    // Wait for audio to be ready if needed
                    if (this.audioElement.readyState < 2) { // HAVE_CURRENT_DATA
                        this.log('⏳ Waiting for audio to load...');
                        await this.waitForAudioReady();
                    }

                    // Backup original settings
                    const originalMuted = this.audioElement.muted;
                    const originalVolume = this.audioElement.volume;

                    // Set to silent for unlock
                    this.audioElement.muted = true;
                    this.audioElement.volume = 0;
                    this.audioElement.currentTime = 0;

                    this.log('🤫 Playing silent audio for unlock...');

                    const playPromise = this.audioElement.play();

                    if (playPromise !== undefined) {
                        try {
                            await playPromise;
                            this.log('✅ Silent play successful');

                            // Stop the silent playback immediately
                            this.audioElement.pause();
                            this.audioElement.currentTime = 0;

                        } catch (playError) {
                            this.log('❌ Silent play failed:', playError);
                            throw playError;
                        }
                    } else {
                        this.log('⚠️ Play method returned undefined (older browser)');
                    }

                    // Restore settings
                    this.audioElement.muted = false;
                    this.audioElement.volume = 1;

                    // Mark as unlocked
                    this.audioUnlocked = true;
                    this.audioEnabled = true;

                    this.log('🔊 Audio successfully unlocked!');

                    // Show success notification
                    this.showAudioStatus('🔊 Audio Ready', 'success');

                    if (typeof toastr !== 'undefined') {
                        // toastr.success('🔊 Suara notifikasi aktif', 'Audio Ready', {
                        //     timeOut: 2000,
                        //     progressBar: true
                        // });
                        
                    }

                    // Test with actual notification sound
                    // setTimeout(() => {
                    //     this.testNotificationSound();
                    // }, 500);

                } catch (error) {
                    this.log('⚠️ Audio unlock failed:', error);
                    this.handleUnlockError(error);
                }
            }

            async waitForAudioReady() {
                return new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('Audio load timeout'));
                    }, 5000);

                    const checkReady = () => {
                        if (this.audioElement.readyState >= 2) {
                            clearTimeout(timeout);
                            resolve();
                        } else {
                            setTimeout(checkReady, 100);
                        }
                    };

                    checkReady();
                });
            }

            handleUnlockError(error) {
                console.error('Audio unlock error details:', {
                    name: error.name,
                    message: error.message,
                    code: error.code || 'No code'
                });

                switch (error.name) {
                    case 'NotAllowedError':
                        this.log('🚫 Autoplay blocked - showing permission prompt');
                        this.showAudioPermissionPrompt();
                        break;
                    case 'NotSupportedError':
                        this.log('🔇 Audio format not supported');
                        this.fallbackToVisualNotification();
                        break;
                    case 'AbortError':
                        this.log('⏹️ Audio operation aborted');
                        break;
                    default:
                        this.log('❓ Unknown audio error, falling back to visual');
                        this.fallbackToVisualNotification();
                }

                this.audioEnabled = false;
                this.audioUnlocked = false;
            }

            async testNotificationSound() {
                this.log('🧪 Testing notification sound...');

                try {
                    this.audioElement.currentTime = 0;
                    this.audioElement.volume = 1; // Lower volume for test

                    const testPromise = this.audioElement.play();

                    if (testPromise !== undefined) {
                        await testPromise;
                        this.log('✅ Test sound played successfully');

                        // Stop after brief play
                        setTimeout(() => {
                            this.audioElement.pause();
                            this.audioElement.currentTime = 0;
                            this.audioElement.volume = 1; // Restore volume
                        }, 300);

                    } else {
                        this.log('⚠️ Test play returned undefined');
                    }

                } catch (error) {
                    this.log('❌ Test sound failed:', error);
                    this.audioEnabled = false;
                    this.audioUnlocked = false;
                }
            }

            async playNotificationSound() {
                this.log('🔔 Attempting to play notification sound...');

                if (!this.canPlayAudio()) {
                    this.log('🔇 Cannot play audio, using visual notification');
                    this.showVisualNotification();
                    return false;
                }

                try {
                    // Reset audio to beginning
                    this.audioElement.currentTime = 0;
                    this.audioElement.volume = 1;

                    this.log('🎵 Playing notification sound...');

                    const playPromise = this.audioElement.play();

                    if (playPromise !== undefined) {
                        await playPromise;
                        this.log('✅ Notification sound played successfully');

                        // Show status indicator
                        this.showAudioStatus('🔊 Sound Played', 'success');

                        // Auto-stop after 2 seconds (adjust as needed)
                        setTimeout(() => {
                            if (!this.audioElement.paused) {
                                this.audioElement.pause();
                                this.audioElement.currentTime = 0;
                            }
                        }, 4000);

                        return true;
                    } else {
                        this.log('⚠️ Play promise undefined');
                        return false;
                    }

                } catch (error) {
                    this.log('❌ Failed to play notification sound:', error);
                    this.showAudioStatus('🔇 Sound Failed', 'error');
                    this.showVisualNotification();
                    return false;
                }
            }

            canPlayAudio() {
                const canPlay = this.audioElement &&
                    this.audioEnabled &&
                    this.audioUnlocked &&
                    this.userHasInteracted &&
                    this.audioElement.readyState >= 2;

                this.log('🔍 Can play audio check:', {
                    hasElement: !!this.audioElement,
                    enabled: this.audioEnabled,
                    unlocked: this.audioUnlocked,
                    interacted: this.userHasInteracted,
                    readyState: this.audioElement?.readyState,
                    result: canPlay
                });

                return canPlay;
            }

            showVisualNotification() {
                this.log('👁️ Showing visual notification');
                this.flashBrowserTab();
                this.showVisualIndicator();
            }

            flashBrowserTab() {
                const originalTitle = document.title;
                let flashCount = 0;
                const maxFlash = 6;

                const flashInterval = setInterval(() => {
                    document.title = flashCount % 2 === 0 ? '🔔 Notifikasi Baru!' : originalTitle;
                    flashCount++;

                    if (flashCount >= maxFlash) {
                        clearInterval(flashInterval);
                        document.title = originalTitle;
                    }
                }, 1000);
            }

            showVisualIndicator() {
                const notifBell = document.querySelector('#notifDropdown i');
                if (notifBell) {
                    notifBell.style.animation = 'pulse 1s ease-in-out 3';
                    notifBell.style.color = '#ff6b6b';

                    setTimeout(() => {
                        notifBell.style.color = '';
                    }, 3000);
                }
            }

            showAudioStatus(message, type = 'info') {
                const statusEl = document.getElementById('audio-status');
                if (statusEl) {
                    statusEl.textContent = message;
                    statusEl.className = `audio-status show ${type}`;

                    setTimeout(() => {
                        statusEl.classList.remove('show');
                    }, 2000);
                }
            }

            showAudioPermissionPrompt() {
                // Remove existing prompt
                const existingPrompt = document.querySelector('.audio-permission-prompt');
                if (existingPrompt) {
                    existingPrompt.remove();
                }

                const prompt = document.createElement('div');
                prompt.className = 'audio-permission-prompt';
                prompt.innerHTML = `
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-volume-up me-2 fs-4"></i>
                            <div class="flex-grow-1">
                                <strong>🔊 Aktifkan Suara Notifikasi</strong><br>
                                <small>Browser memblokir audio otomatis. Klik tombol di bawah untuk mengaktifkan.</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-sm me-2" onclick="audioManager.forceUnlockAudio()">
                                <i class="fa fa-volume-up"></i> Aktifkan Suara
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('.audio-permission-prompt').remove()">
                                Nanti Saja
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(prompt);

                // Auto remove after 15 seconds
                setTimeout(() => {
                    if (prompt.parentNode) {
                        prompt.remove();
                    }
                }, 15000);
            }

            async forceUnlockAudio() {
                this.log('🔄 Force unlock requested by user');

                // Remove permission prompt
                const prompt = document.querySelector('.audio-permission-prompt');
                if (prompt) prompt.remove();

                // Reset flags
                this.audioUnlocked = false;
                this.audioEnabled = false;
                this.userHasInteracted = true; // Ensure this is set

                // Try unlock again
                await this.unlockAudio();
            }

            fallbackToVisualNotification() {
                this.log('🔇 Falling back to visual notifications only');
                this.audioEnabled = false;
                this.audioUnlocked = false;

                this.showAudioStatus('🔇 Audio Disabled', 'warning');

                if (typeof toastr !== 'undefined') {
                    toastr.warning('Audio tidak tersedia. Hanya notifikasi visual yang aktif.', 'Audio Disabled', {
                        timeOut: 4000
                    });
                }
            }

            enableAudioForSession() {
                this.log('📝 Enabling audio for session');
                if (!this.audioUnlocked) {
                    this.unlockAudio();
                }
                sessionStorage.setItem('audio_enabled', 'true');
            }

            // Debug logging helper
            log(...args) {
                if (this.debugMode) {
                    console.log('[AudioManager]', ...args);
                }
            }

            // Public method to get status
            getStatus() {
                return {
                    audioElement: !!this.audioElement,
                    audioEnabled: this.audioEnabled,
                    audioUnlocked: this.audioUnlocked,
                    userHasInteracted: this.userHasInteracted,
                    readyState: this.audioElement?.readyState,
                    src: this.audioElement?.src,
                    canPlay: this.canPlayAudio()
                };
            }
        }

        // Initialize Audio Manager
        let audioManager;

        // Toastr configuration
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000"
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize audio manager
            audioManager = new NotificationAudioManager();

            // Make it globally accessible for debugging
            window.audioManager = audioManager;

            @auth
            // Pusher configuration
            const loggedInUserId = '{{ auth()->user()->id }}';
            const userRole = '{{ auth()->user()->role }}';

            const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
                cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }
            });

            // Connection event handlers
            pusher.connection.bind('connected', () => {
                console.log('✅ Pusher Connected');
            });

            pusher.connection.bind('disconnected', () => {
                console.warn('❌ Pusher Disconnected');
            });

            pusher.connection.bind('error', (err) => {
                console.error('🔴 Pusher Error:', err);
            });

            // Public channel for member updates
            const publicChannel = pusher.subscribe('member-channel');

            publicChannel.bind('pusher:subscription_succeeded', () => {
                console.log('📡 Subscribed to member-channel');
            });

            publicChannel.bind('member.updated', function(data) {
                const el = document.getElementById('member-count');
                if (el) {
                    el.innerText = data.memberCount;
                    console.log('📈 Member count updated:', data.memberCount);
                }
            });

            // Private channel for notifications
            const privateChannel = pusher.subscribe(`private-notifications.${loggedInUserId}`);

            privateChannel.bind('pusher:subscription_succeeded', () => {
                console.log('🔐 Connected to private notifications for user', loggedInUserId);
            });

            // ✅ ENHANCED NOTIFICATION HANDLER
            privateChannel.bind('notification.received', async function(data) {
                console.log('🔔 Notification received:', data);

                const notif = data.notification;

                // ✅ PRIORITAS 1: Coba putar suara
                const soundPlayed = await audioManager.playNotificationSound();
                console.log('🔊 Sound played:', soundPlayed);

                // ✅ PRIORITAS 2: Update UI
                updateNotificationCounts();
                addNotificationToList(notif);
                showNotificationToast(notif);

                // ✅ PRIORITAS 3: Log untuk debugging
                console.log('📊 Audio Manager Status:', audioManager.getStatus());
            });
        @endauth
        });

        // Helper functions
        function updateNotificationCounts() {
            const countEl = document.getElementById('notification-count');
            const textCountEl = document.getElementById('notification-text-count');

            if (countEl && textCountEl) {
                const currentCount = parseInt(countEl.innerText) || 0;
                const newCount = currentCount + 1;

                countEl.innerText = newCount;
                textCountEl.innerText = newCount;

                countEl.style.animation = 'pulse 0.5s ease-in-out';
            }
        }

        function addNotificationToList(notif) {
            const listEl = document.querySelector('#notification-list');
            if (!listEl) return;

            const iconClass = getNotificationIcon(notif.type);
            const createdAt = dayjs(notif.created_at).fromNow();

            const html = `
                <a href="${notif.url}" class="notification-item new" data-id="${notif.id || ''}">
                    <div class="notif-icon notif-primary">
                        <i class="fa ${iconClass}"></i>
                    </div>
                    <div class="notif-content">
                        <span class="block">${notif.message}</span>
                        <span class="time">${createdAt}</span>
                    </div>
                </a>
            `;

            listEl.insertAdjacentHTML('afterbegin', html);

            // Remove 'new' class after animation
            setTimeout(() => {
                const newItem = listEl.querySelector('.notification-item.new');
                if (newItem) {
                    newItem.classList.remove('new');
                }
            }, 3000);
        }

        function showNotificationToast(notif) {
            const title = getNotificationTitle(notif.type);

            toastr.options.onclick = function() {
                if (notif.url) {
                    window.location.href = notif.url;
                }
            };

            toastr.info(notif.message, title);
            setTimeout(() => {
                location.reload()
            }, 4000)
        }

        function getNotificationIcon(type) {
            const icons = {
                'new_referral': 'fa-user-plus',
                'withdraw_request': 'fa-money-bill',
                'withdraw_approved': 'fa-check-circle',
                'bonus_received': 'fa-gift',
                'finance_approved': 'fa-check-circle',
                'finance_rejected': 'fa-times-circle',
                'admin_generate': 'fa-check-circle',
                'member_request_bonus': 'fa-money-bill',
                'pairing_downline': 'fa-users',
                'new_member_registered': 'fa-user-check'
            };
            return icons[type] || 'fa-bell';
        }

        function getNotificationTitle(type) {
            const titles = {
                'new_referral': 'Referral Baru',
                'withdraw_request': 'Withdraw Masuk',
                'withdraw_approved': 'Withdraw Disetujui',
                'bonus_received': 'Bonus Masuk',
                'finance_approved': 'Finance menyetujui aktivasi pin',
                'finance_rejected': 'Finance menolak aktivasi pin',
                'admin_generate': 'Finance telah generate pin aktivasi anda',
                'member_request_bonus': 'Member meminta pengajuan penarikan bonus',
                'pairing_downline': 'User berhasil dipasang ke tree',
                'new_member_registered': 'User berhasil register menggunakan Kode Referal dan Pin anda'
            };
            return titles[type] || 'Notifikasi Baru';
        }

        function copyReferral() {
            const text = document.getElementById("referralCode").innerText;
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('Kode referral berhasil disalin!');
            }, function(err) {
                toastr.error('Gagal menyalin kode referral.');
            });
        }

        // Search functionality (unchanged)
        (() => {
            "use strict";

            const SEARCH_URL = "/tree/search";
            const dForm = document.getElementById("global-search-form");
            const dInput = document.getElementById("global-search-input");
            const mForm = document.getElementById("navSearchForm");
            const mInput = document.getElementById("navSearchInput");
            const mResults = document.getElementById("navSearchResults");
            const mMenu = mResults ? mResults.closest(".dropdown-menu") : null;

            let dResults = document.getElementById("global-search-results");
            if (!dResults && dForm) {
                dForm.style.position = "relative";
                dResults = document.createElement("div");
                dResults.id = "global-search-results";
                dResults.className = "dropdown-menu show";
                Object.assign(dResults.style, {
                    position: "absolute",
                    top: "100%",
                    left: "0",
                    minWidth: "320px",
                    maxHeight: "320px",
                    overflow: "auto",
                    display: "none",
                    zIndex: "1051"
                });
                dForm.appendChild(dResults);
            }

            const debounce = (fn, ms = 300) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), ms);
                };
            };
            const toArray = (data) => Array.isArray(data) ? data : (data ? [data] : []);

            function render(container, items) {
                if (!container) return;
                container.innerHTML = items.length ?
                    items.map(u => `
          <a href="#" class="dropdown-item search-item" data-id="${u.id}">
            #${u.id} — ${u.username ?? ""} <small class="text-muted">${u.name ?? ""}</small>
          </a>
        `).join("") :
                    `<div class="text-muted small px-2">Tidak ada hasil</div>`;
            }

            async function doSearch(target, q) {
                if (!q) {
                    if (target === "desktop" && dResults) dResults.style.display = "none";
                    if (target === "mobile" && mResults) {
                        mResults.innerHTML = "";
                        mMenu?.classList.remove("show");
                    }
                    return;
                }
                try {
                    const res = await fetch(`${SEARCH_URL}?` + new URLSearchParams({
                        query: q
                    }), {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const items = toArray(await res.json());

                    if (target === "desktop" && dResults) {
                        render(dResults, items);
                        dResults.style.display = items.length ? "block" : "none";
                    } else if (target === "mobile" && mResults) {
                        render(mResults, items);
                        if (items.length) mMenu?.classList.add("show");
                    }
                } catch (e) {
                    console.warn("[search] gagal fetch:", e);
                }
            }

            function pickFirst(target) {
                const first = target === "desktop" ?
                    dResults?.querySelector(".search-item") :
                    mResults?.querySelector(".search-item");
                if (first) first.click();
            }

            function setRootAndReload(id) {
                window.currentRootId = Number(id);
                if (typeof window.setRoot === "function") {
                    window.setRoot(id);
                    return;
                }
                if (typeof window.loadTree === "function") {
                    window.loadTree();
                    return;
                }
            }

            dForm?.addEventListener("submit", (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            mForm?.addEventListener("submit", (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            dInput?.addEventListener("input", debounce(e => doSearch("desktop", e.target.value.trim()), 250));
            dInput?.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    pickFirst("desktop");
                }
                if (e.key === "Escape" && dResults) dResults.style.display = "none";
            });
            dResults?.addEventListener("click", (e) => {
                const a = e.target.closest(".search-item");
                if (!a) return;
                e.preventDefault();
                setRootAndReload(a.dataset.id);
                if (dInput) dInput.value = "";
                dResults.style.display = "none";
            });

            mInput?.addEventListener("input", debounce(e => doSearch("mobile", e.target.value.trim()), 250));
            mInput?.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    pickFirst("mobile");
                }
                if (e.key === "Escape") {
                    mResults.innerHTML = "";
                    mMenu?.classList.remove("show");
                }
            });
            mResults?.addEventListener("click", (e) => {
                const a = e.target.closest(".search-item");
                if (!a) return;
                e.preventDefault();
                setRootAndReload(a.dataset.id);
                if (mInput) mInput.value = "";
                mResults.innerHTML = "";
                mMenu?.classList.remove("show");
            });

            document.addEventListener("click", (e) => {
                if (dResults && dForm && !dForm.contains(e.target)) dResults.style.display = "none";
            });
        })();
    </script>
    @stack('scripts')

</body>

</html>
