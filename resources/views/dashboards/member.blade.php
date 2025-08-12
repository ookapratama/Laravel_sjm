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

    <div class="container-fluid">
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

        <!-- Downline Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card card-black">
                    <div class="card-body">
                        <h4 class="text-center text-white">Downline Kiri <strong>{{ $leftDownline }}</strong></h4>
                        <p class="text-center text-white">
                            {{ optional($user->getLeftChild())->name ?? 'Belum Ada' }}
                        </p>
                        <p class="text-center text-white">
                            {{ optional($user->getLeftChild())->username ?? 'Belum Ada' }}
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
                        <p class="text-center text-white">
                            {{ optional($user->getRightChild())->username ?? 'Belum Ada' }}
                        </p>
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
    </script>
@endpush
