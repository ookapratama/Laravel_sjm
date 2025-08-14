@extends('layouts.app')
@section('content')
    <div class="page-inner">
        <div class="card-header">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Data Member</h4>
                            <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal"
                                data-bs-target="#userModal">
                                <i class="fa fa-plus"></i>
                                Member Baru
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="multi-filter-select" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Posisi</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr data-id="{{ $user->id }}">
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->username }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ ucfirst($user->position ?? 'Belum ada') }}</td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    {{-- MODAL REGISTRASI (lebar + backdrop hitam + wizard 4 langkah) --}}
    <div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Form Registrasi Member</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                {{-- HANYA SATU FORM di modal ini --}}
                <form id="ref-register-form" action="{{ route('users.downline.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-body">data-

                        {{-- Step Indicator --}}
                        <div class="step-indicator mb-3">
                            <div class="step-item active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-text">PIN & Sponsor</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-text">Data Diri</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-text">Akun Login</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="4">
                                <div class="step-number">4</div>
                                <div class="step-text">Rekening</div>
                            </div>
                        </div>

                        {{-- Error global --}}
                        <div id="errorContainer" class="alert alert-danger d-none">
                            <ul class="mb-0" id="errorList"></ul>
                        </div>

                        {{-- Banner sponsor dari ?ref --}}
                        <div id="sponsorBanner" class="alert alert-info d-none">
                            <strong>Sponsor:</strong> <span id="sponsorText"></span>
                        </div>

                        <input type="hidden" name="ref" id="ref" value="{{ auth()->user()->referral_code }}">

                        {{-- ===== STEP 1: PIN & Sponsor ===== --}}
                        <div class="js-step active" data-step="1">
                            <h4 class="mb-3">Step 1: PIN Aktivasi & Kode Sponsor</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">PIN Aktivasi</label>
                                    <div class="input-group">
                                        <input type="text" name="pin_aktivasi" id="pin_aktivasi" class="form-control"
                                            required placeholder="Masukkan PIN aktivasi">
                                        <button class="btn btn-outline-success" type="button" id="checkPin">
                                            <span id="checkPinText">Verifikasi</span>
                                            <i class="fas fa-spinner fa-spin d-none" id="checkPinSpinner"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Didapat dari upline/admin saat membeli PIN. <span
                                            id="pinStatus" class="d-block"></span></small>
                                    <div class="invalid-feedback" id="pinFeedback"></div>
                                    <div class="valid-feedback d-none" id="pinValidFeedback"><i
                                            class="fas fa-check-circle"></i> PIN valid!</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Kode Sponsor</label>
                                    <div class="input-group">
                                        <input type="text" id="sponsor_code_display" name="sponsor_code"
                                            class="form-control" required placeholder="Masukkan kode sponsor"
                                            value="{{ auth()->user()->referral_code }}">
                                        <button class="btn btn-outline-info" type="button" id="checkSponsor">
                                            <span id="checkSponsorText">Verifikasi</span>
                                            <i class="fas fa-spinner fa-spin d-none" id="checkSponsorSpinner"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Kode referral dari sponsor/upline Anda. <span
                                            id="sponsorStatus" class="d-block"></span></small>
                                    <div class="invalid-feedback" id="sponsorFeedback"></div>
                                    <div class="valid-feedback d-none" id="sponsorValidFeedback"><i
                                            class="fas fa-check-circle"></i> Sponsor ditemukan!</div>
                                </div>

                                <div class="col-12">
                                    <div id="sponsorInfoBanner" class="alert alert-info d-none">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-tie me-2"></i>
                                            <div><strong>Sponsor Anda:</strong>
                                                <div id="sponsorInfo"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== STEP 2: Data Diri ===== --}}
                        <div class="js-step" data-step="2">
                            <h4 class="mb-3">Step 2: Data Diri</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. HP / WA</label>
                                    <div class="input-group">
                                        <input type="text" name="no_telp" id="no_telp" class="form-control"
                                            required placeholder="08xx atau +62xxx" inputmode="numeric">
                                        <button class="btn btn-outline-success" type="button" id="checkWhatsApp">
                                            <span id="checkWhatsAppText">Cek WA</span>
                                            <i class="fas fa-spinner fa-spin d-none" id="checkWhatsAppSpinner"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Nomor digunakan untuk notifikasi. <span id="whatsappStatus"
                                            class="d-block"></span></small>
                                    <div class="invalid-feedback" id="phoneFeedback"></div>
                                    <div class="valid-feedback d-none" id="phoneValidFeedback"><i
                                            class="fab fa-whatsapp"></i> Nomor WhatsApp valid!</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="email" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Jenis Kelamin</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin"
                                            id="jk_pria" value="pria" required>
                                        <label class="form-check-label" for="jk_pria">Pria</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin"
                                            id="jk_wanita" value="wanita" required>
                                        <label class="form-check-label" for="jk_wanita">Wanita</label>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">No. KTP (opsional)</label>
                                    <input type="text" name="no_ktp" id="no_ktp" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label d-block">Agama</label>
                                    @foreach (['islam', 'kristen', 'katolik', 'budha', 'hindu', 'lainnya'] as $ag)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_{{ $ag }}" value="{{ $ag }}" required>
                                            <label class="form-check-label"
                                                for="agama_{{ $ag }}">{{ ucfirst($ag) }}</label>
                                        </div>
                                    @endforeach
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Alamat Lengkap</label>
                                    <textarea name="alamat" id="alamat" class="form-control" rows="2" required></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">RT</label>
                                    <input type="text" name="rt" id="rt" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">RW</label>
                                    <input type="text" name="rw" id="rw" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Desa/Kelurahan</label>
                                    <input type="text" name="desa" id="desa" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kecamatan</label>
                                    <input type="text" name="kecamatan" id="kecamatan" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kota/Kabupaten</label>
                                    <input type="text" name="kota" id="kota" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Kode Pos</label>
                                    <input type="text" name="kode_pos" id="kode_pos" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== STEP 3: Akun Login ===== --}}
                        <div class="js-step" data-step="3">
                            <h4 class="mb-3">Step 3: Data Akun Login</h4>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Username</label>
                                    <div class="input-group">
                                        <input type="text" name="username" id="username" class="form-control"
                                            required placeholder="Masukkan username unik">
                                        <button class="btn btn-outline-primary" type="button" id="checkUsername">
                                            <span id="checkUsernameText">Cek Ketersediaan</span>
                                            <i class="fas fa-spinner fa-spin d-none" id="checkUsernameSpinner"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="usernameFeedback"></div>
                                    <div class="valid-feedback d-none" id="usernameValidFeedback"><i
                                            class="fas fa-check-circle"></i> Username tersedia!</div>
                                    <small class="form-text text-muted">4–20 karakter, huruf/angka/underscore. <span
                                            id="usernameStatus"></span></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control"
                                            required minlength="6" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i
                                                class="fas fa-eye" id="togglePasswordIcon"></i></button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="form-control" required minlength="6" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="togglePasswordConfirmation"><i class="fas fa-eye"
                                                id="togglePasswordConfirmationIcon"></i></button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== STEP 4: Rekening & Ahli Waris ===== --}}
                        <div class="js-step" data-step="4">
                            <h4 class="mb-3">Step 4: Data Rekening & Ahli Waris</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama di Rekening</label>
                                    <input type="text" name="nama_rekening" id="nama_rekening" class="form-control"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor Rekening</label>
                                    <input type="text" name="nomor_rekening" id="nomor_rekening" class="form-control"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Nama Bank</label>
                                    <input type="text" name="nama_bank" id="nama_bank" list="bankList"
                                        class="form-control" required placeholder="Pilih / ketik nama bank">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Ahli Waris (opsional)</label>
                                    <input type="text" name="nama_ahli_waris" id="nama_ahli_waris"
                                        class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Hubungan Ahli Waris (opsional)</label>
                                    <input type="text" name="hubungan_ahli_waris" id="hubungan_ahli_waris"
                                        class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="agree" id="agree"
                                            value="1" required>
                                        <label class="form-check-label" for="agree">Saya menyetujui Syarat &
                                            Ketentuan</label>
                                    </div>
                                    <div class="invalid-feedback">Anda harus menyetujui Syarat & Ketentuan.</div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-buttons d-flex gap-2 mt-4">
                            <button type="button" id="prevBtn" class="btn btn-secondary"
                                style="display:none">Sebelumnya</button>
                            <button type="button" id="nextBtn" class="btn btn-warning">Selanjutnya</button>
                            <button type="submit" id="submitBtn" class="btn btn-success" style="display:none">Daftar
                                Sekarang</button>
                        </div>

                        <datalist id="bankList"></datalist>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>
        /* Backdrop hitam pekat */
        .modal-backdrop.show {
            opacity: .9 !important;
            background: #000 !important;
        }

        /* Lebarkan modal di desktop */
        @media (min-width: 1200px) {
            .modal-xl {
                --bs-modal-width: 1140px;
            }
        }

        /* Wizard UI ringkas */
        .step-indicator {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            opacity: .5
        }

        .step-item.active,
        .step-item.completed {
            opacity: 1
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #ffc107;
            color: #000;
            font-weight: 700
        }

        .step-line {
            flex: 1 1 40px;
            height: 2px;
            background: #e5e7eb
        }

        .js-step {
            display: none
        }

        .js-step.active {
            display: block
        }
    </style>
