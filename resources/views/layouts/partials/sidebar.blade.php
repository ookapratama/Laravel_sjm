<div class="sidebar-wrapper scrollbar scrollbar-inner">
  <div class="sidebar-content">
    <ul class="nav nav-secondary">
      {{-- DASHBOARD --}}
      <li class="nav-item">
        <a href="/{{ Auth::user()->role }}">
          <i class="fas fa-home"></i>
          <p>Dashboard</p>
        </a>
      </li>

      {{-- SECTION HEADER --}}
      <li class="nav-section">
        <span class="sidebar-mini-icon">
          <i class="fa fa-ellipsis-h"></i>
        </span>
        <h4 class="text-section">Menu Utama</h4>
      </li>

      @php $role = auth()->user()->role; @endphp

      @switch($role)

      {{-- SUPER ADMIN --}}
      @case('super-admin')
        {{-- Manajemen --}}
    <li class="nav-item">
      <a data-bs-toggle="collapse" href="#manajemen">
        <i class="fas fa-users-cog"></i>
        <p>Manajemen</p>
        <span class="caret"></span>
      </a>
      <div class="collapse" id="manajemen">
        <ul class="nav nav-collapse">
          <li><a href="/data-member"><span class="sub-item">Data Member</span></a></li>
        </ul>
      </div>
    </li>

    {{-- Jaringan --}}
    <li class="nav-item">
      <a href="tree">
        <i class="fas fa-sitemap"></i>
        <p>Jaringan</p>
      </a>
    </li>

    {{-- Bonus --}}
    <li class="nav-item">
      <a data-bs-toggle="collapse" href="#bonus">
        <i class="fas fa-hand-holding-usd"></i>
        <p>Bonus</p>
        <span class="caret"></span>
      </a>
      <div class="collapse" id="bonus">
        <ul class="nav nav-collapse">
          <li><a href="/bonus"><span class="sub-item">Bonus Pasangan</span></a></li>
        </ul>
      </div>
    </li>
    {{-- Pre-register --}}
    <li class="nav-item">
      <a href="pre-register">
        <i class="fas fa-user-check"></i>
        <p>Pre-register</p>
      </a>
    </li>

    {{-- Penarikan --}}
    <li class="nav-item">
      <a href="super-admin/withdraws">
        <i class="fas fa-wallet"></i>
        <p>Penarikan</p>
      </a>
    </li>

    {{-- Laporan Keuangan --}}
    <li class="nav-item">
      <a href="report">
        <i class="fas fa-file-invoice-dollar"></i>
        <p>Laporan Keuangan</p>
      </a>
    </li>

    {{-- Bonus Saya --}}
    <li class="nav-item">
      <a href="bonus">
        <i class="fas fa-money-check-alt"></i>
        <p>Bonus Saya</p>
      </a>
    </li>

    {{-- Pengaturan --}}
    <li class="nav-item">
      <a data-bs-toggle="collapse" href="#bonustetting">
        <i class="fas fa-cogs"></i>
        <p>Pengaturan</p>
        <span class="caret"></span>
      </a>
      <div class="collapse" id="bonustetting">
        <ul class="nav nav-collapse">
          <li><a href="bonus-settings"><span class="sub-item">Bonus</span></a></li>
          <li><a href="management"><span class="sub-item">Hak Ases</span></a></li>
        </ul>
      </div>
    </li>


        @break

      {{-- ADMIN --}}
      @case('admin')
       <li class="nav-item"><a href="/tree"><i class="fas fa-sitemap"></i><p>Jaringan Saya</p></a></li>
        <li class="nav-item"><a href="/bonus"><i class="fas fa-money-check-alt"></i><p>Bonus Saya</p></a></li>
        <li class="nav-item"><a href="/admin/withdraw"><i class="fas fa-credit-card"></i><p>Penarikan Bonus</p></a></li>
        {{-- <li class="nav-item"><a href="/orders"><i class="fas fa-cart-plus"></i><p>Repeat Order</p></a></li> --}}
        <li class="nav-item"><a href="/notifications"><i class="fas fa-bell"></i><p>Notifikasi</p></a></li>
        <li class="nav-item"><a href="/help"><i class="fas fa-headset"></i><p>Bantuan</p></a></li>
        <li class="nav-item"><a href="/admin/withdraws"><i class="fas fa-wallet"></i><p>Permintaan Withdraw</p></a></li>
        <li class="nav-item"><a href="/data-member"><i class="fas fa-users"></i><p>Data Member</p></a></li>
        <li class="nav-item"><a href="/admin/pre-register"><i class="fas fa-user-check"></i><p>Pre-Registrasi</p></a></li>
        <li class="nav-item"><a href="/admin/pin-requests"><i class="fas fa-user-check"></i><p>Pin Aktivasi</p></a></li>
        <li class="nav-item"><a href="/produk"><i class="fas fa-box"></i><p>Produk</p></a></li>
        @break

      {{-- FINANCE --}}
      @case('finance')
          <li class="nav-item">
      <a data-bs-toggle="collapse" href="#manajemen">
        <i class="fas fa-users-cog"></i>
        <p>Manajemen Member</p>
        <span class="caret"></span>
      </a>
      <div class="collapse" id="manajemen">
        <ul class="nav nav-collapse">
          <li><a href="/finance/pre-registrations"><span class="sub-item">Member Pre-Registrasi</span></a></li>
          <li><a href="/finance/pin-requests"><span class="sub-item">Pin Aktivasi</span></a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
        <a href="/finance/withdraws">
            <i class="fas fa-wallet"></i>
            <p>Permintaan Withdraw</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="/report">
            <i class="fas fa-file-invoice-dollar"></i>
            <p>Laporan Keuangan</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="/bank-accounts">
            <i class="fas fa-university"></i>
            <p>Rekening Perusahaan</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="{{ route('finance.cashflow') }}">
            <i class="fas fa-chart-line"></i>
            <p>Ringkasan Arus Kas</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="{{ route('finance.bonus-rekap') }}">
            <i class="fas fa-exchange-alt"></i>
            <p>Rekap Bonus Pairing vs RO</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="{{ route('finance.poin-reward') }}">
            <i class="fas fa-gift"></i>
            <p>Status Reward Member</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="{{ route('finance.target') }}">
            <i class="fas fa-bullseye"></i>
            <p>Target vs Realisasi</p>
        </a>
    </li>

    <li class="nav-item">
        <a href="{{ route('finance.growth') }}">
            <i class="fas fa-users"></i>
            <p>Pertumbuhan Member</p>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('finance.topbonus') }}">
            <i class="fas fa-crown"></i>
            <p>Top 10 Bonus Terbesar</p>
        </a>
    </li>
@break


      {{-- MEMBER --}}
      @case('member')
        <li class="nav-item"><a href="/tree"><i class="fas fa-sitemap"></i><p>Jaringan Saya</p></a></li>
        <li class="nav-item"><a href="/bonus"><i class="fas fa-money-check-alt"></i><p>Bonus Saya</p></a></li>
        <li class="nav-item"><a href="/member/withdraw"><i class="fas fa-credit-card"></i><p>Penarikan Bonus</p></a></li>
        <li class="nav-item"><a href="member/pins"><i class="fas fa-cart-plus"></i><p>Pin Aktivasi</p></a></li>
        <li class="nav-item"><a href="/notifications"><i class="fas fa-bell"></i><p>Notifikasi</p></a></li>
        <li class="nav-item"><a href="/help"><i class="fas fa-headset"></i><p>Bantuan</p></a></li>
        @break

      @endswitch

      {{-- LOGOUT --}}
      <li class="nav-item">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          <i class="fas fa-sign-out-alt"></i>
          <p>Logout</p>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          @csrf
        </form>
      </li>
    </ul>
  </div>
</div>
