@extends('layouts.app')

@section('content')
    <div class="page-inner relative">
        <div class="absolute right-5 top-3 z-10 flex gap-2">
            <button onclick="zoomIn()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">＋</button>
            <button onclick="zoomOut()" class="px-3 py-1 bg-blue-600 text-white rounded btn-primary">－</button>
            <button onclick="navLeft()" class="px-3 py-1 bg-yellow-600 text-white rounded btn-warning">Prev</button>
            <button onclick="navRight()" class="px-3 py-1 bg-green-600  text-white rounded btn-success">Next</button>
        </div>

        <!-- Kanvas tree -->
        <div id="tree-scroll" class="overflow-auto w-full h-[85vh] border relative">
            <div id="tree-container"></div>

            <!-- Overlay panah -->
            <div class="tree-nav left"><button onclick="navLeft()">◀</button></div>
            <div class="tree-nav right"><button onclick="navRight()">▶</button></div>
            <div class="tree-nav up"><button onclick="navUp()">▲</button></div>
            <div class="tree-nav down"><button onclick="navDown()">▼</button></div>
        </div>
    </div>

    <!-- Tooltip -->
    <div id="tree-tooltip" class="hidden"></div>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Modal: Clone / Tambah / Add New Member -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah / Clone Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <ul class="nav nav-tabs" id="addTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tabCloneBtn" data-bs-toggle="tab" data-bs-target="#tabClone"
                                type="button">Clone</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tabTambahBtn" data-bs-toggle="tab" data-bs-target="#tabTambah"
                                type="button">
                                Tambah
                                <span id="pendingCountBadge" class="badge rounded-pill bg-danger ms-2 d-none">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tabAddNewBtn" data-bs-toggle="tab" data-bs-target="#tabAddNew"
                                type="button">Member Baru</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        {{-- ======== TAB CLONE ======== --}}
                        <div class="tab-pane fade show active" id="tabClone" role="tabpanel" aria-labelledby="tabCloneBtn">
                            <form id="cloneForm">
                                @csrf
                                <input type="hidden" name="parent_id" id="cloneParentId">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Posisi</label>
                                        <div class="form-control-plaintext">
                                            <span id="clonePositionLabel">-</span>
                                        </div>

                                        <input type="hidden" name="position" id="clonePosition" value="left">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bagan</label>
                                        <select class="form-select" name="bagan" id="cloneBagan" disabled>
                                            <option value="1" selected>Bagan 1</option>
                                        </select>
                                        <input type="hidden" name="bagan" value="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Base Cloning</label>
                                        <select class="form-select" name="use_login" id="cloneUseLogin">
                                            <option value="1" selected>Pakai ID yang login</option>
                                            <option value="0">Pakai ID parent</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Pilih PIN Aktivasi (multi)</label>
                                        <select class="form-select" name="pin_codes[]" id="pinCodes" multiple
                                            size="8" required></select>
                                        <small class="text-muted">Jumlah ID = banyaknya PIN yang dipilih.</small>
                                    </div>

                                    <div class="col-12 d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary" id="btnPreview">Preview
                                            Username/Referral</button>
                                        <div id="clonePreview" class="small"></div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" id="btnCloneSubmit">Clone & Tempel</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- ======== TAB TAMBAH (dari daftar pending) ======== --}}
                        <div class="tab-pane fade" id="tabTambah" role="tabpanel" aria-labelledby="tabTambahBtn">
                            <div id="userList" class="list-group"></div>
                        </div>

                        {{-- ======== TAB ADD NEW MEMBER ======== --}}
                        <div class="tab-pane fade" id="tabAddNew" role="tabpanel" aria-labelledby="tabAddNewBtn">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-plus me-2"></i>Member baru
                                    </h6>
                                </div>
                                <div class="card-body">
                                    {{-- <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Form untuk menambahkan member baru akan ditampilkan di sini.
                                        <br>
                                        <small class="text-muted">
                                            Placeholder ini akan diganti dengan modal versi yang telah Anda buat.
                                        </small>
                                    </div> --}}

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3">Quick Info</h6>
                                            <div class="mb-2">
                                                <strong>Parent ID:</strong>
                                                <span id="addNewParentId" class="badge bg-secondary">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Position:</strong>
                                                <span id="addNewPosition" class="badge bg-info">-</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Current Bagan:</strong>
                                                <span id="addNewBagan" class="badge bg-warning text-dark">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3">Actions</h6>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#memberModal">
                                                    <i class="fas fa-external-link-alt me-2"></i>Open Registration Form
                                                </button>
                                                {{-- <button type="button" class="btn btn-outline-secondary"
                                                    onclick="loadMemberTemplate()">
                                                    <i class="fas fa-file-alt me-2"></i>Load Template
                                                </button> --}}
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    {{-- <div class="text-center">
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-tools me-2"></i>
                                            This section is ready for your custom member registration modal
                                        </p>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="memberModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
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
                        <input type="hidden" name="register_method" value="from_tree">
                        <input type="hidden" name="tree_upline_id" id="treeUplineId">
                        <input type="hidden" name="tree_position" id="treePosition">
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
@endsection

<style>
    .tree-nav {
        position: absolute;
        z-index: 30;
        opacity: .9
    }

    .tree-nav.left {
        left: 10px;
        top: 50%;
        transform: translateY(-50%)
    }

    .tree-nav.right {
        right: 10px;
        top: 50%;
        transform: translateY(-50%)
    }

    .tree-nav.up {
        left: 50%;
        top: calc(var(--node-height, 80px) + 10px);
        transform: translateX(-50%);
    }

    .tree-nav.down {
        left: 50%;
        bottom: 10px;
        transform: translateX(-50%)
    }

    .tree-nav button {
        background: #60a5fa;
        border: none;
        color: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .15)
    }

    @media (max-width:640px) {
        .tree-nav button {
            padding: 8px 10px
        }
    }

    #tree-tooltip {
        position: absolute;
        background: #fff;
        border: 1px solid #ddd;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        pointer-events: none;
        z-index: 40
    }

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

    /* Tab Add New Member styles */
    #tabAddNew .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    #tabAddNew .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }

    #tabAddNew .btn {
        border-radius: 6px;
    }

    #tabAddNew .alert-info {
        border-left: 4px solid #0dcaf0;
        background-color: #e7f3ff;
        border-color: #b8daff;
    }
</style>

@push('scripts')
  <script>
    window.AUTH_USER_ID        = {{ auth()->user()->id }};
    window.AUTH_USER_ROLE      = {{ Js::from(auth()->user()->role ?? 'member') }};
    window.AUTH_USER_UPLINE_ID = {{ Js::from(auth()->user()->referrer_id ?? null) }};
    window.CURRENT_ROOT_ID     = {{ $root->id }};

    window.ROUTES = {
      pinsUnused:     {{ Js::from(route('pins.unused')) }},
      clonePreview:   {{ Js::from(route('tree.clone.preview')) }},
      cloneStore:     {{ Js::from(route('tree.clone.store')) }},
      treeIndex:      {{ Js::from(route('tree.index')) }},
      checkUsername:  "/member/check-username",
      checkPin:       "/member/check-pin",
      checkSponsor:   "/member/check-sponsor",
      checkWhatsApp:  "/member/check-whatsapp",
      availableUsers: "/tree/available-users"
    };
  </script>

  <script src="https://d3js.org/d3.v7.min.js"></script>
  <script src="{{ asset('assets/js/tree-core.js') }}"></script>
  <script src="{{ asset('assets/js/tree-registration.js') }}"></script>
@endpush