@endsection
@push('scripts')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <script>
        (function() {
            // script wizard step nya
            'use strict';

            // Wizard variables
            let currentStep = 1;
            const totalSteps = 4;
            const errorContainer = document.getElementById('errorContainer');
            const errorList = document.getElementById('errorList');

            window.pinValidationStatus = {
                isValid: false,
                lastChecked: ''
            };

            window.sponsorValidationStatus = {
                isValid: false,
                lastChecked: ''
            };

            // Validation rules for each step
            const validationRules = {
                1: { // Step 1: PIN & Sponsor
                    pin_aktivasi: {
                        required: true,
                        minLength: 8,
                        pattern: /^[A-Z0-9]+$/,
                        customValidator: () => {
                            return window.isPinValid && window.isPinValid();
                        },
                        message: 'PIN aktivasi harus diverifikasi dan valid'
                    },
                    sponsor_code: {
                        required: true,
                        minLength: 3,
                        pattern: /^[A-Za-z0-9]+$/,
                        customValidator: () => {
                            return window.isSponsorValid && window.isSponsorValid();
                        },
                        message: 'Kode sponsor harus diverifikasi dan valid'
                    }
                },
                2: { // Step 2: Data Diri
                    name: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.,']+$/,
                        message: 'Nama lengkap harus diisi minimal 3 karakter (hanya huruf dan tanda baca umum)'
                    },
                    no_telp: {
                        required: true,
                        pattern: /^(\+?62|0)[0-9]{8,13}$/,
                        message: 'Nomor HP harus valid format Indonesia (08xx atau +62xxx)'
                    },
                    email: {
                        required: true,
                        minLength: 6,
                        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                        message: 'Format email tidak valid'
                    },
                    jenis_kelamin: {
                        required: true,
                        type: 'radio',
                        message: 'Pilih jenis kelamin'
                    },
                    no_ktp: {
                        required: false,
                        pattern: /^[0-9]{16}$/,
                        message: 'No KTP harus 16 digit angka'
                    },
                    tempat_lahir: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.]+$/,
                        message: 'Tempat lahir harus diisi minimal 3 karakter'
                    },
                    tanggal_lahir: {
                        required: true,
                        customValidator: (value) => {
                            const birthDate = new Date(value);
                            const today = new Date();
                            const age = today.getFullYear() - birthDate.getFullYear();
                            return age >= 17 && age <= 100;
                        },
                        message: 'Tanggal lahir tidak valid (minimal usia 17 tahun)'
                    },
                    agama: {
                        required: true,
                        type: 'radio',
                        message: 'Pilih agama'
                    },
                    alamat: {
                        required: true,
                        minLength: 10,
                        message: 'Alamat lengkap harus diisi minimal 10 karakter'
                    },
                    rt: {
                        required: false,
                        pattern: /^[0-9]{1,3}$/,
                        message: 'RT harus berupa angka 1-3 digit'
                    },
                    rw: {
                        required: false,
                        pattern: /^[0-9]{1,3}$/,
                        message: 'RW harus berupa angka 1-3 digit'
                    },
                    desa: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.]+$/,
                        message: 'Desa/Kelurahan harus diisi minimal 3 karakter'
                    },
                    kecamatan: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.]+$/,
                        message: 'Kecamatan harus diisi minimal 3 karakter'
                    },
                    kota: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.]+$/,
                        message: 'Kota/Kabupaten harus diisi minimal 3 karakter'
                    },
                    kode_pos: {
                        required: false,
                        pattern: /^[0-9]{5}$/,
                        message: 'Kode pos harus 5 digit angka'
                    }
                },
                3: { // Step 3: Akun Login
                    username: {
                        required: true,
                        minLength: 4,
                        maxLength: 20,
                        pattern: /^[a-zA-Z0-9_]+$/,
                        message: 'Username 4-20 karakter, hanya huruf, angka, dan underscore'
                    },
                    password: {
                        required: true,
                        minLength: 6,
                        customValidator: (value) => {
                            // Password harus mengandung minimal 1 huruf dan 1 angka
                            return /[a-zA-Z]/.test(value) && /[0-9]/.test(value);
                        },
                        message: 'Password minimal 6 karakter dengan kombinasi huruf dan angka'
                    },
                    password_confirmation: {
                        required: true,
                        customValidator: (value) => {
                            const password = document.getElementById('password').value;
                            return value === password;
                        },
                        message: 'Konfirmasi password tidak cocok'
                    }
                },
                4: { // Step 5: Rekening
                    nama_rekening: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.,']+$/,
                        message: 'Nama rekening harus diisi minimal 3 karakter'
                    },
                    nomor_rekening: {
                        required: true,
                        pattern: /^[0-9]{5,20}$/,
                        message: 'Nomor rekening harus 5-20 digit angka'
                    },
                    nama_bank: {
                        required: true,
                        minLength: 3,
                        message: 'Nama bank harus dipilih atau diisi'
                    },
                    nama_ahli_waris: {
                        required: false,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.,']*$/,
                        message: 'Nama ahli waris minimal 3 karakter jika diisi'
                    },
                    hubungan_ahli_waris: {
                        required: false,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.,']*$/,
                        message: 'Hubungan ahli waris minimal 3 karakter jika diisi'
                    },
                    agree: {
                        required: true,
                        type: 'checkbox',
                        message: 'Anda harus menyetujui Syarat & Ketentuan'
                    }
                }
            };

            // Validation function for individual field
            function validateField(fieldName, value, rules) {
                const rule = rules[fieldName];
                if (!rule) return {
                    isValid: true
                };

                // Check required
                if (rule.required && (!value || value.trim() === '')) {
                    return {
                        isValid: false,
                        message: rule.message
                    };
                }

                // Skip other validations if field is not required and empty
                if (!rule.required && (!value || value.trim() === '')) {
                    return {
                        isValid: true
                    };
                }

                // Check minimum length
                if (rule.minLength && value.length < rule.minLength) {
                    return {
                        isValid: false,
                        message: rule.message
                    };
                }

                // Check maximum length
                if (rule.maxLength && value.length > rule.maxLength) {
                    return {
                        isValid: false,
                        message: rule.message
                    };
                }

                // Check pattern
                if (rule.pattern && !rule.pattern.test(value)) {
                    return {
                        isValid: false,
                        message: rule.message
                    };
                }

                // Check custom validator
                if (rule.customValidator && !rule.customValidator(value)) {
                    return {
                        isValid: false,
                        message: rule.message
                    };
                }

                return {
                    isValid: true
                };
            }

            // Validation function for radio buttons and checkboxes
            function validateSpecialField(fieldName, rules) {
                const rule = rules[fieldName];
                if (!rule) return {
                    isValid: true
                };

                if (rule.type === 'radio') {
                    const radios = document.querySelectorAll(`input[name="${fieldName}"]`);
                    const isChecked = Array.from(radios).some(radio => radio.checked);
                    if (rule.required && !isChecked) {
                        return {
                            isValid: false,
                            message: rule.message
                        };
                    }
                }

                if (rule.type === 'checkbox') {
                    const checkbox = document.querySelector(`input[name="${fieldName}"]`);
                    if (rule.required && (!checkbox || !checkbox.checked)) {
                        return {
                            isValid: false,
                            message: rule.message
                        };
                    }
                }

                return {
                    isValid: true
                };
            }

            // Clear validation feedback
            function clearValidationFeedback() {
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.textContent = '';
                });
                errorContainer.classList.add('d-none');
                errorList.innerHTML = '';
            }

            // Show validation error for field
            function showFieldError(fieldName, message) {
                const inputs = document.querySelectorAll(`[name="${fieldName}"]`);
                inputs.forEach(input => {
                    input.classList.add('is-invalid');

                    let feedback = input.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        // For radio buttons, find the container
                        const container = input.closest('.col-md-6, .col-md-12, .col-12');
                        if (container) {
                            feedback = container.querySelector('.invalid-feedback');
                        }
                    }

                    if (feedback) {
                        feedback.textContent = message;
                    } else {
                        // Create new feedback element
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = message;

                        if (input.type === 'radio' || input.type === 'checkbox') {
                            const container = input.closest('.col-md-6, .col-md-12, .col-12');
                            if (container) {
                                container.appendChild(feedback);
                            }
                        } else {
                            input.parentNode.insertBefore(feedback, input.nextSibling);
                        }
                    }
                });
            }

            // Validate entire step
            function validateStep(step) {
                clearValidationFeedback();

                const stepRules = validationRules[step];
                if (!stepRules) return true;

                let isStepValid = true;
                const errors = [];

                // Validasi khusus untuk Step 1
                if (step === 1) {
                    const pinInput = document.getElementById('pin_aktivasi');
                    const sponsorInput = document.getElementById('sponsor_code_display');

                    // Cek PIN
                    const pinValue = pinInput.value.trim();
                    let pinValid = true;

                    if (!pinValue) {
                        showFieldError('pin_aktivasi', 'PIN aktivasi harus diisi');
                        errors.push('PIN aktivasi harus diisi');
                        pinValid = false;
                    } else if (pinValue.length < 8) {
                        showFieldError('pin_aktivasi', 'PIN harus minimal 8 karakter');
                        errors.push('PIN harus minimal 8 karakter');
                        pinValid = false;
                    } else if (!window.pinValidationStatus.isValid || window.pinValidationStatus.lastChecked !==
                        pinValue) {
                        showFieldError('pin_aktivasi', 'PIN aktivasi harus diverifikasi terlebih dahulu');
                        errors.push('PIN aktivasi harus diverifikasi terlebih dahulu');

                        const pinFeedback = document.getElementById('pinFeedback');
                        const pinStatus = document.getElementById('pinStatus');
                        if (pinFeedback) {
                            pinInput.classList.add('is-invalid');
                            pinFeedback.textContent = 'Silakan klik tombol "Verifikasi" untuk memverifikasi PIN';
                        }
                        if (pinStatus) {
                            pinStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> PIN belum diverifikasi';
                            pinStatus.className = 'verification-status status-warning';
                        }
                        pinValid = false;
                    }

                    // Cek Sponsor
                    const sponsorValue = sponsorInput.value.trim();
                    let sponsorValid = true;

                    if (!sponsorValue) {
                        showFieldError('sponsor_code', 'Kode sponsor harus diisi');
                        errors.push('Kode sponsor harus diisi');
                        sponsorValid = false;
                    } else if (sponsorValue.length < 3) {
                        showFieldError('sponsor_code', 'Kode sponsor harus minimal 3 karakter');
                        errors.push('Kode sponsor harus minimal 3 karakter');
                        sponsorValid = false;
                    } else if (!window.sponsorValidationStatus.isValid || window.sponsorValidationStatus.lastChecked !==
                        sponsorValue) {
                        showFieldError('sponsor_code', 'Kode sponsor harus diverifikasi terlebih dahulu');
                        errors.push('Kode sponsor harus diverifikasi terlebih dahulu');

                        const sponsorFeedback = document.getElementById('sponsorFeedback');
                        const sponsorStatus = document.getElementById('sponsorStatus');
                        if (sponsorFeedback) {
                            sponsorInput.classList.add('is-invalid');
                            sponsorFeedback.textContent =
                                'Silakan klik tombol "Verifikasi" untuk memverifikasi sponsor';
                        }
                        if (sponsorStatus) {
                            sponsorStatus.innerHTML =
                                '<i class="fas fa-exclamation-triangle"></i> Sponsor belum diverifikasi';
                            sponsorStatus.className = 'verification-status status-warning';
                        }
                        sponsorValid = false;
                    }

                    isStepValid = pinValid && sponsorValid;

                    // Tampilkan pesan error global jika ada
                    // Debug log
                    console.log('Step 1 Validation Debug:', {
                        pinValue,
                        pinValid,
                        pinStatus: window.pinValidationStatus,
                        sponsorValue,
                        sponsorValid,
                        sponsorStatus: window.sponsorValidationStatus,
                        isStepValid
                    });

                    if (!isStepValid) {
                        if (window.toastr) {
                            if (!pinValid && !sponsorValid) {
                                toastr.error('PIN aktivasi dan kode sponsor harus diverifikasi terlebih dahulu');
                            } else if (!pinValid) {
                                toastr.error('PIN aktivasi harus diverifikasi terlebih dahulu');
                            } else if (!sponsorValid) {
                                toastr.error('Kode sponsor harus diverifikasi terlebih dahulu');
                            }
                        }
                    }
                }
                // Validasi untuk step lainnya
                else {
                    // Validasi untuk step lainnya (tetap sama)
                    Object.keys(stepRules).forEach(fieldName => {
                        const rule = stepRules[fieldName];

                        if (rule.type === 'radio' || rule.type === 'checkbox') {
                            const validation = validateSpecialField(fieldName, stepRules);
                            if (!validation.isValid) {
                                showFieldError(fieldName, validation.message);
                                errors.push(validation.message);
                                isStepValid = false;
                            }
                        } else {
                            const input = document.querySelector(`[name="${fieldName}"]`);
                            if (input) {
                                const validation = validateField(fieldName, input.value, stepRules);
                                if (!validation.isValid) {
                                    showFieldError(fieldName, validation.message);
                                    errors.push(validation.message);
                                    isStepValid = false;
                                }
                            }
                        }
                    });
                }

                // Show global errors if any
                if (errors.length > 0) {
                    errorList.innerHTML = errors.map(error => `<li>${error}</li>`).join('');
                    errorContainer.classList.remove('d-none');

                    const firstInvalidField = document.querySelector('.is-invalid');
                    if (firstInvalidField) {
                        firstInvalidField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }

                return isStepValid;
            }

            // Real-time validation for individual fields
            function setupRealTimeValidation() {
                Object.keys(validationRules).forEach(step => {
                    const stepRules = validationRules[step];
                    Object.keys(stepRules).forEach(fieldName => {
                        const inputs = document.querySelectorAll(`[name="${fieldName}"]`);

                        inputs.forEach(input => {
                            if (input.type === 'radio' || input.type === 'checkbox') {
                                input.addEventListener('change', () => {
                                    // Clear previous errors for this field
                                    input.classList.remove('is-invalid');
                                    const feedback = input.closest(
                                            '.col-md-6, .col-md-12, .col-12')
                                        ?.querySelector('.invalid-feedback');
                                    if (feedback) feedback.textContent = '';

                                    // Validate
                                    const validation = validateSpecialField(fieldName,
                                        stepRules);
                                    if (!validation.isValid) {
                                        showFieldError(fieldName, validation.message);
                                    }
                                });
                            } else {
                                // For text inputs, validate on blur
                                input.addEventListener('blur', () => {
                                    // Clear previous errors
                                    input.classList.remove('is-invalid');
                                    const feedback = input.parentNode.querySelector(
                                        '.invalid-feedback');
                                    if (feedback) feedback.textContent = '';

                                    // Validate
                                    const validation = validateField(fieldName, input
                                        .value, stepRules);
                                    if (!validation.isValid) {
                                        showFieldError(fieldName, validation.message);
                                    }
                                });

                                // For password confirmation, also validate on input
                                if (fieldName === 'password_confirmation') {
                                    input.addEventListener('input', () => {
                                        const validation = validateField(fieldName,
                                            input.value, stepRules);
                                        if (!validation.isValid) {
                                            showFieldError(fieldName, validation
                                                .message);
                                        } else {
                                            input.classList.remove('is-invalid');
                                            const feedback = input.parentNode
                                                .querySelector('.invalid-feedback');
                                            if (feedback) feedback.textContent = '';
                                        }
                                    });
                                }
                            }
                        });
                    });
                });
            }

            // Wizard navigation functions
            function showStep(step) {
                // Hide all steps
                document.querySelectorAll('.js-step').forEach(s => {
                    s.classList.remove('active');
                });

                // Show current step
                const currentStepElement = document.querySelector(`.js-step[data-step="${step}"]`);
                if (currentStepElement) {
                    currentStepElement.classList.add('active');
                }

                // Update step indicators
                document.querySelectorAll('.step-item').forEach((item, index) => {
                    const stepNumber = index + 1;
                    item.classList.remove('active', 'completed');

                    if (stepNumber < step) {
                        item.classList.add('completed');
                    } else if (stepNumber === step) {
                        item.classList.add('active');
                    }
                });

                // Update button visibility
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');
                const submitBtn = document.getElementById('submitBtn');

                prevBtn.style.display = step > 1 ? 'block' : 'none';
                nextBtn.style.display = step < totalSteps ? 'block' : 'none';
                submitBtn.style.display = step === totalSteps ? 'block' : 'none';
            }

            // Initialize wizard
            function initWizard() {
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');

                nextBtn.addEventListener('click', () => {
                    if (validateStep(currentStep)) {
                        currentStep++;
                        showStep(currentStep);
                    }
                });

                prevBtn.addEventListener('click', () => {
                    currentStep--;
                    showStep(currentStep);
                });

                // Initialize first step
                showStep(currentStep);

                // Setup real-time validation
                setupRealTimeValidation();
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWizard);
            } else {
                initWizard();
            }

            // Make validateStep available globally for form submission
            window.validateStep = validateStep;
            window.clearValidationFeedback = clearValidationFeedback;
            window.totalSteps = totalSteps;


            // isi sponsor display dari ?ref
            const urlParams = new URLSearchParams(window.location.search);
            // Ambil dari URL, jika tidak ada, biarkan kosong. Ini untuk memastikan tidak ada kesalahan saat `request('ref')` tidak ada
            const ref = urlParams.get('ref') || "";
            const refInput = document.getElementById('ref');
            const sponsorDisp = document.getElementById('sponsor_code_display');
            const sponsorBanner = document.getElementById('sponsorBanner');
            const sponsorText = document.getElementById('sponsorText');

            if (ref) {
                refInput.value = ref;
                if (sponsorDisp) sponsorDisp.value = ref; // Set display field
                if (sponsorBanner) sponsorBanner.classList.remove('d-none');
                if (sponsorText) sponsorText.textContent = 'Kode: ' + ref + ' (akan divalidasi saat submit)';
            }
            // Tambahkan event listener untuk sponsor_code_display agar selalu mengisi hidden ref
            if (sponsorDisp && refInput) {
                sponsorDisp.addEventListener('input', function() {
                    refInput.value = this.value;
                });
            }


            // datalist Bank (BUMN dulu)
            const datalist = document.getElementById('bankList');
            if (datalist) {
                const BUMN = ['Bank Mandiri', 'Bank Rakyat Indonesia (BRI)', 'Bank Negara Indonesia (BNI)',
                    'Bank Tabungan Negara (BTN)', 'Bank Syariah Indonesia (BSI)'
                ];
                const UMUM = ['Bank Central Asia (BCA)', 'CIMB Niaga', 'Bank Danamon', 'OCBC NISP', 'Permata Bank',
                    'Panin Bank', 'Maybank Indonesia', 'KB Bukopin', 'Bank BTPN', 'Bank Mega', 'Bank Sinarmas',
                    'UOB Indonesia', 'HSBC Indonesia', 'Standard Chartered Indonesia',
                    'Citibank N.A. Indonesia',
                    'ICBC Indonesia', 'Bank China Construction Bank Indonesia (CCB Indonesia)',
                    'Bank Commonwealth',
                    'QNB Indonesia', 'Bank Woori Saudara', 'Bank Shinhan Indonesia', 'Bank JTrust Indonesia',
                    'Bank MNC Internasional', 'Bank Artha Graha Internasional', 'Bank Capital Indonesia',
                    'Bank Maspion Indonesia', 'Bank Ina Perdana', 'Bank Index Selindo',
                    'Bank Victoria International', 'Bank Mayora', 'Bank Oke Indonesia',
                    'Bank Sahabat Sampoerna',
                    'Krom Bank Indonesia', 'Bank Fama Internasional', 'Bank Neo Commerce (BNC)',
                    'Allo Bank Indonesia', 'SeaBank Indonesia', 'Bank Jago', 'BCA Digital (blu)',
                    'Bank Muamalat Indonesia', 'BTPN Syariah', 'Bank Mega Syariah'
                ];
                const BPD = ['Bank DKI', 'Bank BJB (Jawa Barat & Banten)', 'Bank Jateng', 'Bank Jatim', 'Bank DIY',
                    'Bank BPD Bali', 'Bank NTB Syariah', 'Bank NTT', 'Bank BPD Sumut', 'Bank Sumsel Babel',
                    'Bank Nagari (Sumbar)', 'Bank Riau Kepri', 'Bank Jambi', 'Bank Bengkulu', 'Bank Lampung',
                    'Bank Kalbar', 'Bank Kalteng', 'Bank Kalsel', 'Bank Kaltimtara', 'Bank Kaltara',
                    'Bank Sulselbar', 'Bank Sultra', 'Bank Sulteng', 'Bank SulutGo', 'Bank Maluku Malut',
                    'Bank Papua'
                ];
                const banks = [...BUMN, ...UMUM.sort((a, b) => a.localeCompare(b)), ...BPD.sort((a, b) => a
                    .localeCompare(b))];
                datalist.innerHTML = banks.map(b => `<option value="${b}"></option>`).join('');
            }

            // submit via fetch → balas JSON (ok untuk SPA feel)
            const form = document.getElementById('ref-register-form');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Pastikan validasi step terakhir berhasil
                if (!validateStep(totalSteps)) {
                    // Scroll to the first invalid field
                    const firstInvalidField = document.querySelector('.is-invalid');
                    if (firstInvalidField) {
                        firstInvalidField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                    if (window.toastr) toastr.error(
                        'Harap lengkapi semua bidang yang wajib diisi pada step terakhir.');
                    return;
                }

                // bersihkan error lama dari seluruh form
                clearValidationFeedback();

                try {
                    const formData = new FormData(this);

                    console.log('Form would be submitted with data:', Object.fromEntries(formData));

                    const res = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        credentials: "include",
                        headers: {
                            // 'X-CSRF-TOKEN' otomatis diambil oleh Blade @csrf directive di sini
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        redirect: 'manual' // Penting untuk menangani redirect secara manual pada 200 OK
                    });

                    const ct = res.headers.get('content-type') || '';
                    const data = ct.includes('application/json') ? await res.json() :
                    {}; // Dapatkan JSON jika ada, jika tidak, objek kosong
                    console.log(res)
                    if (res.ok) { // Status 2xx
                        if (window.toastr) toastr.success(data.message ||
                            'User berhasil disimpan');
                        if (data.redirect) {
                            setTimeout(() => { // Beri waktu toastr tampil
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            // Default redirect jika tidak ada dari server
                            setTimeout(() => {
                                window.location.href = "{{ route('users.downline') }}";
                            }, 1000);
                        }
                        return;
                    }

                    // Handle validation errors (422 Unprocessable Entity)
                    if (res.status === 422 && data.errors) {
                        errorList.innerHTML = ''; // Clear previous errors in global container
                        let firstInvalidField = null;

                        Object.entries(data.errors).forEach(([field, msgs]) => {
                            const input = document.querySelector(`[name="${field}"]`);
                            const firstMsg = msgs[0];

                            // Add to global error list
                            const li = document.createElement('li');
                            li.textContent = firstMsg;
                            errorList.appendChild(li);

                            // Add specific feedback to the input field
                            if (input) {
                                input.classList.add('is-invalid');
                                let feedback = input.nextElementSibling;
                                if (!feedback || !feedback.classList.contains(
                                        'invalid-feedback')) {
                                    feedback = document.createElement('div');
                                    feedback.className = 'invalid-feedback';
                                    input.parentNode.insertBefore(feedback, input.nextSibling);
                                }
                                feedback.textContent = firstMsg;

                                // If this is the first invalid field, store it for scrolling
                                if (!firstInvalidField) {
                                    firstInvalidField = input;
                                }

                                // For radio groups, apply invalid class to all
                                if (input.type === 'radio') {
                                    document.querySelectorAll(`[name="${field}"]`).forEach(
                                        radio => {
                                            radio.classList.add('is-invalid');
                                        });
                                }
                            } else {
                                // If no specific input found, add to global error container only
                                console.warn(
                                    `Validation error for unknown field: ${field} - ${firstMsg}`
                                );
                            }
                        });

                        errorContainer.classList.remove('d-none');
                        if (window.toastr) toastr.error(
                            'Terdapat kesalahan pada input Anda. Silakan periksa kembali.');

                        // Scroll to the first invalid field or error container
                        if (firstInvalidField) {
                            // Determine which step the first invalid field belongs to
                            let parentStep = firstInvalidField.closest('.js-step');
                            if (parentStep) {
                                let stepNumber = parseInt(parentStep.dataset.step);
                                if (stepNumber && stepNumber !== currentStep) {
                                    currentStep = stepNumber;
                                    showStep(currentStep); // Go to the step with the error
                                }
                            }
                            // Then scroll to the element
                            setTimeout(() => { // Delay slightly to allow step change animation
                                firstInvalidField.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }, 100);
                        } else {
                            // If no specific field, scroll to the global error container
                            errorContainer.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                        return;
                    }

                    // Handle other server errors (e.g., 500, 403, etc.)
                    const errorMessage = data.message ||
                        `Terjadi kesalahan ${res.status}: ${res.message ?? res.statusText}.`;
                    errorList.innerHTML = `<li>${errorMessage}</li>`;
                    console.log(errorList.innerHTML)
                    errorContainer.classList.remove('d-none');
                    if (window.toastr) toastr.error(errorMessage);
                    console.error('Server error:', data);

                } catch (err) {
                    console.error('Network or unexpected error:', err);
                    errorList.innerHTML =
                        `<li>Terjadi kesalahan koneksi atau tidak terduga. Silakan coba lagi.</li>`;
                    errorContainer.classList.remove('d-none');
                    if (window.toastr) toastr.error('Terjadi kesalahan koneksi atau tidak terduga.');
                } finally {
                    // Re-enable submit button if it was disabled (optional, but good practice)
                    // submitBtn.disabled = false;
                }
            });
        })();


        // Tambahkan setelah script validasi wizard
        document.addEventListener('DOMContentLoaded', function() {



            // Function untuk input hanya angka
            function makeNumericOnly(input) {
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/\D/g, '');
                });

                input.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(String.fromCharCode(e.which))) {
                        e.preventDefault();
                    }
                });
            }

            // Apply ke field-field numerik
            const numericFields = [
                'no_telp', // Nomor HP
                'no_ktp', // Nomor KTP  
                'nomor_rekening', // Nomor Rekening
                'rt', // RT
                'rw', // RW
                'kode_pos' // Kode Pos
            ];

            numericFields.forEach(fieldId => {
                const input = document.getElementById(fieldId);
                if (input) {
                    makeNumericOnly(input);
                }
            });

            // Password Toggle Function
            function setupPasswordToggle(inputId, buttonId, iconId) {
                const passwordInput = document.getElementById(inputId);
                const toggleButton = document.getElementById(buttonId);
                const toggleIcon = document.getElementById(iconId);

                if (!passwordInput || !toggleButton || !toggleIcon) return;

                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Toggle password visibility
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';

                    // Toggle icon
                    if (isPassword) {
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                        toggleButton.setAttribute('title', 'Sembunyikan password');
                    } else {
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                        toggleButton.setAttribute('title', 'Tampilkan password');
                    }
                });

                // Set initial attributes
                toggleButton.setAttribute('title', 'Tampilkan password');
            }

            // Setup untuk kedua password field
            setupPasswordToggle('password', 'togglePassword', 'togglePasswordIcon');
            setupPasswordToggle('password_confirmation', 'togglePasswordConfirmation',
                'togglePasswordConfirmationIcon');

            // Update event listener untuk PIN
            const pinInput = document.getElementById('pin_aktivasi');
            const checkPinBtn = document.getElementById('checkPin');

            // Reset status saat input berubah
            pinInput.addEventListener('input', function(e) {
                // Reset status validasi
                window.pinValidationStatus.isValid = false;
                window.pinValidationStatus.lastChecked = '';

                // Reset visual feedback
                this.classList.remove('is-valid', 'is-invalid');
                const pinFeedback = document.getElementById('pinFeedback');
                const pinValidFeedback = document.getElementById('pinValidFeedback');
                const pinStatus = document.getElementById('pinStatus');

                if (pinFeedback) pinFeedback.textContent = '';
                if (pinValidFeedback) pinValidFeedback.classList.add('d-none');
                if (pinStatus) pinStatus.textContent = '';

                // Format input - hanya huruf kapital dan angka
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            // Update event listener untuk Sponsor
            const sponsorInput = document.getElementById('sponsor_code_display');
            const checkSponsorBtn = document.getElementById('checkSponsor');

            // Reset status saat input berubah
            sponsorInput.addEventListener('input', function(e) {
                // Reset status validasi
                window.sponsorValidationStatus.isValid = false;
                window.sponsorValidationStatus.lastChecked = '';

                // Reset visual feedback
                this.classList.remove('is-valid', 'is-invalid');
                const sponsorFeedback = document.getElementById('sponsorFeedback');
                const sponsorValidFeedback = document.getElementById('sponsorValidFeedback');
                const sponsorStatus = document.getElementById('sponsorStatus');
                const sponsorInfoBanner = document.getElementById('sponsorInfoBanner');

                if (sponsorFeedback) sponsorFeedback.textContent = '';
                if (sponsorValidFeedback) sponsorValidFeedback.classList.add('d-none');
                if (sponsorStatus) sponsorStatus.textContent = '';
                if (sponsorInfoBanner) sponsorInfoBanner.classList.add('d-none');

                // Format input - hanya alfanumerik
                this.value = this.value.replace(/[^A-Za-z0-9]/g, '');

                // Update hidden ref field
                const refInput = document.getElementById('ref');
                if (refInput) {
                    refInput.value = this.value;
                }
            });


            // Pastikan fungsi global tersedia
            window.isPinValid = function() {
                const pin = pinInput.value.trim();
                return window.pinValidationStatus.isValid &&
                    window.pinValidationStatus.lastChecked === pin;
            };

            window.isSponsorValid = function() {
                const sponsor = sponsorInput.value.trim();
                return window.sponsorValidationStatus.isValid &&
                    window.sponsorValidationStatus.lastChecked === sponsor;
            };

        });

        //  verifikasi username
        const usernameInput = document.getElementById('username');
        const checkButton = document.getElementById('checkUsername');
        const checkText = document.getElementById('checkUsernameText');
        const checkSpinner = document.getElementById('checkUsernameSpinner');
        const usernameFeedback = document.getElementById('usernameFeedback');
        const usernameValidFeedback = document.getElementById('usernameValidFeedback');
        const usernameStatus = document.getElementById('usernameStatus');

        let checkTimeout;
        let lastCheckedUsername = '';
        let isUsernameAvailable = false;

        // Function to validate username format
        function validateUsernameFormat(username) {
            const regex = /^[a-zA-Z0-9_]+$/;
            return username.length >= 4 && username.length <= 20 && regex.test(username);
        }

        // Function to reset username status
        function resetUsernameStatus() {
            usernameInput.classList.remove('is-valid', 'is-invalid');
            usernameFeedback.textContent = '';
            usernameValidFeedback.classList.add('d-none');
            usernameStatus.textContent = '';
            isUsernameAvailable = false;
        }

        // Function to show loading state
        function showCheckingState() {
            checkButton.classList.add('btn-checking');
            checkText.textContent = 'Mengecek...';
            checkSpinner.classList.remove('d-none');
            usernameStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek ketersediaan...';
            usernameStatus.className = 'username-status username-checking';
        }

        // Function to hide loading state
        function hideCheckingState() {
            checkButton.classList.remove('btn-checking');
            checkText.textContent = 'Cek Ketersediaan';
            checkSpinner.classList.add('d-none');
        }

        // Function to check username availability
        async function checkUsernameAvailability(username) {
            console.log('checkUsernameAvailability  : ', username)
            console.log('usernameStatus : ', usernameStatus)
            if (!validateUsernameFormat(username)) {
                usernameInput.classList.add('is-invalid');
                usernameFeedback.textContent = 'Username harus 4-20 karakter, hanya huruf, angka, dan underscore';
                usernameStatus.innerHTML = '<i class="fas fa-times-circle"></i> Format tidak valid';
                usernameStatus.className = 'username-status username-taken';
                return false;
            }

            showCheckingState();

            try {
                // Ganti URL ini dengan route Laravel Anda
                const response = await fetch(`/member/check-username`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        username: username
                    })
                });

                const data = await response.json();

                hideCheckingState();

                if (response.ok) {
                    if (data.available) {
                        // Username tersedia
                        usernameInput.classList.remove('is-invalid');
                        usernameInput.classList.add('is-valid');
                        usernameValidFeedback.classList.remove('d-none');
                        usernameStatus.innerHTML = '<i class="fas fa-check-circle"></i> Username tersedia!';
                        usernameStatus.className = 'username-status username-available';
                        isUsernameAvailable = true;
                        lastCheckedUsername = username;
                    } else {
                        // Username sudah terpakai
                        usernameInput.classList.remove('is-valid');
                        usernameInput.classList.add('is-invalid');
                        usernameFeedback.textContent = data.message || 'Username sudah terpakai';
                        usernameStatus.innerHTML = '<i class="fas fa-times-circle"></i> Username sudah terpakai';
                        usernameStatus.className = 'username-status username-taken';
                        isUsernameAvailable = false;
                    }
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan saat mengecek username');
                }

            } catch (error) {
                hideCheckingState();
                console.error('Error checking username:', error);
                usernameInput.classList.add('is-invalid');
                usernameFeedback.textContent = 'Terjadi kesalahan saat mengecek username. Silakan coba lagi.';
                usernameStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error checking';
                usernameStatus.className = 'username-status username-taken';
                isUsernameAvailable = false;
            }
        }

        // Event listener for check button
        checkButton.addEventListener('click', function(e) {
            e.preventDefault();
            const username = usernameInput.value.trim();

            if (!username) {
                usernameInput.focus();
                return;
            }

            checkUsernameAvailability(username);
        });

        // Event listener for username input
        usernameInput.addEventListener('input', function(e) {
            clearTimeout(checkTimeout);
            resetUsernameStatus();

            const username = this.value.trim();

            // Real-time format validation
            if (username && !validateUsernameFormat(username)) {
                this.classList.add('is-invalid');
                usernameFeedback.textContent = 'Username harus 4-20 karakter, hanya huruf, angka, dan underscore';
            } else if (username && username !== lastCheckedUsername) {
                // Auto-check after 1 second of no typing
                checkTimeout = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 1000);
            }
        });

        // Event listener for Enter key
        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkButton.click();
            }
        });

        // Validate username availability before form submission
        const form = document.getElementById('ref-register-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();

                if (!isUsernameAvailable || username !== lastCheckedUsername) {
                    e.preventDefault();
                    usernameInput.classList.add('is-invalid');
                    usernameFeedback.textContent = 'Silakan periksa ketersediaan username terlebih dahulu';
                    usernameInput.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    if (window.toastr) {
                        toastr.error('Silakan periksa ketersediaan username terlebih dahulu');
                    }
                    return false;
                }
            });
        }

        // Make isUsernameAvailable accessible globally for step validation
        window.isUsernameAvailable = function() {
            const username = usernameInput.value.trim();
            return isUsernameAvailable && username === lastCheckedUsername;
        };


        // cek referral dan pin aktivasi 
        const pinInput = document.getElementById('pin_aktivasi');
        const checkPinBtn = document.getElementById('checkPin');
        const checkPinText = document.getElementById('checkPinText');
        const checkPinSpinner = document.getElementById('checkPinSpinner');
        const pinFeedback = document.getElementById('pinFeedback');
        const pinValidFeedback = document.getElementById('pinValidFeedback');
        const pinStatus = document.getElementById('pinStatus');

        // Sponsor Elements
        const sponsorInput = document.getElementById('sponsor_code_display');
        const checkSponsorBtn = document.getElementById('checkSponsor');
        const checkSponsorText = document.getElementById('checkSponsorText');
        const checkSponsorSpinner = document.getElementById('checkSponsorSpinner');
        const sponsorFeedback = document.getElementById('sponsorFeedback');
        const sponsorValidFeedback = document.getElementById('sponsorValidFeedback');
        const sponsorStatus = document.getElementById('sponsorStatus');
        const sponsorInfoBanner = document.getElementById('sponsorInfoBanner');
        const sponsorInfo = document.getElementById('sponsorInfo');

        // Global variables
        let isPinValid = false;
        let isSponsorValid = false;
        let lastCheckedPin = '';
        let lastCheckedSponsor = '';

        // PIN Activation Functions
        function validatePinFormat(pin) {
            const regex = /^[A-Z0-9]+$/;
            return pin.length >= 8 && pin.length <= 16 && regex.test(pin);
        }

        function showPinCheckingState() {
            checkPinBtn.classList.add('btn-checking');
            checkPinText.textContent = 'Mengecek...';
            checkPinSpinner.classList.remove('d-none');
            pinStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi PIN...';
            pinStatus.className = 'verification-status status-checking';
        }

        function hidePinCheckingState() {
            checkPinBtn.classList.remove('btn-checking');
            checkPinText.textContent = 'Verifikasi';
            checkPinSpinner.classList.add('d-none');
        }

        function resetPinStatus() {
            pinInput.classList.remove('is-valid', 'is-invalid');
            pinFeedback.textContent = '';
            pinValidFeedback.classList.add('d-none');
            pinStatus.textContent = '';
            // Reset global status
            window.pinValidationStatus.isValid = false;
            window.pinValidationStatus.lastChecked = '';
        }

        async function checkPinActivation(pin) {
            if (!validatePinFormat(pin)) {
                pinInput.classList.add('is-invalid');
                pinFeedback.textContent = 'PIN harus 8-16 karakter, hanya huruf kapital dan angka';
                pinStatus.innerHTML = '<i class="fas fa-times-circle"></i> Format PIN tidak valid';
                pinStatus.className = 'verification-status status-invalid';
                window.pinValidationStatus.isValid = false;
                return false;
            }

            showPinCheckingState();

            try {
                const formData = new FormData();
                formData.append('pin_aktivasi', pin);

                const form = document.getElementById('ref-register-form');
                const csrfInput = form.querySelector('input[name="_token"]');
                if (csrfInput) {
                    formData.append('_token', csrfInput.value);
                }

                const response = await fetch('/member/check-pin', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();
                hidePinCheckingState();

                if (response.ok) {
                    if (data.valid) {
                        // PIN valid
                        pinInput.classList.remove('is-invalid');
                        pinInput.classList.add('is-valid');
                        pinValidFeedback.classList.remove('d-none');
                        pinStatus.innerHTML =
                            `<i class="fas fa-check-circle"></i> ${data.message || 'PIN valid dan tersedia!'}`;
                        pinStatus.className = 'verification-status status-valid';

                        // Set status global dengan benar
                        window.pinValidationStatus.isValid = true;
                        window.pinValidationStatus.lastChecked = pin;

                        if (data.info) {
                            pinStatus.innerHTML += `<br><small>${data.info}</small>`;
                        }
                    } else {
                        // PIN tidak valid
                        pinInput.classList.remove('is-valid');
                        pinInput.classList.add('is-invalid');
                        pinFeedback.textContent = data.message || 'PIN tidak valid atau sudah digunakan';
                        pinStatus.innerHTML = '<i class="fas fa-times-circle"></i> PIN tidak valid';
                        pinStatus.className = 'verification-status status-invalid';

                        // Reset status global
                        window.pinValidationStatus.isValid = false;
                        window.pinValidationStatus.lastChecked = '';
                    }
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan saat memverifikasi PIN');
                }

            } catch (error) {
                hidePinCheckingState();
                console.error('Error checking PIN:', error);
                pinInput.classList.add('is-invalid');
                pinFeedback.textContent = 'Terjadi kesalahan saat memverifikasi PIN. Silakan coba lagi.';
                pinStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                pinStatus.className = 'verification-status status-invalid';

                // Reset status global
                window.pinValidationStatus.isValid = false;
                window.pinValidationStatus.lastChecked = '';
            }
        }

        // Sponsor Code Functions
        function validateSponsorFormat(sponsor) {
            const regex = /^[A-Za-z0-9]+$/;
            return sponsor.length >= 3 && sponsor.length <= 20 && regex.test(sponsor);
        }

        function showSponsorCheckingState() {
            checkSponsorBtn.classList.add('btn-checking');
            checkSponsorText.textContent = 'Mengecek...';
            checkSponsorSpinner.classList.remove('d-none');
            sponsorStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi sponsor...';
            sponsorStatus.className = 'verification-status status-checking';
        }

        function hideSponsorCheckingState() {
            checkSponsorBtn.classList.remove('btn-checking');
            checkSponsorText.textContent = 'Verifikasi';
            checkSponsorSpinner.classList.add('d-none');
        }

        function resetSponsorStatus() {
            sponsorInput.classList.remove('is-valid', 'is-invalid');
            sponsorFeedback.textContent = '';
            sponsorValidFeedback.classList.add('d-none');
            sponsorStatus.textContent = '';
            sponsorInfoBanner.classList.add('d-none');
            // Reset global status
            window.sponsorValidationStatus.isValid = false;
            window.sponsorValidationStatus.lastChecked = '';
        }

        async function checkSponsorCode(sponsor) {
            if (!validateSponsorFormat(sponsor)) {
                sponsorInput.classList.add('is-invalid');
                sponsorFeedback.textContent = 'Kode sponsor harus 3-20 karakter alfanumerik';
                sponsorStatus.innerHTML = '<i class="fas fa-times-circle"></i> Format sponsor tidak valid';
                sponsorStatus.className = 'verification-status status-invalid';
                window.sponsorValidationStatus.isValid = false;
                return false;
            }

            showSponsorCheckingState();

            try {
                const formData = new FormData();
                formData.append('sponsor_code', sponsor);

                const form = document.getElementById('ref-register-form');
                const csrfInput = form.querySelector('input[name="_token"]');
                if (csrfInput) {
                    formData.append('_token', csrfInput.value);
                }

                const response = await fetch('/member/check-sponsor', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();
                hideSponsorCheckingState();

                if (response.ok) {
                    if (data.valid) {
                        // Sponsor valid
                        sponsorInput.classList.remove('is-invalid');
                        sponsorInput.classList.add('is-valid');
                        sponsorValidFeedback.classList.remove('d-none');
                        sponsorStatus.innerHTML = '<i class="fas fa-check-circle"></i> Sponsor ditemukan!';
                        sponsorStatus.className = 'verification-status status-valid';

                        // Set status global dengan benar
                        window.sponsorValidationStatus.isValid = true;
                        window.sponsorValidationStatus.lastChecked = sponsor;

                        // Show sponsor info
                        if (data.sponsor_info) {
                            sponsorInfo.innerHTML = `
                        <strong>${data.sponsor_info.name}</strong><br>
                        <small>ID: ${data.sponsor_info.member_id || sponsor} | Level: ${data.sponsor_info.level || 'Member'}</small>
                    `;
                            sponsorInfoBanner.classList.remove('d-none');
                        }

                        // Update hidden ref field
                        const refInput = document.getElementById('ref');
                        if (refInput) {
                            refInput.value = sponsor;
                        }
                    } else {
                        // Sponsor tidak ditemukan
                        sponsorInput.classList.remove('is-valid');
                        sponsorInput.classList.add('is-invalid');
                        sponsorFeedback.textContent = data.message || 'Kode sponsor tidak ditemukan';
                        sponsorStatus.innerHTML = '<i class="fas fa-times-circle"></i> Sponsor tidak ditemukan';
                        sponsorStatus.className = 'verification-status status-invalid';

                        // Reset status global
                        window.sponsorValidationStatus.isValid = false;
                        window.sponsorValidationStatus.lastChecked = '';
                        sponsorInfoBanner.classList.add('d-none');
                    }
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan saat memverifikasi sponsor');
                }

            } catch (error) {
                hideSponsorCheckingState();
                console.error('Error checking sponsor:', error);
                sponsorInput.classList.add('is-invalid');
                sponsorFeedback.textContent = 'Terjadi kesalahan saat memverifikasi sponsor. Silakan coba lagi.';
                sponsorStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                sponsorStatus.className = 'verification-status status-invalid';

                // Reset status global
                window.sponsorValidationStatus.isValid = false;
                window.sponsorValidationStatus.lastChecked = '';
                sponsorInfoBanner.classList.add('d-none');
            }
        }


        // Event Listeners
        // PIN Verification
        checkPinBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const pin = pinInput.value.trim();
            if (!pin) {
                pinInput.focus();
                return;
            }
            checkPinActivation(pin);
        });

        pinInput.addEventListener('input', function(e) {
            resetPinStatus();
            // Format input - hanya huruf kapital dan angka
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        pinInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkPinBtn.click();
            }
        });

        // Sponsor Verification
        checkSponsorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const sponsor = sponsorInput.value.trim();
            if (!sponsor) {
                sponsorInput.focus();
                return;
            }
            checkSponsorCode(sponsor);
        });

        sponsorInput.addEventListener('input', function(e) {
            resetSponsorStatus();
            // Format input - hanya alfanumerik
            this.value = this.value.replace(/[^A-Za-z0-9]/g, '');

            // Update hidden ref field
            const refInput = document.getElementById('ref');
            if (refInput) {
                refInput.value = this.value;
            }
        });

        sponsorInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                checkSponsorBtn.click();
            }
        });

        // Auto-load sponsor from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');
        if (ref) {
            sponsorInput.value = ref;
            const refInput = document.getElementById('ref');
            if (refInput) refInput.value = ref;
            // Auto-check sponsor setelah delay
            setTimeout(() => {
                checkSponsorCode(ref);
            }, 500);
        }

        // Make validation functions available globally
        window.isPinValid = function() {
            const pin = pinInput.value.trim();
            return isPinValid && pin === lastCheckedPin;
        };

        window.isSponsorValid = function() {
            const sponsor = sponsorInput.value.trim();
            return isSponsorValid && sponsor === lastCheckedSponsor;
        };

        // Update step validation
        if (window.validateStep) {
            const originalValidateStep = window.validateStep;
            window.validateStep = function(step) {
                if (step === 1) {
                    let isValid = true;

                    // Check PIN validation
                    if (!window.isPinValid()) {
                        pinInput.classList.add('is-invalid');
                        pinFeedback.textContent = 'PIN aktivasi harus diverifikasi terlebih dahulu';
                        isValid = false;
                    }

                    // Check Sponsor validation
                    if (!window.isSponsorValid()) {
                        sponsorInput.classList.add('is-invalid');
                        sponsorFeedback.textContent = 'Kode sponsor harus diverifikasi terlebih dahulu';
                        isValid = false;
                    }

                    if (!isValid) {
                        if (window.toastr) {
                            toastr.error('Silakan verifikasi PIN aktivasi dan kode sponsor terlebih dahulu');
                        }
                    }

                    return isValid;
                }

                return originalValidateStep(step);
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('no_telp');
            const checkWhatsAppBtn = document.getElementById('checkWhatsApp');
            const checkWhatsAppText = document.getElementById('checkWhatsAppText');
            const checkWhatsAppSpinner = document.getElementById('checkWhatsAppSpinner');
            const phoneFeedback = document.getElementById('phoneFeedback');
            const phoneValidFeedback = document.getElementById('phoneValidFeedback');
            const whatsappStatus = document.getElementById('whatsappStatus');

            let isPhoneValid = false;
            let lastCheckedPhone = '';
            let checkTimeout;

            // Format no_telp number for Indonesia
            function formatPhoneNumber(no_telp) {
                // Remove all non-numeric characters
                let cleaned = no_telp.replace(/\D/g, '');

                // Handle different formats
                if (cleaned.startsWith('62')) {
                    // Already in international format
                    return '+' + cleaned;
                } else if (cleaned.startsWith('0')) {
                    // Indonesian format starting with 0
                    return '+62' + cleaned.substring(1);
                } else if (cleaned.startsWith('8')) {
                    // Missing leading 0
                    return '+628' + cleaned.substring(1);
                } else if (cleaned.length > 0) {
                    // Assume Indonesian number
                    return '+62' + cleaned;
                }

                return cleaned;
            }

            // Validate Indonesian no_telp number format
            function validatePhoneFormat(no_telp) {
                const cleaned = no_telp.replace(/\D/g, '');

                // Indonesian no_telp number validation
                // Should be 10-13 digits after country code
                if (cleaned.startsWith('62')) {
                    // +62 format
                    return cleaned.length >= 12 && cleaned.length <= 15;
                } else if (cleaned.startsWith('0')) {
                    // 0xxx format
                    return cleaned.length >= 10 && cleaned.length <= 13;
                } else if (cleaned.startsWith('8')) {
                    // 8xxx format (missing 0)
                    return cleaned.length >= 9 && cleaned.length <= 12;
                }

                return false;
            }

            // Show checking state
            function showWhatsAppCheckingState() {
                checkWhatsAppBtn.classList.add('btn-checking');
                checkWhatsAppText.textContent = 'Mengecek...';
                checkWhatsAppSpinner.classList.remove('d-none');
                whatsappStatus.innerHTML = '<i class="fab fa-whatsapp fa-spin"></i> Memverifikasi WhatsApp...';
                whatsappStatus.className = 'whatsapp-status whatsapp-checking';
            }

            // Hide checking state
            function hideWhatsAppCheckingState() {
                checkWhatsAppBtn.classList.remove('btn-checking');
                checkWhatsAppText.textContent = 'Cek WA';
                checkWhatsAppSpinner.classList.add('d-none');
            }

            // Reset no_telp status
            function resetPhoneStatus() {
                phoneInput.classList.remove('is-valid', 'is-invalid');
                phoneFeedback.textContent = '';
                phoneValidFeedback.classList.add('d-none');
                whatsappStatus.textContent = '';
                isPhoneValid = false;
            }

            // Check WhatsApp availability
            async function checkWhatsAppAvailability(no_telp) {
                const formattedPhone = formatPhoneNumber(no_telp);

                if (!validatePhoneFormat(no_telp)) {
                    phoneInput.classList.add('is-invalid');
                    phoneFeedback.textContent = 'Format nomor HP tidak valid untuk Indonesia';
                    whatsappStatus.innerHTML = '<i class="fas fa-times-circle"></i> Format tidak valid';
                    whatsappStatus.className = 'whatsapp-status whatsapp-invalid';
                    return false;
                }

                showWhatsAppCheckingState();

                try {
                    const formData = new FormData();
                    formData.append('no_telp', formattedPhone);

                    // Ambil CSRF dari form
                    const form = document.getElementById('ref-register-form');
                    const csrfInput = form.querySelector('input[name="_token"]');
                    if (csrfInput) {
                        formData.append('_token', csrfInput.value);
                    }

                    const response = await fetch('/member/check-whatsapp', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    hideWhatsAppCheckingState();

                    if (response.ok) {
                        if (data.valid) {
                            // WhatsApp valid
                            phoneInput.classList.remove('is-invalid');
                            phoneInput.classList.add('is-valid');
                            phoneValidFeedback.classList.remove('d-none');

                            let statusText = '<i class="fab fa-whatsapp"></i> WhatsApp aktif!';
                            if (data.info) {
                                statusText += `<br><small>${data.info}</small>`;
                            }

                            whatsappStatus.innerHTML = statusText;
                            whatsappStatus.className = 'whatsapp-status whatsapp-valid';
                            isPhoneValid = true;
                            lastCheckedPhone = formattedPhone;

                            // Update input with formatted number
                            phoneInput.value = formattedPhone;

                        } else {
                            // WhatsApp tidak ditemukan atau tidak aktif
                            phoneInput.classList.remove('is-valid');
                            phoneInput.classList.add('is-invalid');
                            phoneFeedback.textContent = data.message ||
                                'Nomor WhatsApp tidak ditemukan atau tidak aktif';

                            let statusIcon = '<i class="fas fa-times-circle"></i>';
                            if (data.reason === 'not_whatsapp') {
                                statusIcon = '<i class="fas fa-exclamation-triangle"></i>';
                                whatsappStatus.className = 'whatsapp-status whatsapp-warning';
                            } else {
                                whatsappStatus.className = 'whatsapp-status whatsapp-invalid';
                            }

                            whatsappStatus.innerHTML =
                                `${statusIcon} ${data.message || 'WhatsApp tidak ditemukan'}`;
                            isPhoneValid = false;
                        }
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan saat memverifikasi WhatsApp');
                    }

                } catch (error) {
                    hideWhatsAppCheckingState();
                    console.error('Error checking WhatsApp:', error);

                    phoneInput.classList.add('is-invalid');
                    phoneFeedback.textContent =
                        'Terjadi kesalahan saat memverifikasi WhatsApp. Silakan coba lagi.';
                    whatsappStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error verifikasi';
                    whatsappStatus.className = 'whatsapp-status whatsapp-invalid';
                    isPhoneValid = false;

                    if (window.toastr) {
                        toastr.error('Gagal memverifikasi WhatsApp. Silakan coba lagi.');
                    }
                }
            }

            // Event listeners
            checkWhatsAppBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const no_telp = phoneInput.value.trim();

                if (!no_telp) {
                    phoneInput.focus();
                    return;
                }

                checkWhatsAppAvailability(no_telp);
            });

            phoneInput.addEventListener('input', function(e) {
                clearTimeout(checkTimeout);
                resetPhoneStatus();

                // Format input - hanya angka dan + di awal
                let value = this.value;
                if (value.startsWith('+')) {
                    value = '+' + value.substring(1).replace(/\D/g, '');
                } else {
                    value = value.replace(/\D/g, '');
                }
                this.value = value;

                // Real-time format validation
                if (value && !validatePhoneFormat(value)) {
                    this.classList.add('is-invalid');
                    phoneFeedback.textContent = 'Format: 08xxx atau +62xxx (10-13 digit)';
                }

                // Auto-check after 2 seconds of no typing (only if format is valid)
                if (value && validatePhoneFormat(value)) {
                    checkTimeout = setTimeout(() => {
                        checkWhatsAppAvailability(value);
                    }, 2000);
                }
            });

            phoneInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    checkWhatsAppBtn.click();
                }

                // Allow numbers, +, and control keys
                const char = String.fromCharCode(e.which);
                if (!/[0-9+]/.test(char) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });

            phoneInput.addEventListener('blur', function(e) {
                const no_telp = this.value.trim();
                if (no_telp && validatePhoneFormat(no_telp)) {
                    // Auto-format on blur
                    this.value = formatPhoneNumber(no_telp);
                }
            });

            // Make validation function available globally
            window.isPhoneValid = function() {
                const no_telp = formatPhoneNumber(phoneInput.value.trim());
                return isPhoneValid && no_telp === lastCheckedPhone;
            };

            // Integration with existing validation
            if (window.validateStep) {
                const originalValidateStep = window.validateStep;
                window.validateStep = function(step) {
                    if (step === 2) {
                        // Check if no_telp validation is required
                        const no_telp = phoneInput.value.trim();
                        if (no_telp && !window.isPhoneValid()) {
                            phoneInput.classList.add('is-invalid');
                            phoneFeedback.textContent = 'Nomor WhatsApp harus diverifikasi terlebih dahulu';

                            if (window.toastr) {
                                toastr.error('Silakan verifikasi nomor WhatsApp terlebih dahulu');
                            }

                            phoneInput.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            return false;
                        }
                    }

                    return originalValidateStep(step);
                };
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sponsorValue = sponsorInput.value.trim();
            if (sponsorValue) {
                // Auto-check sponsor setelah delay
                setTimeout(() => {
                    checkSponsorCode(sponsorValue);
                }, 500);
            }
        });

        // Utility function untuk WhatsApp link
        function generateWhatsAppLink(no_telp, message = '') {
            const cleanPhone = no_telp.replace(/\D/g, '');
            const formattedPhone = cleanPhone.startsWith('62') ? cleanPhone : '62' + cleanPhone.substring(1);
            const encodedMessage = encodeURIComponent(message);

            console.log(formattedPhone)
            console.log(encodedMessage)
            return `https://wa.me/${formattedPhone}${message ? '?text=' + encodedMessage : ''}`;
        }

        // Function untuk test WhatsApp (opsional)
        function testWhatsApp(no_telp) {
            const link = generateWhatsAppLink(no_telp, 'Test pesan dari sistem registrasi');
            window.open(link, '_blank');
        }
    </script>
@endpush
