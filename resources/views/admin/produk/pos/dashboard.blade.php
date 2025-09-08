@extends('layouts.app')

@section('title', 'Dashboard POS')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Dashboard POS</h3>
                <p class="mb-2">Kelola pemberian produk untuk member yang menggunakan PIN</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.pos.history') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-history"></i> History
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-primary">
                    <i class="fas fa-box"></i> Kelola Produk
                </a>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">PIN Menunggu</p>
                                    <h4 class="card-title">{{ $stats['pending_pins'] }}</h4>
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
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Session Aktif</p>
                                    <h4 class="card-title">{{ $stats['active_sessions'] }}</h4>
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
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Selesai Hari Ini</p>
                                    <h4 class="card-title">{{ $stats['completed_today'] }}</h4>
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
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Nilai Hari Ini</p>
                                    <h4 class="card-title">Rp {{ number_format($stats['total_value_today'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PIN yang Menunggi Ditangani -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">PIN Menunggu Ditangani</div>
                        <div class="card-tools">
                            <span class="badge badge-warning">{{ $pendingPins->count() }} PIN</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($pendingPins->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PIN Code</th>
                                            <th>Member</th>
                                            <th>Bagan</th>
                                            <th>Budget</th>
                                            <th>Digunakan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingPins as $pin)
                                            <tr>
                                                <td><code>{{ $pin->code }}</code></td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $pin->usedBy->name ?? 'N/A' }}</strong>
                                                        <br><small class="text-muted">{{ $pin->usedBy->email ?? '' }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $pin->bagan == 1 ? 'secondary' : ($pin->bagan == 2 ? 'primary' : 'success') }}">
                                                        {{ $pin->bagan == 1 ? 'Basic' : ($pin->bagan == 2 ? 'Premium' : 'VIP') }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <strong>Rp {{ number_format($pin->price, 0, ',', '.') }}</strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $pin->used_at->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="startPosSession({{ $pin->id }})">
                                                        <i class="fas fa-shopping-cart"></i> Mulai POS
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">Semua PIN sudah ditangani</h5>
                                <p class="text-muted">Tidak ada PIN yang menunggu untuk ditangani</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Session Aktif -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Session Aktif</div>
                        <div class="card-tools">
                            <span class="badge badge-info">{{ $activeSessions->count() }} Session</span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        @if($activeSessions->count() > 0)
                            @foreach($activeSessions as $session)
                                <div class="card border-left-primary mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">{{ $session->activationPin->usedBy->name ?? 'N/A' }}</h6>
                                                <small class="text-muted">PIN: {{ $session->activationPin->code }}</small>
                                            </div>
                                            <span class="badge badge-info">{{ $session->items->count() }} item</span>
                                        </div>
                                        <div class="progress mb-2" style="height: 5px;">
                                            <div class="progress-bar bg-primary" 
                                                 style="width: {{ $session->total_budget > 0 ? ($session->used_budget / $session->total_budget) * 100 : 0 }}%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Rp {{ number_format($session->used_budget, 0, ',', '.') }} / 
                                                Rp {{ number_format($session->total_budget, 0, ',', '.') }}
                                            </small>
                                            <a href="{{ route('admin.pos.session', $session->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-coffee fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada session aktif</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startPosSession(pinId) {
            Swal.fire({
                title: 'Mulai Session POS?',
                text: 'Anda akan memulai session POS untuk PIN ini',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Mulai',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('admin.pos.start-session', '') }}/${pinId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                window.location.href = data.redirect_url;
                            });
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Terjadi kesalahan sistem', 'error');
                    });
                }
            });
        }

        // Auto refresh setiap 30 detik
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>

    <style>
        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }
        
        .card-stats {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .icon-big {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .bubble-shadow-small {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection