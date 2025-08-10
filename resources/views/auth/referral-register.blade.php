@extends('layouts.front')
@section('title', 'Registrasi Member')

@push('styles')
    <style>
        .wizard-step {
            display: none;
        }

        .wizard-step.active {
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

                        <form id="ref-register-form" action="{{ route('referral.register.store') }}" method="POST" novalidate>
                            @csrf <!-- Laravel CSRF token directive -->
                            <input type="hidden" name="ref" id="ref" value="">

                            <!-- Step 1: PIN & Sponsor -->
                            <div class="wizard-step active" data-step="1">
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
                            <div class="wizard-step" data-step="2">
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
                            <div class="wizard-step" data-step="3">
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
                            <div class="wizard-step" data-step="4">
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
                            <div class="wizard-step" data-step="5">
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
            if (typeof AOS !== 'undefined') {
                AOS.init();
            }

            let currentStep = 1;
            const totalSteps = 5;

            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const submitBtn = document.getElementById('submitBtn');
            const errorContainer = document.getElementById('errorContainer');
            const errorList = document.getElementById('errorList');

            // Helper to clear all validation feedback
            function clearValidationFeedback() {
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                errorContainer.classList.add('d-none');
                errorList.innerHTML = '';
            }

            function showStep(step) {
                clearValidationFeedback(); // Clear feedback when changing steps

                document.querySelectorAll('.wizard-step').forEach(el => {
                    el.classList.remove('active');
                });

                document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');

                document.querySelectorAll('.step-item').forEach((item, index) => {
                    const stepNum = index + 1;
                    item.classList.remove('active', 'completed');

                    if (stepNum < step) {
                        item.classList.add('completed');
                    } else if (stepNum === step) {
                        item.classList.add('active');
                    }
                });

                prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
                nextBtn.style.display = step < totalSteps ? 'inline-block' : 'none';
                submitBtn.style.display = step === totalSteps ? 'inline-block' : 'none';

                // Scroll to top of the card when changing steps
                const card = document.querySelector('.card');
                if (card) {
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }

            function validateStep(step) {
                const stepElement = document.querySelector(`[data-step="${step}"]`);
                const requiredFields = stepElement.querySelectorAll('[required]');
                let isValid = true;
                const errors = [];

                // Clear previous invalid states for the current step
                stepElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                stepElement.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');


                requiredFields.forEach(field => {
                    let fieldIsValid = true;
                    let errorMessage = '';

                    if (field.type === 'radio') {
                        const radioGroup = stepElement.querySelectorAll(`[name="${field.name}"]`);
                        const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                        if (!isChecked) {
                            fieldIsValid = false;
                            errorMessage = 'Pilihan ini wajib diisi.';
                            // Mark all radios in the group as invalid to show feedback near one of them
                            radioGroup.forEach(radio => radio.classList.add('is-invalid'));
                        } else {
                            radioGroup.forEach(radio => radio.classList.remove('is-invalid'));
                        }
                    } else if (field.type === 'checkbox') {
                        if (!field.checked) {
                            fieldIsValid = false;
                            errorMessage = field.labels[0] ? field.labels[0].textContent + ' harus dicentang.' :
                                'Bidang ini wajib dicentang.';
                            field.classList.add('is-invalid');
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    } else {
                        if (!field.value.trim()) {
                            fieldIsValid = false;
                            errorMessage = 'Bidang ini wajib diisi.';
                            field.classList.add('is-invalid');
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    }

                    if (!fieldIsValid) {
                        isValid = false;
                        if (errorMessage) {
                            // Find or create invalid-feedback element
                            let feedback = field.nextElementSibling;
                            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                                feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback';
                                field.parentNode.insertBefore(feedback, field.nextSibling);
                            }
                            feedback.textContent = errorMessage;
                        }
                    }
                });

                if (step === 3) {
                    const password = stepElement.querySelector('[name="password"]');
                    const confirmPassword = stepElement.querySelector('[name="password_confirmation"]');

                    if (password && confirmPassword) {
                        if (password.value !== confirmPassword.value) {
                            isValid = false;
                            confirmPassword.classList.add('is-invalid');
                            let feedback = confirmPassword.nextElementSibling;
                            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                                feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback';
                                confirmPassword.parentNode.insertBefore(feedback, confirmPassword.nextSibling);
                            }
                            feedback.textContent = 'Konfirmasi password tidak cocok.';
                        } else {
                            confirmPassword.classList.remove('is-invalid');
                            let feedback = confirmPassword.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = '';
                            }
                        }

                        if (password.value.length < 6) {
                            isValid = false;
                            password.classList.add('is-invalid');
                            let feedback = password.nextElementSibling;
                            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                                feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback';
                                password.parentNode.insertBefore(feedback, password.nextSibling);
                            }
                            feedback.textContent = 'Password minimal 6 karakter.';
                        } else {
                            password.classList.remove('is-invalid');
                            let feedback = password.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = '';
                            }
                        }
                    }
                }

                return isValid;
            }

            nextBtn.addEventListener('click', function() {
                if (validateStep(currentStep)) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        showStep(currentStep);
                    }
                } else {
                    // Scroll to the first invalid field
                    const firstInvalidField = document.querySelector('.is-invalid');
                    if (firstInvalidField) {
                        firstInvalidField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                    if (window.toastr) toastr.error('Harap lengkapi semua bidang yang wajib diisi.');
                }
            });

            prevBtn.addEventListener('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            showStep(1);


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
                    'UOB Indonesia', 'HSBC Indonesia', 'Standard Chartered Indonesia', 'Citibank N.A. Indonesia',
                    'ICBC Indonesia', 'Bank China Construction Bank Indonesia (CCB Indonesia)', 'Bank Commonwealth',
                    'QNB Indonesia', 'Bank Woori Saudara', 'Bank Shinhan Indonesia', 'Bank JTrust Indonesia',
                    'Bank MNC Internasional', 'Bank Artha Graha Internasional', 'Bank Capital Indonesia',
                    'Bank Maspion Indonesia', 'Bank Ina Perdana', 'Bank Index Selindo',
                    'Bank Victoria International', 'Bank Mayora', 'Bank Oke Indonesia', 'Bank Sahabat Sampoerna',
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
                                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
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
                            let parentStep = firstInvalidField.closest('.wizard-step');
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
