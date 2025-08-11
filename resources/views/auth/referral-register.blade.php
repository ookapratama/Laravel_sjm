@extends('layouts.front')
@section('title', 'Registrasi Member')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/css/formValidation.min.css"
        integrity="sha512-B9GRVQaYJ7aMZO3WC2UvS9xds1D+gWQoNiXiZYRlqIVszL073pHXi0pxWxVycBk0fnacKIE3UHuWfSeETDCe7w=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .js-step {
            display: none;
        }

        .js-step.active {
            display: block;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }

        .step-item {
            display: flex;
            align-items: center;
            color: #6c757d;
        }

        .step-item.active {
            color: #ffc107;
        }

        .step-item.completed {
            color: #28a745;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        .step-item.active .step-number {
            background: #ffc107;
            color: #000;
        }

        .step-item.completed .step-number {
            background: #28a745;
        }

        .step-line {
            width: 100px;
            height: 2px;
            background: #6c757d;
            margin: 0 10px;
        }

        .step-item.completed+.step-item .step-line {
            background: #28a745;
        }

        .wizard-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        /* Tambahan untuk invalid feedback */
        .is-invalid+.invalid-feedback {
            display: block;
            /* Pastikan feedback terlihat saat input invalid */
        }

        @media (max-width: 768px) {
            .step-line {
                width: 50px;
            }

            .step-text {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-5" id="register">
        <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="300">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-dark text-warning">
                        <h2 class="mb-0">Registrasi Member</h2>
                        <small>Masukkan kode referal & PIN aktivasi Anda</small>
                    </div>

                    <div class="card-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
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
                                <div class="step-text">Alamat</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="5">
                                <div class="step-number">5</div>
                                <div class="step-text">Rekening</div>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div id="errorContainer" class="alert alert-danger d-none">
                            <ul class="mb-0" id="errorList"></ul>
                        </div>

                        <!-- Banner Sponsor -->
                        <div id="sponsorBanner" class="alert alert-info d-none">
                            <strong>Sponsor:</strong>
                            <span id="sponsorText"></span>
                        </div>

                        <form id="ref-register-form" action="{{ route('referral.register.store') }}" method="POST"
                            novalidate>
                            @csrf <!-- Laravel CSRF token directive -->
                            <input type="hidden" name="ref" id="ref" value="">

                            <!-- Step 1: PIN & Sponsor -->
                            <div class="js-step active" data-step="1">
                                <h4 class="mb-3">Step 1: PIN Aktivasi & Kode Sponsor</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="pin_aktivasi" class="form-label">PIN Aktivasi</label>
                                        <input type="text" name="pin_aktivasi" id="pin_aktivasi" class="form-control"
                                            required>
                                        <small class="text-muted">Diperoleh dari upline/admin saat membeli PIN.</small>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sponsor_code_display" class="form-label">Kode Sponsor</label>
                                        <input type="text" id="sponsor_code_display" name="sponsor_code"
                                            class="form-control" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Data Diri -->
                            <div class="js-step" data-step="2">
                                <h4 class="mb-3">Step 2: Data Diri</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nama Lengkap</label>
                                        <input type="text" name="name" id="name" class="form-control" required
                                            autocomplete="name">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">No. HP / WA</label>
                                        <input type="tel" name="phone" id="phone" class="form-control" required
                                            autocomplete="tel">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email (opsional)</label>
                                        <input type="email" name="email" id="email" class="form-control"
                                            autocomplete="email">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Kelamin</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin"
                                                id="jenis_kelamin_pria" value="pria" required>
                                            <label class="form-check-label" for="jenis_kelamin_pria">Pria</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="jenis_kelamin"
                                                id="jenis_kelamin_wanita" value="wanita" required>
                                            <label class="form-check-label" for="jenis_kelamin_wanita">Wanita</label>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="no_ktp" class="form-label">No. KTP (opsional)</label>
                                        <input type="text" name="no_ktp" id="no_ktp" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                                            class="form-control" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Agama</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_islam" value="islam" required>
                                            <label class="form-check-label" for="agama_islam">Islam</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_kristen" value="kristen" required>
                                            <label class="form-check-label" for="agama_kristen">Kristen</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_katolik" value="katolik" required>
                                            <label class="form-check-label" for="agama_katolik">Katolik</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_budha" value="budha" required>
                                            <label class="form-check-label" for="agama_budha">Budha</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_hindu" value="hindu" required>
                                            <label class="form-check-label" for="agama_hindu">Hindu</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="agama"
                                                id="agama_lainnya" value="lainnya" required>
                                            <label class="form-check-label" for="agama_lainnya">Lainnya</label>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Akun Login -->
                            <div class="js-step" data-step="3">
                                <h4 class="mb-3">Step 3: Data Akun Login</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" name="username" id="username" class="form-control"
                                            required autocomplete="username">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" name="password" id="password" class="form-control"
                                            required minlength="6" autocomplete="new-password">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="form-control" required minlength="6" autocomplete="new-password">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Alamat -->
                            <div class="js-step" data-step="4">
                                <h4 class="mb-3">Step 4: Data Alamat</h4>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                                        <textarea name="alamat" id="alamat" class="form-control" rows="2" required></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="rt" class="form-label">RT</label>
                                        <input type="text" name="rt" id="rt" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="rw" class="form-label">RW</label>
                                        <input type="text" name="rw" id="rw" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="desa" class="form-label">Desa/Kelurahan</label>
                                        <input type="text" name="desa" id="desa" class="form-control"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="kecamatan" class="form-label">Kecamatan</label>
                                        <input type="text" name="kecamatan" id="kecamatan" class="form-control"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="kota" class="form-label">Kota/Kabupaten</label>
                                        <input type="text" name="kota" id="kota" class="form-control"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="kode_pos" class="form-label">Kode Pos</label>
                                        <input type="text" name="kode_pos" id="kode_pos" class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Rekening & Ahli Waris -->
                            <div class="js-step" data-step="5">
                                <h4 class="mb-3">Step 5: Data Rekening & Ahli Waris</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nama_rekening" class="form-label">Nama di Rekening</label>
                                        <input type="text" name="nama_rekening" id="nama_rekening"
                                            class="form-control" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                        <input type="text" name="nomor_rekening" id="nomor_rekening"
                                            class="form-control" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="nama_bank" class="form-label">Nama Bank</label>
                                        <input type="text" name="nama_bank" id="nama_bank" list="bankList"
                                            class="form-control" required placeholder="Pilih / ketik nama bank">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nama_ahli_waris" class="form-label">Nama Ahli Waris (opsional)</label>
                                        <input type="text" name="nama_ahli_waris" id="nama_ahli_waris"
                                            class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hubungan_ahli_waris" class="form-label">Hubungan Ahli Waris
                                            (opsional)</label>
                                        <input type="text" name="hubungan_ahli_waris" id="hubungan_ahli_waris"
                                            class="form-control">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="agree"
                                                id="agree" value="1" required>
                                            <label class="form-check-label" for="agree">Saya menyetujui Syarat &
                                                Ketentuan</label>
                                        </div>
                                        <div class="invalid-feedback">Anda harus menyetujui Syarat & Ketentuan.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Wizard Navigation Buttons -->
                            <div class="wizard-buttons">
                                <button type="button" id="prevBtn" class="btn btn-secondary"
                                    style="display: none;">Sebelumnya</button>
                                <button type="button" id="nextBtn" class="btn btn-warning">Selanjutnya</button>
                                <button type="submit" id="submitBtn" class="btn btn-success"
                                    style="display: none;">Daftar Sekarang</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            Sudah punya akun? <a href="{{ route('login') }}" class="text-warning">Masuk</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <datalist id="bankList"></datalist>
@endsection

@push('scripts')

    <script>
        (function() {
            // script wizard step nya
            'use strict';

            // Wizard variables
            let currentStep = 1;
            const totalSteps = 5;
            const errorContainer = document.getElementById('errorContainer');
            const errorList = document.getElementById('errorList');

            // Validation rules for each step
            const validationRules = {
                1: { // Step 1: PIN & Sponsor
                    pin_aktivasi: {
                        required: true,
                        minLength: 8,
                        pattern: /^[A-Z0-9]+$/,
                        message: 'PIN aktivasi harus diisi minimal 8 karakter (huruf kapital dan angka)'
                    },
                    sponsor_code: {
                        required: true,
                        minLength: 3,
                        pattern: /^[A-Za-z0-9]+$/,
                        message: 'Kode sponsor harus diisi minimal 3 karakter alfanumerik'
                    }
                },
                2: { // Step 2: Data Diri
                    name: {
                        required: true,
                        minLength: 3,
                        pattern: /^[a-zA-Z\s.,']+$/,
                        message: 'Nama lengkap harus diisi minimal 3 karakter (hanya huruf dan tanda baca umum)'
                    },
                    phone: {
                        required: true,
                        pattern: /^(\+?62|0)[0-9]{8,13}$/,
                        message: 'Nomor HP harus valid format Indonesia (08xx atau +62xxx)'
                    },
                    email: {
                        required: false,
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
                4: { // Step 4: Alamat
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
                5: { // Step 5: Rekening
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

                // Show global errors if any
                if (errors.length > 0) {
                    errorList.innerHTML = errors.map(error => `<li>${error}</li>`).join('');
                    errorContainer.classList.remove('d-none');

                    // Scroll to first invalid field
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

                    if (res.ok) { // Status 2xx
                        if (window.toastr) toastr.success(data.message ||
                            'Registrasi berhasil! Anda akan dialihkan.');
                        if (data.redirect) {
                            setTimeout(() => { // Beri waktu toastr tampil
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            // Default redirect jika tidak ada dari server
                            setTimeout(() => {
                                window.location.href = "{{ route('member') }}";
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
    </script>
@endpush
