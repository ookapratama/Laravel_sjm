@extends('layouts.app')

@section('content')
    @php
        $baganNames = [
            1 => 'Basic',
            2 => 'Starter',
            3 => 'Booster',
            4 => 'Growth',
            5 => 'Champion',
            6 => 'Legacy',
        ];
        $biaya = [
            1 => 750000,
            2 => 1500000,
            3 => 3000000,
            4 => 6000000,
            5 => 12000000,
            6 => 24000000,
        ];
        $userBaganAktif = collect($userBagans)->filter(fn($b) => $b->is_active)->pluck('bagan')->toArray();

        $refCode = auth()->user()->referral_code;
        $refUrl = Route::has('register')
            ? route('register', ['ref' => $refCode])
            : url('/register') . '?ref=' . urlencode($refCode);
    @endphp
<script>
    window.userId = {{ auth()->id() }};
</script>
<div class="page-inner">
            <div class="row">

            <div class="row">
              <div class="col-md-4">
                <div class="card card-primary bg-primary-gradient">
                  <div class="card-body bubble-shadow">
                    <h1>Rp. {{ number_format($bonus_manajemen, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Bonus manajemen</h5>
                    <div class="pull-right">
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card card-info bg-info-gradient">
                  <div class="card-body bubble-shadow">
                    <h1>Rp. {{ number_format($bonus_sjm, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Bonus SJM</h5>
                    <div class="pull-right">
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card card-success bg-success-gradient">
                  <div class="card-body curves-shadow">
                    <h1>Rp. {{ number_format($saldoBonusSJMTersedia, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Bonus SJM tersedia</h5>
                    <div class="pull-right">

                    </div>
                  </div>
                </div>
              </div>
            </div>
             <!-- Referral Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card card-secondary bg-secondary-gradient">
                    <div class="card-body">
                        <div class="row align-items-center g-3">
                            <!-- QR Code di kiri -->
                            <div class="col-auto">
                                <img src="{{ route('member.ref.qr.png') }}" alt="QR Referral"
                                    class="img-fluid rounded shadow-sm" style="width:120px;height:120px;object-fit:cover;">
                                <div class="text-center mt-2">
                                    <a href="{{ route('member.ref.qr.download') }}" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>

                            <!-- Referral Info di kanan -->
                            <div class="col">
                                <h5 class="mb-2 text-white"><b>Kode Referral Anda</b></h5>
                                <div class="mb-2">
                                    <span id="referralCode" class="badge bg-info text-white fs-6 px-3 py-2" role="button"
                                        onclick="copyReferral()">
                                        {{ $refCode ?? 'Tidak ada kode' }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span id="referralLink" class="badge bg-dark text-white px-3 py-2" role="button"
                                        onclick="copyReferralLink()"
                                        style="word-break: break-all; white-space: normal; display: inline-block; max-width: 100%;">
                                        {{ $refUrl }}
                                    </span>
                                </div>
                                <small class="text-white-50 d-block">Klik untuk menyalin ke clipboard</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<div class="col-md-4">
                <div class="card card-success bg-success-gradient">
                  <div class="card-body bubble-shadow">
                    <h1>Rp. {{ number_format($saldoBonusManajemenTersedia, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Saldo Manajemen Tersedia</h5>
                    <div class="pull-right">
                    </div>
                  </div>
                </div>
              </div>
          
        </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-primary bubble-shadow-small"
                        >
                          <i class="fas fa-users"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pengunjung</p>
                          <h4 class="card-title">1,294</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-info bubble-shadow-small"
                        >
                          <i class="fas fa-user-check"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Member</p>
                          <h4 class="card-title" id="member-count">{{ $totalMembers }}</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-success bubble-shadow-small"
                        >
                          <i class="fas fa-luggage-cart"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Penjualan</p>
                          <h4 class="card-title">$ 1,345</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-secondary bubble-shadow-small"
                        >
                          <i class="far fa-check-circle"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pesanan</p>
                          <h4 class="card-title">576</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


          </div>
        </div> 
@push('scripts')

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
            // Copy referral link
        function copyReferralLink() {
            const text = document.getElementById("referralLink").innerText.trim();
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('Link referral berhasil disalin!');
            }, function(err) {
                toastr.error('Gagal menyalin link referral.');
            });
        }
</script>
@endpush
@endsection
