<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>PT. SAIR JAYA MANDIRI</title>
  <meta
  content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
  name="viewport"
  />
  <link
  rel="icon"
  href="images/logo.ico"
  type="image/x-icon"
  />
<audio id="notification-sound" src="{{ asset('assets/sound/notify.mp3') }}" preload="auto"></audio>


<!-- Fonts and icons -->
<script src="{{ asset('assets/js/plugin/webfont/webfont.min.js') }}"></script>
<script>
  WebFont.load({
    google: { families: ["Public Sans:300,400,500,600,700"] },
    custom: {
      families: [
        "Font Awesome 5 Solid",
        "Font Awesome 5 Regular",
        "Font Awesome 5 Brands",
        "simple-line-icons",
      ],
      urls: ["{{ asset('assets/css/fonts.min.css') }}"],
    },
    active: function () {
      sessionStorage.fonts = true;
    },
  });
</script>

<!-- CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.min.css') }}" />

<!-- Bootstrap Treeview CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.css">
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

</style>
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
</head>
<body>
 


  <div class="wrapper">
<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
<!-- Logo Header -->
<div class="logo-header" data-background-color="dark">

  <a href="/" class="logo">
    <img
    src="{{ asset('images/logo.png') }}"
    alt="navbar brand"
    class="navbar-brand"
    height="40"
    />
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
    <img
    src="{{ asset('images/logo.png') }}"
    alt="navbar brand"
    class="navbar-brand"
    height="40"
    />
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
<nav
class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
>
<div class="container-fluid">
  <nav
  class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex"
  >
<form id="global-search-form" class="input-group">
  <input
    type="text"
    id="global-search-input"
    placeholder="Search ..."
    class="form-control"
  />
    <div class="input-group-prepend">
    <button type="submit" class="btn btn-search pe-1">
      <i class="fa fa-search search-icon"></i>
    </button>
  </div>
</form>

</nav>

<ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
  <li
  class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none"
  >
  <a
  class="nav-link dropdown-toggle"
  data-bs-toggle="dropdown"
  href="#"
  role="button"
  aria-expanded="false"
  aria-haspopup="true"
  >
  <i class="fa fa-search"></i>
</a>
<ul class="dropdown-menu dropdown-search animated fadeIn">
  <form class="navbar-left navbar-form nav-search">
    <div class="input-group">
      <input
      type="text"
      placeholder="Search ..."
      class="form-control"
      />
    </div>
  </form>
</ul>
</li>

 
<li class="nav-item topbar-icon dropdown hidden-caret">
  <a
  class="nav-link dropdown-toggle"
  href="#"
  id="notifDropdown"
  role="button"
  data-bs-toggle="dropdown"
  aria-haspopup="true"
  aria-expanded="false"
>
  <i class="fa fa-bell"></i>
  <span class="notification" id="notification-count">{{ $notifications->count() }}</span>
</a>

  <ul
    class="dropdown-menu notif-box animated fadeIn"
    aria-labelledby="notifDropdown"
  >
    <li>
      <div class="dropdown-title">
        Terdapat <span id="notification-text-count">{{ $notifications->count() }}</span> notifikasi baru
      </div>
    </li>

    <li>
      <div class="notif-scroll scrollbar-outer">
        <div class="notif-center" id="notification-list">
          @foreach ($notifications as $notif)
        <a href="{{ $notif->url }}" class="notification-item" data-id="{{ $notif->id }}">
          <div class="notif-icon notif-primary">
            <i class="fa fa-user-plus"></i>
          </div>
          <div class="notif-content">
            <span class="block">{{ $notif->message }}</span>
            <span class="time">{{ $notif->created_at->diffForHumans() }}</span>
          </div>
        </a>
        @endforeach
        </div>
      </div>
    </li>

  </ul>
</li>

<li class="nav-item topbar-icon dropdown hidden-caret">
  <a
  class="nav-link"
  data-bs-toggle="dropdown"
  href="#"
  aria-expanded="false"
  >
  <i class="fas fa-layer-group"></i>
</a>
<div class="dropdown-menu quick-actions animated fadeIn">
  <div class="quick-actions-header">
    <span class="title mb-1">Quick Actions</span>
    <span class="subtitle op-7">Shortcuts</span>
  </div>
  <div class="quick-actions-scroll scrollbar-outer">
    <div class="quick-actions-items">
      <div class="row m-0">
        <a class="col-6 col-md-4 p-0" href="#">
          <div class="quick-actions-item">
            <div class="avatar-item bg-danger rounded-circle">
              <i class="far fa-calendar-alt"></i>
            </div>
            <span class="text">Calendar</span>
          </div>
        </a>
        <a class="col-6 col-md-4 p-0" href="#">
          <div class="quick-actions-item">
            <div
            class="avatar-item bg-warning rounded-circle"
            >
            <i class="fas fa-map"></i>
          </div>
          <span class="text">Maps</span>
        </div>
      </a>
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
          <div
          class="avatar-item bg-success rounded-circle"
          >
          <i class="fas fa-envelope"></i>
        </div>
        <span class="text">Emails</span>
      </div>
    </a>
    <a class="col-6 col-md-4 p-0" href="#">
      <div class="quick-actions-item">
        <div
        class="avatar-item bg-primary rounded-circle"
        >
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <span class="text">Invoice</span>
    </div>
  </a>
  <a class="col-6 col-md-4 p-0" href="#">
    <div class="quick-actions-item">
      <div
      class="avatar-item bg-secondary rounded-circle"
      >
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
  <a
  class="dropdown-toggle profile-pic"
  data-bs-toggle="dropdown"
  href="#"
  aria-expanded="false"
  >
  <div class="avatar-sm">
    <img
    src="{{ asset('assets/img/profile.jpg')}}"
    alt="..."
    class="avatar-img rounded-circle"
    />
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
          <img
          src="{{ asset('assets/img/profile.jpg')}}"
          alt="image profile"
          class="avatar-img rounded"
          />
        </div>
        <div class="u-text">
          <h4></h4>
          <p class="text-muted"></p>
          <a
          href="profile.html"
          class="btn btn-xs btn-secondary btn-sm"
          >View Profile</a
          >
        </div>
      </div>
    </li>
    <li>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="{{ route('profile.index') }}">My Profile</a>
      
    <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="#">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn btn-danger"> <i class="icon-power"></i>
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
  

function enableNotification() {
        const sound = document.getElementById('notification-sound');
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch((err) => {
                console.warn('🔇 Autoplay blocked:', err);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const notifBell = document.getElementById('notifDropdown');
        if (notifBell) {
            notifBell.addEventListener('click', () => {
                enableNotification(); // Aktifkan suara saat bell diklik
            });
        }
    });

    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "4000"
    };

    @auth

    // Gunakan var agar tidak bentrok jika ini dimuat ulang oleh layout Blade/AJAX
    var loggedInUserId = '{{ auth()->user()->id }}';
    var userRole = '{{ auth()->user()->role }}';

    // Inisialisasi Pusher
    var pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }
    });

    // ✅ 1. Subscribe ke member-channel untuk update jumlah member
    var publicChannel = pusher.subscribe('member-channel');
    publicChannel.bind('member.updated', function(data) {
    const el = document.getElementById('member-count');
    if (el) {
        el.innerText = data.memberCount;
        console.log('📈 Jumlah member diperbarui:', data.memberCount);
    }
});

    // ✅ 2. Subscribe ke channel private user untuk notifikasi
    var privateChannel = pusher.subscribe(`private-notifications.${loggedInUserId}`);
    privateChannel.bind('pusher:subscription_succeeded', () => {
        console.log('🔐 Terhubung ke private-notifications untuk user', loggedInUserId);
    });

    privateChannel.bind('notification.received', function (data) {
        var notif = data.notification;
        var sound = document.getElementById('notification-sound');
        if (sound) {
            sound.play().catch(e => {
                console.warn('Autoplay blocked:', e);
            });
        }
        // ===== Update badge =====
        var countEl = document.getElementById('notification-count');
        var textCountEl = document.getElementById('notification-text-count');
        if (countEl && textCountEl) {
            var currentCount = parseInt(countEl.innerText) || 0;
            countEl.innerText = ++currentCount;
            textCountEl.innerText = currentCount;
        }

        // ===== Tentukan ikon & judul =====
        var iconClass = 'fa-bell';
        var title = 'Notifikasi Baru';
        switch (notif.type) {
            case 'new_referral':
                iconClass = 'fa-user-plus'; title = 'Referral Baru'; break;
            case 'withdraw_request':
                iconClass = 'fa-money-bill'; title = 'Withdraw Masuk'; break;
            case 'withdraw_approved':
                iconClass = 'fa-check-circle'; title = 'Withdraw Disetujui'; break;
            case 'bonus_received':
                iconClass = 'fa-gift'; title = 'Bonus Masuk'; break;
        }

        // ===== Tambahkan notifikasi baru ke dropdown =====
        var createdAt = dayjs(notif.created_at).fromNow();
        var listEl = document.querySelector('#notification-list');
        if (listEl) {
            var html = `
                <a href="${notif.url}" class="notification-item">
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
        }

        // ===== Tampilkan toastr popup =====
        toastr.info(notif.message, title);
    });

@endauth
function copyReferral() {
    const text = document.getElementById("referralCode").innerText;
    navigator.clipboard.writeText(text).then(function () {
        toastr.success('Kode referral berhasil disalin!');
    }, function (err) {
        toastr.error('Gagal menyalin kode referral.');
    });
}
</script>
@stack('scripts')
</body>
</html>
