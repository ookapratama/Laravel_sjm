@extends('layouts.app')
@push('styles')
    <style>
        .voucher-card:hover .card {
            transform: translateY(-3px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .voucher-card .card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Button hover effects */
        .voucher-card .btn:hover {
            transform: translateY(-1px);
        }

        /* PIN Transfer Card Styles */
        .pin-transfer-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .pin-transfer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pin-dots" width="15" height="15" patternUnits="userSpaceOnUse"><circle cx="7.5" cy="7.5" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23pin-dots)"/></svg>');
            opacity: 0.3;
        }

        .pin-transfer-card .card-body {
            position: relative;
            z-index: 1;
        }

        .pin-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.3);
        }

        .upline-info {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .pulse-animation {
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
            }

            to {
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.6), 0 0 30px rgba(118, 75, 162, 0.4);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .voucher-card .card-body {
                padding: 1rem !important;
            }

            .voucher-card .h5 {
                font-size: 1.1rem !important;
            }

            .voucher-card h6 {
                font-size: 12px !important;
            }

            .pin-transfer-card {
                margin-bottom: 1rem;
            }
        }

        /* Compact SweetAlert */
        .swal-compact {
            width: 85% !important;
            max-width: 500px !important;
        }
    </style>
@endpush
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

        // Sample data untuk PIN transfer - ganti dengan data dari database
        $pinTransfers = [
            [
                'id' => 1,
                'pin_code' => 'PIN' . strtoupper(substr(md5('1'), 0, 8)),
                'upline_name' => 'Ahmad Suharto',
                'upline_username' => 'ahmad123',
                'bagan_type' => 'Basic',
                'bagan_level' => 1,
                'transfer_date' => now()->subDays(2),
                'status' => 'active',
                'value' => 750000,
            ],
            [
                'id' => 2,
                'pin_code' => 'PIN' . strtoupper(substr(md5('12345'), 0, 8)),
                'upline_name' => 'Ahmad Suharto',
                'upline_username' => 'ahmad123',
                'bagan_type' => 'Basic',
                'bagan_level' => 1,
                'transfer_date' => now()->subDays(2),
                'status' => 'active',
                'value' => 750000,
            ],
            [
                'id' => 3,
                'pin_code' => 'PIN' . strtoupper(substr(md5('54321'), 0, 8)),
                'upline_name' => 'Ahmad Suharto',
                'upline_username' => 'ahmad123',
                'bagan_type' => 'Basic',
                'bagan_level' => 1,
                'transfer_date' => now()->subDays(2),
                'status' => 'active',
                'value' => 750000,
            ],
            // Tambahkan data lain jika ada multiple transfers
        ];
    @endphp

    <div class="modal fade" id="requestPinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('member.pin.request') }}"
                enctype="multipart/form-data" id="pinRequestForm">
                @csrf
                <div class="modal-header bg-dark text-warning">
                    <h5 class="modal-title">Request PIN Aktivasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Jumlah PIN</label>
                            <input type="number" name="qty" class="form-control" min="1" max="100"
                                value="1" required>
                            <div class="form-text">1 PIN = Rp750.000</div>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label d-block">Metode Pembayaran</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input pay-method" type="radio" name="payment_method"
                                    value="qris" id="payQris" required>
                                <label class="form-check-label" for="payQris">QRIS (statis)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input pay-method" type="radio" name="payment_method"
                                    value="transfer" id="payTf">
                                <label class="form-check-label" for="payTf">Transfer Rekening</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input pay-method" type="radio" name="payment_method"
                                    value="cash" id="payCash">
                                <label class="form-check-label" for="payCash">Cash</label>
                            </div>

                            {{-- QRIS statis --}}
                            <div id="qrisSection" class="border rounded p-2 mt-2 d-none">
                                <div class="small mb-2">Scan QR berikut, lalu unggah bukti:</div>
                                <img src="{{ asset('images/qris.jpg') }}" alt="QRIS" style="max-height:160px">
                            </div>

                            {{-- Rekening perusahaan --}}
                            <div id="tfSection" class="border rounded p-2 mt-2 d-none">
                                <div class="small mb-2">Transfer ke rekening perusahaan:</div>
                                <ul class="mb-2 small">
                                    <li>Bank BNI • 1234567890 a.n. PT Sair Jaya Mandiri</li>
                                    <li>Bank BRI • 777888999 a.n. PT Sair Jaya Mandiri</li>
                                </ul>
                                <label class="form-label">No/Ref Transaksi (opsional)</label>
                                <input type="text" name="payment_reference" class="form-control"
                                    placeholder="Mis. No. Ref / berita">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Bukti Pembayaran (foto kamera)</label>
                            <input type="file" name="payment_proof" id="payment_proof" class="form-control"
                                accept="image/*,.pdf" capture="environment" />
                            <div class="form-text">Wajib untuk QRIS/Transfer. JPG/PNG/PDF, maks 300KB.</div>
                            <img id="proof_preview" class="mt-2 d-none" style="max-width: 220px; border-radius: 6px;">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-warning">Kirim Permintaan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Transfer PIN --}}
    <div class="modal fade" id="transferPinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('member.pin.transfer') }}" id="pinTransferForm">
                @csrf
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2"></i>Transfer PIN ke Downline
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="pin_id" id="transferPinId">

                    <!-- PIN Info -->
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-key fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading mb-1">PIN yang akan ditransfer</h6>
                                <div class="mb-1">
                                    <strong>Kode:</strong> <span id="transferPinCode" class="badge bg-dark">-</span>
                                </div>
                                <div>
                                    <strong>Nilai:</strong> <span id="transferPinValue" class="text-success fw-bold">Rp
                                        750.000</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Select Downline -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-users me-2"></i>Pilih Downline Tujuan
                        </label>
                        <select name="downline_id" id="downlineSelect" class="form-select" required>
                            <option value="">-- Pilih Downline --</option>
                            @foreach ($downlines as $downline)
                                <option value="{{ $downline->id }}" data-username="{{ $downline->username }}"
                                    data-name="{{ $downline->name }}">
                                    {{ $downline->name }} ({{ $downline->username }})
                                    -
                                    {{ $downline->position === 'left' ? 'Kiri' : ($downline === 'right' ? 'Kanan' : 'Jaringan belum ada') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Pilih downline yang akan menerima PIN ini</div>
                    </div>

                    <!-- Downline Preview -->
                    <div id="downlinePreview" class="card border-success d-none">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 50px; height: 50px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <h6 class="mb-1" id="previewName">-</h6>
                                    <small class="text-muted">@<span id="previewUsername">-</span></small>
                                    <br>
                                    <small class="badge bg-info" id="previewPosition">-</small>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer Notes -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-comment me-2"></i>Catatan (Opsional)
                        </label>
                        <textarea name="transfer_notes" class="form-control" rows="3"
                            placeholder="Tambahkan catatan untuk downline..."></textarea>
                    </div>

                    <!-- Confirmation -->
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmTransfer" required>
                        <label class="form-check-label" for="confirmTransfer">
                            Saya yakin akan mentransfer PIN ini ke downline yang dipilih.
                            <strong class="text-warning">Aksi ini tidak dapat dibatalkan.</strong>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitTransfer" disabled>
                        <i class="fas fa-paper-plane me-2"></i>Transfer PIN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container-fluid">
        @if (is_null($user->upline_id))
            <div></div>
        @else
            <!-- PIN Transfer Information -->
            {{-- {{dd($downlines)}} --}}
            @if (!empty($downlines))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card pin-transfer-card pulse-animation">
                            <div class="card-body text-white p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                            <i class="fas fa-key text-white" style="font-size: 24px;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold">
                                            <i class="fas fa-gift me-2"></i>PIN Aktivasi Diterima!
                                        </h5>
                                        <p class="mb-0 opacity-80">Anda telah menerima PIN aktivasi dari upline</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="pin-badge">
                                            {{ $downlines[0]['code'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="row">
                                    @foreach ($downlines as $transfer)
                                        <div class="col-md-6 mb-3">
                                            <div class="upline-info p-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="me-3">
                                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 fw-bold">{{ $transfer->owner['name'] }}</h6>
                                                        <small class="opacity-75">Username
                                                            :{{ $transfer->owner['username'] }}</small>
                                                    </div>
                                                </div>

                                                <div class="row text-center">
                                                    <div class="col-6">
                                                        <div class="border-end border-white border-opacity-25">
                                                            <div class="fw-bold">{{ $transfer['bagan'] }}</div>
                                                            <small class="opacity-75">Bagan Level</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fw-bold">Rp
                                                            {{ number_format($transfer['price'], 0, ',', '.') }}</div>
                                                        <small class="opacity-75">Nilai PIN</small>
                                                    </div>
                                                </div>

                                                <hr class="border-white border-opacity-25 my-2">

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="opacity-75">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $transfer['transferred_date'] }}
                                                    </small>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-light btn-sm"
                                                            onclick="copyPinCode('{{ $transfer['code'] }}')">
                                                            <i class="fas fa-copy me-1"></i>Salin
                                                        </button>
                                                        <button class="btn btn-outline-light btn-sm"
                                                            onclick="showPinDetails({{ json_encode($transfer) }})">
                                                            <i class="fas fa-info-circle me-1"></i>Detail
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Action Buttons -->
                                <div class="text-center mt-3">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light" data-bs-toggle="modal"
                                            data-bs-target="#requestPinModal">
                                            <i class="fas fa-play me-2"></i>Order Pin
                                        </button>
                                        <button type="button" class="btn btn-outline-light"
                                            onclick="viewPinHistory({{ json_encode($transfer) }})">
                                            <i class="fas fa-history me-2"></i>Riwayat PIN
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @endif

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-secondary bg-secondary-gradient">
                    <div class="card-body skew-shadow">
                        <h1>Rp. {{ number_format($totalBonusnett, 0, ',', '.') }}</h1>
                        <h5 class="op-8">Bonus Net</h5>
                        <div class="pull-right">
                            <h3 class="fw-bold op-8"></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-secondary bg-secondary-gradient">
                    <div class="card-body bubble-shadow">
                        <h1>Rp. {{ number_format($totalBonus, 0, ',', '.') }}</h1>
                        <h5 class="op-8">Total Bonus</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-secondary bg-secondary-gradient">
                    <div class="card-body curves-shadow">
                        <h1>{{ $leftDownline + $rightDownline }}</h1>
                        <h5 class="op-8">Jumlah Downline</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referral Section -->
        <div class="row">
            <div class="col-md-8">
                <div class="card card-secondary bg-secondary-gradient">
                    <div class="card-body">
                        <div class="row align-items-center g-3">
                            <!-- QR Code di kiri -->
                            <div class="col-auto">
                                <img src="{{ route('member.ref.qr.png') }}" alt="QR Referral"
                                    class="img-fluid rounded shadow-sm"
                                    style="width:120px;height:120px;object-fit:cover;">
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
                                    <span id="referralCode" class="badge bg-info text-white fs-6 px-3 py-2"
                                        role="button" onclick="copyReferral()">
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

                <div class="mt-0">
                    <div class="voucher-card">
                        <div class="card border-0 shadow-sm overflow-hidden"
                            style="background: linear-gradient(135deg, #0e0b01 0%, #efa906 50%, hsl(42, 97%, 48%) 100%); 
                            border-radius: 12px;">

                            <!-- Subtle Background Pattern -->
                            <div class="position-absolute w-100 h-100"
                                style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"dots\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\"><circle cx=\"10\" cy=\"10\" r=\"2\" fill=\"rgba(255,255,255,0.05)\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23dots)\"/></svg>'
                            </div>

                            <div class="card-body
                                text-white p-3 position-relative">
                                <div class="row align-items-center">
                                    <!-- Left Section - Icon -->
                                    <div class="col-auto">
                                        <div class="d-inline-block p-2 rounded-circle">
                                            {{-- <i class="fas fa-kaaba" style="font-size: 20px; color: #ffd700;"></i> --}}
                                            <img src="{{ asset('images/religion.png') }}" alt=""
                                                class="img-fluid" width="100">
                                        </div>
                                    </div>

                                    <!-- Center Section - Main Content -->
                                    <div class="col">
                                        <div class="row align-items-center">
                                            <div class="col-md-7">
                                                <h6 class="fw-bold mb-1" style="color: #ffd700; font-size: 30px;">
                                                    VOUCHER UMROH
                                                </h6>
                                                <div class="mb-1">
                                                    <span class="h5 fw-bold mb-0" style="color: #ffd700;">
                                                        Rp 1.000.000
                                                    </span>
                                                </div>
                                                {{-- <small class="opacity-75" style="font-size: 11px;">
                                                    <i class="fas fa-calendar me-1"></i>Berlaku hingga
                                                    {{ now()->addYear()->format('d M Y') }}
                                                </small> --}}
                                            </div>

                                            <div class="col-md-5">
                                                <!-- Voucher Code -->
                                                <div class="mb-2">
                                                    <div class="bg-white text-dark px-2 py-1 rounded text-center">
                                                        <small class="fw-bold d-block"
                                                            style="font-size: 9px; letter-spacing: 0.5px;">
                                                            KODE VOUCHER
                                                        </small>
                                                        <div class="fw-bold"
                                                            style="font-size: 11px; letter-spacing: 1px; font-family: monospace; color: #1a5d1a;">
                                                            UMROH{{ strtoupper(substr(auth()->user()->username ?? 'USER', 0, 4)) }}2025
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="d-grid gap-1">
                                                    <button class="btn btn-light btn-sm py-1" style="font-size: 11px;"
                                                        onclick="copyVoucherCode('UMROH{{ strtoupper(substr(auth()->user()->username ?? 'USER', 0, 4)) }}2025')">
                                                        <i class="fas fa-copy me-1"></i>Salin Kode
                                                    </button>
                                                    <button class="btn btn-outline-light btn-sm py-1"
                                                        style="font-size: 11px;" onclick="showVoucherDetails()">
                                                        <i class="fas fa-info-circle me-1"></i>Detail
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Info -->
                                <div class="row mt-2 pt-2" style="border-top: 1px solid rgba(255,255,255,0.2);">
                                    <div class="col-md-6">
                                        <small class="opacity-75" style="font-size: 10px;">
                                            <i class="fas fa-tag me-1"></i>
                                            Min. pembelian: Rp 750.000
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="opacity-75" style="font-size: 10px;">
                                            <i class="fas fa-check me-1"></i>
                                            Berlaku untuk semua paket
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Minimal Decorative Element -->
                            <div class="position-absolute" style="bottom: 10px; left: 10px; opacity: 0.2;">
                                <i class="fas fa-crescent-moon" style="font-size: 14px; color: #ffd700;"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- QR Code Detail -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">QR Code Detail</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ route('member.ref.qr.png') }}" alt="QR Referral" class="img-fluid mb-3"
                            style="max-width: 200px;">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('member.ref.qr.download') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i> PNG
                            </a>
                        </div>
                        <small class="text-muted d-block mt-2">Scan untuk registrasi</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Downline Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card card-black">
                    <div class="card-body">
                        <h4 class="text-center text-white">Downline Kiri <strong>{{ $leftDownline }}</strong></h4>
                        <p class="text-center text-white">
                            {{ optional($user->getLeftChild())->name ?? 'Belum Ada' }}
                        </p>
                    </div>
                </div>

            </div>
            <div class="col-md-6">
                <div class="card card-black">
                    <div class="card-body">
                        <h4 class="text-center text-white">Downline Kanan <strong>{{ $rightDownline }}</strong></h4>
                        <p class="text-center text-white">
                            {{ optional($user->getRightChild())->name ?? 'Belum Ada' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagan Status & Upgrade -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-black">
                    <div class="card-header text-white">
                        <h4 class="mb-0">🔰 Status Bagan & Upgrade</h4>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($baganNames as $bagan => $label)
                                @php
                                    $allocated =
                                        optional(collect($userBagans)->firstWhere('bagan', $bagan))
                                            ->allocated_from_bonus ?? 0;
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Bagan {{ $bagan }}: {{ $label }}</strong>
                                        <br>
                                        <small class="text-muted">Biaya:
                                            Rp{{ number_format($biaya[$bagan], 0, ',', '.') }}</small>
                                        @if ($allocated > 0)
                                            <br>
                                            <small class="text-info">Bonus tertahan:
                                                Rp{{ number_format($allocated, 0, ',', '.') }}</small>
                                        @endif
                                    </div>

                                    @if (in_array($bagan, $userBaganAktif))
                                        <span class="badge bg-success">✅ Aktif</span>
                                    @else
                                        <button type="button" class="btn btn-warning btn-sm"
                                            data-bagan="{{ $bagan }}" data-nama="{{ $label }}"
                                            data-biaya="{{ $biaya[$bagan] }}" data-allocated="{{ $allocated }}"
                                            onclick="openUpgradeModal(this)">
                                            <i class="fas fa-arrow-up"></i> Upgrade
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upgrade Modal -->
    <div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formUpgradeBagan">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title text-dark">
                            <i class="fas fa-arrow-up"></i> Upgrade Bagan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="baganId" name="bagan">

                        <!-- Informasi Bagan -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Detail Upgrade</h6>
                            <p class="mb-1">Target: <strong id="namaBagan">Bagan X</strong></p>
                            <p class="mb-1">Biaya: <strong id="biayaBagan">Rp</strong></p>
                            <hr>
                            <p class="mb-1">Bonus tertahan: <strong id="allocatedBonusText">Rp 0</strong></p>
                            <p class="mb-1">Sisa pembayaran: <strong id="sisaPembayaranText">Rp 0</strong></p>
                            <p class="mb-0">Saldo bonus tersedia:
                                <strong
                                    class="text-success">Rp{{ number_format($saldoBonusTersedia ?? 0, 0, ',', '.') }}</strong>
                            </p>
                        </div>

                        <!-- Metode Pembayaran -->
                        <h6>Pilih Metode Pembayaran:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" value="bonus"
                                id="pakaiBonus" checked>
                            <label class="form-check-label" for="pakaiBonus">
                                <i class="fas fa-wallet text-success"></i> Gunakan saldo bonus
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" value="transfer"
                                id="pakaiTransfer">
                            <label class="form-check-label" for="pakaiTransfer">
                                <i class="fas fa-money-bill-transfer text-primary"></i> Transfer manual
                            </label>
                        </div>

                        <!-- Section Transfer Manual -->
                        <div id="manualTransferSection" style="display: none;" class="mt-3">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle"></i> Transfer Manual
                                </h6>
                                <p class="mb-2">Silakan transfer ke rekening berikut:</p>
                                <div class="bg-light p-3 rounded">
                                    <strong>Bank Mandiri</strong><br>
                                    <strong>1740011176609</strong><br>
                                    a.n. <strong>PT Sair Jaya Mandiri</strong>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="buktiTransfer" class="form-label">
                                    <i class="fas fa-upload"></i> Upload Bukti Transfer
                                </label>
                                <input type="file" name="bukti_transfer" id="buktiTransfer" class="form-control"
                                    accept="image/*">
                                <div class="form-text">Format: JPG, PNG, maksimal 2MB</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-arrow-up"></i> Upgrade Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            // Toggle QRIS/Transfer sections
            document.querySelectorAll('.pay-method').forEach(r => {
                r.addEventListener('change', () => {
                    const v = document.querySelector('.pay-method:checked')?.value;
                    document.getElementById('qrisSection').classList.toggle('d-none', v !== 'qris');
                    document.getElementById('tfSection').classList.toggle('d-none', v !== 'transfer');
                });
            });

            // Client-side compression (≤300KB) & preview
            const input = document.getElementById('payment_proof');
            const preview = document.getElementById('proof_preview');
            const MAX_BYTES = 300 * 1024;
            const MAX_W = 1600;

            input.addEventListener('change', async () => {
                const file = input.files?.[0];
                if (!file) return;

                // preview cepat untuk PDF
                if (file.type === 'application/pdf') {
                    preview.classList.add('d-none');
                    return;
                }

                if (file.type.startsWith('image/')) {
                    try {
                        const compressed = await compressImageFile(file, MAX_BYTES, MAX_W);
                        if (compressed) {
                            preview.src = URL.createObjectURL(compressed);
                            preview.classList.remove('d-none');

                            const dt = new DataTransfer();
                            dt.items.add(compressed);
                            input.files = dt.files;
                        }
                    } catch (e) {
                        console.warn('Compress fail:', e);
                    }
                }
            });

            async function compressImageFile(file, maxBytes, maxW) {
                const dataUrl = await readFileAsDataURL(file);
                const img = await loadImage(dataUrl);

                const scale = Math.min(1, maxW / Math.max(img.width, img.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                let q = 0.9,
                    blob = await toBlob(canvas, 'image/jpeg', q);
                while (blob.size > maxBytes && q > 0.3) {
                    q -= 0.1;
                    blob = await toBlob(canvas, 'image/jpeg', q);
                }
                if (blob.size > maxBytes) {
                    const s2 = 0.85;
                    const tmp = document.createElement('canvas');
                    tmp.width = Math.round(canvas.width * s2);
                    tmp.height = Math.round(canvas.height * s2);
                    tmp.getContext('2d').drawImage(canvas, 0, 0, tmp.width, tmp.height);
                    blob = await toBlob(tmp, 'image/jpeg', 0.8);
                }
                return new File([blob], renameToJpg(file.name), {
                    type: 'image/jpeg'
                });
            }

            function toBlob(canvas, type, quality) {
                return new Promise(res => canvas.toBlob(b => res(b), type, quality));
            }

            function readFileAsDataURL(file) {
                return new Promise((res, rej) => {
                    const fr = new FileReader();
                    fr.onload = () => res(fr.result);
                    fr.onerror = rej;
                    fr.readAsDataURL(file);
                });
            }

            function loadImage(src) {
                return new Promise((res, rej) => {
                    const i = new Image();
                    i.onload = () => res(i);
                    i.onerror = rej;
                    i.src = src;
                });
            }

            function renameToJpg(name) {
                return name.replace(/\.[^.]+$/, '') + '.jpg';
            }

            // Wajib bukti untuk QRIS/Transfer di submit
            document.getElementById('pinRequestForm').addEventListener('submit', (e) => {
                const method = document.querySelector('.pay-method:checked')?.value;
                const file = document.getElementById('payment_proof').files[0];
                if ((method === 'qris' || method === 'transfer') && !file) {
                    e.preventDefault();
                    alert('Bukti pembayaran wajib diunggah untuk QRIS/Transfer.');
                    return;
                }
                if (file && file.size > MAX_BYTES) {
                    e.preventDefault();
                    alert('Ukuran bukti pembayaran melebihi 300KB.');
                }
            });
        })();

        // PIN Transfer functionality
        function openTransferModal(pinId, pinCode) {
            document.getElementById('transferPinId').value = pinId;
            document.getElementById('transferPinCode').textContent = pinCode;

            // Reset form
            document.getElementById('downlineSelect').value = '';
            document.getElementById('downlinePreview').classList.add('d-none');
            document.getElementById('confirmTransfer').checked = false;
            document.getElementById('submitTransfer').disabled = true;
            document.querySelector('textarea[name="transfer_notes"]').value = '';

            new bootstrap.Modal(document.getElementById('transferPinModal')).show();
        }

        // Downline selection handler
        document.getElementById('downlineSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const preview = document.getElementById('downlinePreview');

            if (this.value) {
                document.getElementById('previewName').textContent = selectedOption.dataset.name;
                document.getElementById('previewUsername').textContent = selectedOption.dataset.username;

                // Get position from option text
                const position = selectedOption.text.includes('Kiri') ? 'Downline Kiri' : (selectedOption.text
                    .includes('Kanan') ? 'Downline Kanan' : 'Belum ada jaringan');
                document.getElementById('previewPosition').textContent = position;

                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }

            updateSubmitButton();
        });

        // Confirmation checkbox handler
        document.getElementById('confirmTransfer').addEventListener('change', updateSubmitButton);

        function updateSubmitButton() {
            const downlineSelected = document.getElementById('downlineSelect').value;
            const confirmed = document.getElementById('confirmTransfer').checked;
            document.getElementById('submitTransfer').disabled = !(downlineSelected && confirmed);
        }

        // Transfer form submission
        document.getElementById('pinTransferForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitTransfer');
            const originalText = submitBtn.innerHTML;

            // Show loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mentransfer...';
            submitBtn.disabled = true;

            // Create FormData
            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        if (typeof toastr !== 'undefined') {
                            toastr.success(data.message || 'PIN berhasil ditransfer!');
                        } else {
                            alert('PIN berhasil ditransfer!');
                        }

                        // Close modal and reload
                        bootstrap.Modal.getInstance(document.getElementById('transferPinModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Transfer gagal');
                    }
                })
                .catch(error => {
                    console.error('Transfer error:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(error.message || 'Terjadi kesalahan saat transfer');
                    } else {
                        alert(error.message || 'Terjadi kesalahan saat transfer');
                    }
                })
                .finally(() => {
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });


        // Copy referral code
        function copyReferral() {
            const text = document.getElementById("referralCode").innerText.trim();
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('Kode referral berhasil disalin!');
            }, function(err) {
                toastr.error('Gagal menyalin kode referral.');
            });
        }

        // Copy referral link
        function copyReferralLink() {
            const text = document.getElementById("referralLink").innerText.trim();
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('Link referral berhasil disalin!');
            }, function(err) {
                toastr.error('Gagal menyalin link referral.');
            });
        }

        // PIN Transfer Functions
        function copyPinCode(pinCode) {
            navigator.clipboard.writeText(pinCode).then(function() {
                toastr.success(`PIN ${pinCode} berhasil disalin!`, 'Berhasil', {
                    timeOut: 2000,
                    progressBar: true
                });
            }).catch(function(err) {
                console.error('Gagal menyalin:', err);
                toastr.error('Gagal menyalin kode PIN', 'Error');
            });
        }

        function showPinDetails(transfer) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Detail PIN Transfer',
                    html: `
                        <div class="text-start">
                            <div class="card border-0 mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body text-white p-3">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="bg-white bg-opacity-20 rounded-circle p-2">
                                                <i class="fas fa-key" style="font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h6 class="mb-1">PIN Code</h6>
                                            <div class="badge bg-light text-dark px-3 py-1">${transfer.pin_code}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-user me-2"></i>Purchased By
                                </h6>
                                <div class="border rounded p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 45px; height: 45px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">${transfer.owner.name}</div>
                                            <small class="text-muted">@${transfer.owner.username}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="text-center border rounded p-2">
                                        <div class="fw-bold text-primary">${transfer.bagan}</div>
                                        <small class="text-muted">Bagan Type</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center border rounded p-2">
                                        <div class="fw-bold text-success">Rp ${transfer.price.toLocaleString('id-ID')}</div>
                                        <small class="text-muted">Nilai PIN</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-info mb-2">
                                    <i class="fas fa-info-circle me-2"></i>Informasi Transfer
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Tanggal:</strong> ${new Date(transfer.transferred_date).toLocaleDateString('id-ID', {
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</li>
                                    <li><strong>Status:</strong> <span class="badge bg-success">${transfer.status}</span></li>
                                    <li><strong>Berlaku:</strong> Sampai digunakan</li>
                                </ul>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    // confirmButtonText: 'Order PIN',
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#667eea',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                        popup: 'swal-compact'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // activatePin(transfer.pin_code);
                    }
                });
            } else {
                alert(
                    `PIN Details:\nCode: ${transfer.code}\nFrom: ${transfer.owner.name}\nValue: Rp ${transfer.price.toLocaleString('id-ID')}\nType: ${transfer.bagan}`
                );
            }
        }

        function activatePin(pinCode) {
            if (typeof Swal !== 'undefined') {
                // Swal.fire({
                //     title: 'Konfirmasi Aktivasi',
                //     text: `Apakah Anda yakin ingin mengaktifkan PIN ${pinCode}?`,
                //     icon: 'question',
                //     showCancelButton: true,
                //     confirmButtonText: 'Ya, Aktivasi!',
                //     cancelButtonText: 'Batal',
                //     confirmButtonColor: '#28a745',
                //     cancelButtonColor: '#dc3545'
                // }).then((result) => {
                //     if (result.isConfirmed) {
                //         // Simulate activation process
                //         Swal.fire({
                //             title: 'Mengaktifkan PIN...',
                //             text: 'Mohon tunggu sebentar',
                //             allowOutsideClick: false,
                //             didOpen: () => {
                //                 Swal.showLoading();

                //                 // Simulate API call
                //                 setTimeout(() => {
                //                     Swal.fire({
                //                         title: 'Berhasil!',
                //                         text: 'PIN telah berhasil diaktifkan',
                //                         icon: 'success',
                //                         confirmButtonText: 'OK'
                //                     }).then(() => {
                //                         // Reload page or update UI
                //                         location.reload();
                //                     });
                //                 }, 2000);
                //             }
                //         });
                //     }
                // });
                Swal.fire({
                    title: 'Order PIN...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();

                        // Simulate API call
                        setTimeout(() => {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'PIN telah berhasil di order',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload page or update UI
                                location.reload();
                            });
                        }, 2000);
                    }
                });
            } else {
                if (confirm(`Order PIN ${pinCode}?`)) {
                    alert('PIN berhasil diorder!');
                    location.reload();
                }
            }
        }

        function viewPinHistory(transfer) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Riwayat PIN Transfer',
                    html: `
                        <div class="text-start">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>PIN Code</th>
                                            <th>From</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>${transfer.code}</code></td>
                                            <td>${transfer.owner.name}</td>
                                            <td><span class="badge bg-success">${transfer.status}</span></td>
                                            <td>${transfer.transferred_date}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted">Menampilkan 10 riwayat terakhir</small>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#6c757d',
                    customClass: {
                        popup: 'swal-wide'
                    }
                });
            } else {
                alert('Fitur riwayat PIN dalam pengembangan');
            }
        }

        // Open upgrade modal
        function openUpgradeModal(button) {
            const bagan = button.getAttribute('data-bagan');
            const nama = button.getAttribute('data-nama');
            const biaya = parseInt(button.getAttribute('data-biaya'));
            const allocated = parseInt(button.getAttribute('data-allocated') || 0);
            const sisa = biaya - allocated;

            document.getElementById('baganId').value = bagan;
            document.getElementById('namaBagan').innerText = 'Bagan ' + bagan + ' - ' + nama;
            document.getElementById('biayaBagan').innerText = 'Rp' + biaya.toLocaleString('id-ID');
            document.getElementById('allocatedBonusText').innerText = 'Rp' + allocated.toLocaleString('id-ID');
            document.getElementById('sisaPembayaranText').innerText = 'Rp' + sisa.toLocaleString('id-ID');

            // Reset form
            document.getElementById('pakaiBonus').checked = true;
            toggleTransferSection();

            new bootstrap.Modal(document.getElementById('upgradeModal')).show();
        }

        // Toggle transfer section
        function toggleTransferSection(forceTransfer = false) {
            const selected = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
            const section = document.getElementById('manualTransferSection');

            if (selected === 'transfer' || forceTransfer) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
                // Reset file input
                const fileInput = document.getElementById('buktiTransfer');
                if (fileInput) fileInput.value = '';
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Payment method change
            document.querySelectorAll('input[name="metode_pembayaran"]').forEach(el => {
                el.addEventListener('change', function() {
                    toggleTransferSection();
                });
            });

            // Form submission
            document.getElementById('formUpgradeBagan').addEventListener('submit', function(e) {
                e.preventDefault();
                handleUpgradeSubmission(this);
            });
        });

        // Handle upgrade submission
        function handleUpgradeSubmission(form) {
            const bagan = document.getElementById('baganId').value;
            const metode = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;

            // Create FormData
            const formData = new FormData();
            formData.append('_token', form.querySelector('[name="_token"]').value);
            formData.append('bagan', bagan);
            formData.append('metode_pembayaran', metode);

            if (metode === 'transfer') {
                const fileInput = document.getElementById('buktiTransfer');
                if (fileInput?.files[0]) {
                    formData.append('bukti_transfer', fileInput.files[0]);
                }
            }

            // Validate and submit
            if (metode === 'bonus') {
                // Check balance first
                fetch(`/member/bagan/cek-saldo/${bagan}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            submitUpgrade(formData, bagan);
                        } else {
                            toastr.warning(data.message);
                            // Switch to transfer mode
                            document.getElementById('pakaiTransfer').checked = true;
                            toggleTransferSection(true);
                        }
                    })
                    .catch(() => {
                        toastr.error('Terjadi kesalahan validasi saldo.');
                    });
            } else {
                // Direct submit for transfer
                submitUpgrade(formData, bagan);
            }
        }

        // Submit upgrade
        function submitUpgrade(formData, bagan) {
            // Show loading
            const submitBtn = document.querySelector('#formUpgradeBagan button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch(`/member/bagan/upgrade/${bagan}`, {
                    method: 'POST',
                    body: formData,
                })
                .then(async res => {
                    if (!res.ok) {
                        const data = await res.json();
                        throw new Error(Object.values(data.errors || {})[0]?.[0] || 'Upgrade gagal.');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data?.success) {
                        toastr.success('Upgrade berhasil!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data?.message || 'Upgrade gagal.');
                    }
                })
                .catch(err => {
                    console.error('Upgrade error:', err);
                    toastr.error(err.message || 'Terjadi kesalahan saat upgrade.');
                })
                .finally(() => {
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        function copyVoucherCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Kode voucher berhasil disalin!`, 'Berhasil', {
                        timeOut: 2000,
                        progressBar: true
                    });
                } else {
                    alert(`Kode voucher ${code} berhasil disalin!`);
                }
            }).catch(function(err) {
                console.error('Gagal menyalin:', err);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Gagal menyalin kode voucher', 'Error');
                }
            });
        }

        // Show voucher details - Simplified
        function showVoucherDetails() {
            const voucherCode = 'UMROH{{ strtoupper(substr(auth()->user()->username ?? 'USER', 0, 4)) }}2025';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Voucher Umroh',
                    html: `
                <div class="text-start">
                    <div class="mb-3 text-center">
                        <div class="badge bg-success px-3 py-2 mb-2">
                            Rp 1.000.000
                        </div>
                        <div class="fw-bold text-primary small" style="font-family: monospace;">
                            ${voucherCode}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-success mb-2 small">
                            <i class="fas fa-check-circle me-2"></i>Keuntungan
                        </h6>
                        <ul class="text-muted small">
                            <li>Subsidi Rp 1.000.000 untuk paket Umroh</li>
                            <li>Berlaku untuk paket Umroh/Wisata Religi</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-warning mb-2 small">
                            <i class="fas fa-exclamation-triangle me-2"></i>Syarat & Ketentuan
                        </h6>
                        <ul class="text-muted small">
                            <li>Minimum pembelian Rp 750.000</li>
                            <li>Tidak dapat diuangkan</li>
                            <li>Berlaku untuk 1 kali penggunaan</li>
                            <li>Hanya berlaku untuk member aktif</li>
                        </ul>
                    </div>
                </div>
            `,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#4caf50',
                    customClass: {
                        popup: 'swal-compact'
                    }
                });
            } else {
                alert(
                    `Voucher Umroh & Wisata Halal\nNilai: Rp 1.000.000\nKode: ${voucherCode}\n\nSyarat:\n- Min. pembelian Rp 750.000\n- Tidak dapat diuangkan`
                );
            }
        }
    </script>

    <style>
        .swal-wide {
            width: 90% !important;
            max-width: 700px !important;
        }
    </style>
@endpush
