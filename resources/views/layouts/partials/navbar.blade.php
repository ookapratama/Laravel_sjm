<div class="main-header">
  <div class="main-header-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <a href="{{ url('/') }}" class="logo">
        <img src="{{ asset('images/sairbeauty-logo.png') }}" alt="Sair Beauty" class="navbar-brand" height="40" />
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
  </div>

  <!-- Navbar Header -->
  <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
      <!-- Search (desktop only) -->
      <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
        <div class="input-group">
          <div class="input-group-prepend">
            <button type="submit" class="btn btn-search pe-1">
              <i class="fa fa-search search-icon"></i>
            </button>
          </div>
          <input type="text" placeholder="Search ..." class="form-control" />
        </div>
      </nav>

      <!-- Topbar Right -->
      <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
        <!-- Mobile search -->
        <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
            <i class="fa fa-search"></i>
          </a>
          <ul class="dropdown-menu dropdown-search animated fadeIn">
            <form class="navbar-left navbar-form nav-search">
              <div class="input-group">
                <input type="text" placeholder="Search ..." class="form-control" />
              </div>
            </form>
          </ul>
        </li>

        <!-- Messages -->
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa fa-envelope"></i>
          </a>
          <ul class="dropdown-menu messages-notif-box animated fadeIn" aria-labelledby="messageDropdown">
            <li>
              <div class="dropdown-title d-flex justify-content-between align-items-center">
                Messages <a href="#" class="small">Mark all as read</a>
              </div>
            </li>
            <li>
              <div class="message-notif-scroll scrollbar-outer">
                <div class="notif-center">
                  <a href="#">
                    <div class="notif-img">
                      <img src="{{ asset('assets/img/mlane.jpg') }}" alt="Img Profile" />
                    </div>
                    <div class="notif-content">
                      <span class="subject">John Doe</span>
                      <span class="block">Ready for the meeting today...</span>
                      <span class="time">12 minutes ago</span>
                    </div>
                  </a>
                </div>
              </div>
            </li>
            <li>
              <a class="see-all" href="javascript:void(0);">See all messages<i class="fa fa-angle-right"></i></a>
            </li>
          </ul>
        </li>

        <!-- Notifications -->
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fa fa-bell"></i>
            <span class="notification">1</span>
          </a>
          <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
            <li>
              <div class="dropdown-title">You have 4 new notification</div>
            </li>
            <li>
              <div class="notif-scroll scrollbar-outer">
                <div class="notif-center">
                  <a href="#">
                    <div class="notif-icon notif-primary"><i class="fa fa-user-plus"></i></div>
                    <div class="notif-content">
                      <span class="block">New user registered</span>
                      <span class="time">5 minutes ago</span>
                    </div>
                  </a>
                </div>
              </div>
            </li>
            <li>
              <a class="see-all" href="javascript:void(0);">See all notifications<i class="fa fa-angle-right"></i></a>
            </li>
          </ul>
        </li>

        <!-- Quick Actions -->
        <li class="nav-item topbar-icon dropdown hidden-caret">
          <a class="nav-link" data-bs-toggle="dropdown" href="#"><i class="fas fa-layer-group"></i></a>
          <div class="dropdown-menu quick-actions animated fadeIn">
            <div class="quick-actions-header">
              <span class="title mb-1">Quick Actions</span>
              <span class="subtitle op-7">Shortcuts</span>
            </div>
            <div class="quick-actions-scroll scrollbar-outer">
              <div class="quick-actions-items row m-0">
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
                    <div class="avatar-item bg-warning rounded-circle">
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
        </li>

        <!-- Profile -->
        <li class="nav-item topbar-user dropdown hidden-caret">
          <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#">
            <div class="avatar-sm">
              <img src="{{ asset('assets/img/profile.jpg') }}" alt="..." class="avatar-img rounded-circle" />
            </div>
            <span class="profile-username">
              <span class="op-7">Hi, {{ Auth::user()->name }}</span>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-user animated fadeIn">
            <div class="dropdown-user-scroll scrollbar-outer">
              <li>
                <div class="user-box">
                  <div class="avatar-lg">
                    <img src="{{ asset('assets/img/profile.jpg') }}" alt="image profile" class="avatar-img rounded" />
                  </div>
                  <div class="u-text">
                    <h4>{{ Auth::user()->name }}</h4>
                    <a href="#" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                  </div>
                </div>
              </li>
              <li><div class="dropdown-divider"></div></li>
              <li class="nav-item">
                <a href="{{ route('profile.index') }}">
                    <i class="fas fa-user-cog"></i>
                    <p>Profil Akun</p>
                </a>
            </li>
   
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item text-danger">
                    <i class="icon-power"></i> Logout
                  </button>
                </form>
              </li>
            </div>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</div>
