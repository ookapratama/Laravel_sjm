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
                                type="button">Add New Member</button>
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
                                        <i class="fas fa-user-plus me-2"></i>Add New Member
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
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        (() => {
            "use strict";
            /* =============== STATE =============== */
            const AUTH_USER_ID = {{ auth()->user()->id }};
            window.currentRootId = {{ $root->id }};
            window.currentBagan = Number(localStorage.getItem('selectedBagan') || 1);

            let lastLoadedData = null;
            let svgSel = null,
                g = null;
            let currentZoomTransform = d3.zoomIdentity;

            let isLoading = false;
            let pendingController = null;

            const upStack = []; // riwayat untuk NAV UP
            const parentCache = new Map(); // cache parent: id -> parentId
            let firstRenderDone = false;

            /* =============== UTIL =============== */
            const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
            const toNum = (v) => {
                const n = Number(String(v ?? '').trim());
                return Number.isFinite(n) ? n : null;
            };

            function activeBagansFrom(d) {
                if (!d || typeof d !== 'object') return [];
                if (Array.isArray(d.active_bagans)) return d.active_bagans.map(Number);
                return Object.keys(d).filter(k => k.startsWith('is_active_bagan_') && d[k] == 1)
                    .map(k => parseInt(k.replace('is_active_bagan_', ''), 10));
            }

            function isActiveOnSelected(d) {
                return activeBagansFrom(d).includes(Number(window.currentBagan));
            }

            function topSafeOffset(nodeH) {
                const upBtn = document.querySelector('.tree-nav.up button');
                const upH = upBtn ? upBtn.getBoundingClientRect().height : 36;
                return upH + 18 + (nodeH / 2);
            }

            function shortName(s, max) {
                if (!s) return '';
                return s.length > max ? s.slice(0, max) + '…' : s;
            }

            // Normalisasi id & parent
            function normalizeIds(node) {
                if (!node || typeof node !== 'object') return node;

                node.id = toNum(node.id);
                node.parent_id = toNum(node.parent_id ?? node.upline_id);

                if (node.data && typeof node.data === 'object') {
                    node.data.id = toNum(node.data.id);
                    node.data.parent_id = toNum(node.data.parent_id ?? node.data.upline_id);
                }

                if (Array.isArray(node.children)) {
                    node.children.forEach(c => {
                        if (!c || typeof c !== 'object') return;
                        c.id = toNum(c.id);
                        c.parent_id = toNum(c.parent_id ?? c.upline_id);
                        if (c.data && typeof c.data === 'object') {
                            c.data.id = toNum(c.data.id);
                            c.data.parent_id = toNum(c.data.parent_id ?? c.data.upline_id);
                        }
                        if (Array.isArray(c.children)) {
                            c.children.forEach(g => {
                                if (!g || typeof g !== 'object') return;
                                g.id = toNum(g.id);
                                g.parent_id = toNum(g.parent_id ?? g.upline_id);
                                if (g.data && typeof g.data === 'object') {
                                    g.data.id = toNum(g.data.id);
                                    g.data.parent_id = toNum(g.data.parent_id ?? g.data.upline_id);
                                }
                            });
                        }
                    });
                }
                return node;
            }

            async function fetchJSON(url, opts = {}) {
                if (pendingController) pendingController.abort();
                pendingController = new AbortController();
                const res = await fetch(url, {
                    ...opts,
                    signal: pendingController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(opts.headers || {})
                    }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            }
            async function fetchTEXT(url) {
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const text = await res.text();
                return {
                    ok: res.ok,
                    text,
                    status: res.status
                };
            }

            function tryJSON(any) {
                if (any == null) return null;
                if (typeof any === 'object') return any;
                try {
                    return JSON.parse(any);
                } catch {
                    return null;
                }
            }

            function pickParentId(payload) {
                const d = tryJSON(payload) ?? {};
                const arr = [
                    d.parent_id, d.parentId, d.pid, d.parent,
                    d?.data?.parent_id, d?.data?.parentId,
                    d?.user?.upline_id, d?.user?.parent_id,
                    d.upline_id,
                    d.id
                ];
                for (const c of arr) {
                    const n = toNum(c);
                    if (n && n > 0) return n;
                }
                const nested = toNum(d?.parent?.id ?? d?.parent?.parent_id);
                return (nested && nested > 0) ? nested : null;
            }

            /* =============== PARENT RESOLVER =============== */
            async function resolveParentId(currentId) {
                const cur = toNum(currentId);
                if (!cur || cur <= 0) return null;

                if (parentCache.has(cur)) return parentCache.get(cur); // cache

                const local = [
                    lastLoadedData?.parent_id,
                    lastLoadedData?.upline_id,
                    lastLoadedData?.data?.parent_id,
                    lastLoadedData?.data?.upline_id
                ].map(toNum).find(n => n && n > 0);
                if (local) {
                    parentCache.set(cur, local);
                    return local;
                }

                try {
                    const r = await fetchTEXT(`/tree/parent/${cur}`);
                    if (r.ok) {
                        const pid = pickParentId(r.text);
                        if (pid && pid > 0) {
                            parentCache.set(cur, pid);
                            return pid;
                        }
                    }
                } catch {}

                try {
                    const r = await fetchTEXT(`/users/ajax/${cur}`);
                    if (r.ok) {
                        const pid = pickParentId(r.text);
                        if (pid && pid > 0) {
                            parentCache.set(cur, pid);
                            return pid;
                        }
                    }
                } catch {}

                try {
                    const r = await fetchTEXT(`/tree/node/${cur}`);
                    if (r.ok) {
                        const pid = pickParentId(r.text);
                        if (pid && pid > 0) {
                            parentCache.set(cur, pid);
                            return pid;
                        }
                    }
                } catch {}

                return null;
            }

            /* =============== ZOOM =============== */
            const zoomBehavior = d3.zoom().on('zoom', e => {
                currentZoomTransform = e.transform;
                if (g) g.attr('transform', currentZoomTransform);
            });

            function bindZoomIfNeeded() {
                if (!svgSel || !svgSel.node()) return false;
                if (!svgSel.node().__zoom) svgSel.call(zoomBehavior);
                return true;
            }
            window.zoomIn = () => {
                if (!bindZoomIfNeeded()) return;
                const t = currentZoomTransform.scale(1.2);
                svgSel.transition().duration(300).call(zoomBehavior.transform, t);
                currentZoomTransform = t;
            };
            window.zoomOut = () => {
                if (!bindZoomIfNeeded()) return;
                const t = currentZoomTransform.scale(0.83);
                svgSel.transition().duration(300).call(zoomBehavior.transform, t);
                currentZoomTransform = t;
            };
            window.resetZoom = () => {
                currentZoomTransform = d3.zoomIdentity;
                loadTree();
            };

            /* =============== LOAD =============== */
            async function loadTree() {
                if (isLoading) return;
                isLoading = true;

                const prev = document.querySelector('#tree-container svg');
                const keepT = prev ? d3.zoomTransform(prev) : null;

                try {
                    const data = await fetchJSON(`/tree/load/${window.currentRootId}?limit=3`);
                    if (data && data.parent_id == null && data.upline_id != null) data.parent_id = data.upline_id;
                    lastLoadedData = normalizeIds(data);

                    const cur = toNum(window.currentRootId);
                    const pid = toNum(lastLoadedData?.parent_id ?? lastLoadedData?.upline_id);
                    if (cur && pid && pid > 0) parentCache.set(cur, pid);

                    drawTree(lastLoadedData, true, (keepT && keepT.k) ? keepT : null);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        toastr?.error?.('Gagal memuat tree ');
                        console.log(e)
                    }
                } finally {
                    isLoading = false;
                }
            }
            window.loadTree = loadTree;

            /* =============== SKIN =============== */
            function appendGradients(sel) {
                const defs = sel.append('defs');
                const grad = (id, from, to) => {
                    const g = defs.append('linearGradient').attr('id', id)
                        .attr('x1', '0%').attr('y1', '0%').attr('x2', '100%').attr('y2', '100%');
                    g.append('stop').attr('offset', '0%').attr('stop-color', from);
                    g.append('stop').attr('offset', '100%').attr('stop-color', to);
                };
                grad('goldGradient', '#FFD700', '#000');
                grad('greenGradient', '#00c853', '#003300');
                grad('blueGradient', '#66ccff', '#003366');
                grad('grayGradient', '#9aa5b1', '#3c4a57');
            }

            function getNodeColor(d) {
                if (d.isAddButton) return 'url(#blueGradient)';
                return isActiveOnSelected(d) ? 'url(#greenGradient)' : 'url(#grayGradient)';
            }

            /* =============== DRAW =============== */
            function drawTree(data, preserveZoom = false, zoomOverride = null) {
                if (!data) return;

                const board = document.getElementById('tree-scroll');
                const W = board.clientWidth || 1200,
                    H = board.clientHeight || 750;

                const maxCols = Math.pow(2, 3 - 1);
                const hGap = clamp(Math.floor(W / (maxCols + 4)), 16, 48);
                const vGap = clamp(Math.floor(H / (3 + 3)), 60, 110);
                const NODE_W = clamp(Math.floor((W - (maxCols + 1) * hGap) / maxCols), 72, 110);
                const NODE_H = clamp(Math.floor(NODE_W * 0.9), 60, 100);
                const RADIUS = clamp(Math.floor(NODE_W * 0.16), 8, 14);
                const AVA = Math.floor(NODE_W * 0.38);

                // reset container → render ulang
                const container = document.getElementById('tree-container');
                container.innerHTML = '';
                const svgEl = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svgEl.setAttribute('width', W);
                svgEl.setAttribute('height', H);
                container.appendChild(svgEl);

                svgSel = d3.select(svgEl);
                appendGradients(svgSel);
                g = svgSel.append('g');

                const centerX = W / 2,
                    centerY = topSafeOffset(NODE_H);
                if (preserveZoom && zoomOverride) {
                    currentZoomTransform = zoomOverride;
                    if (currentZoomTransform.y < centerY) currentZoomTransform = d3.zoomIdentity.translate(centerX,
                        centerY).scale(zoomOverride.k);
                } else {
                    currentZoomTransform = d3.zoomIdentity.translate(centerX, centerY);
                }
                svgSel.call(zoomBehavior).call(zoomBehavior.transform, currentZoomTransform);

                const root = d3.hierarchy(data);
                root.eachBefore(d => {
                    if (d.children) {
                        d.children.sort((a, b) => {
                            if (a.data.position === 'left') return -1;
                            if (a.data.position === 'right') return 1;
                            return 0;
                        });
                    }
                    if (d.depth >= 2) d.children = null; // tampil 3 level
                });

                const getStarCount = () => {
                    const n = parseInt(String(window.currentBagan).trim(), 10);
                    return Math.max(0, Math.min(5, Number.isFinite(n) ? (n - 1) : 0));
                };

                const layout = d3.tree().nodeSize([hGap + NODE_W, vGap + NODE_H]);
                layout(root);

                // edges (pakai garis siku/elbow)
                g.append('g')
                    .attr('fill', 'none')
                    .attr('stroke', '#cbd5e1')
                    .attr('stroke-opacity', 0.65)
                    .attr('stroke-width', 1.2)
                    .selectAll('path')
                    .data(root.links())
                    .join('path')
                    .attr('d', d => {
                        return `
          M${d.source.x},${d.source.y}
          V${(d.source.y + d.target.y) / 2}
          H${d.target.x}
          V${d.target.y}
        `;
                    });

                // nodes
                const node = g.append('g').selectAll('g').data(root.descendants()).join('g')
                    .attr('transform', d => `translate(${d.x},${d.y})`)
                    .on('mouseover', showTooltip).on('mouseout', hideTooltip);

                node.append('rect')
                    .attr('x', -NODE_W / 2).attr('y', -NODE_H / 2)
                    .attr('width', NODE_W).attr('height', NODE_H).attr('rx', RADIUS)
                    .attr('fill', d => getNodeColor(d.data));

                node.filter(d => !d.data.isAddButton).append('image')
                    .attr("xlink:href", d => d.data.photo ?
                        `/${d.data.photo}` :
                        `/assets/img/profile.jpg`)
                    .attr('x', -AVA / 2).attr('y', -NODE_H / 2 + 6)
                    .attr('width', AVA).attr('height', AVA)
                    .attr('clip-path', `circle(${AVA/2}px at ${AVA/2}px ${AVA/2}px)`);

                node.filter(d => !d.data.isAddButton).append('text')
                    .attr('y', 10).attr('text-anchor', 'middle')
                    .text(() => '⭐️'.repeat(getStarCount()))
                    .style('font-size', Math.max(9, Math.floor(NODE_W * 0.11)) + 'px')
                    .attr('fill', 'gold');

                node.filter(d => !d.data.isAddButton).append('text')
                    .attr('y', NODE_H / 2 - 8).attr('text-anchor', 'middle')
                    .text(d => shortName(d.data.name || d.data.username || '', NODE_W <= 80 ? 7 : 9))
                    .attr('fill', d => isActiveOnSelected(d.data) ? '#fff' : '#cbd5e1')
                    .style('font-size', Math.max(10, Math.floor(NODE_W * 0.12)) + 'px');

                // Tombol + Tambah (buka modal: default tab Clone)
                const addNodes = node.filter(d => d.data.isAddButton);
                addNodes.style('cursor', 'pointer').on('click', (e, d) => {
                    e.stopPropagation();
                    const pos = d.data.position || d.parent?.data?.position || 'left';
                    const up = d.data.parent_id ?? d.parent?.data?.id ?? null;
                    if (!up) {
                        toastr?.warning?.('Upline tidak terdeteksi.');
                        return;
                    }
                    openAddModalUnified({
                        parentId: up,
                        position: pos,
                        mode: 'clone'
                    });
                });
                addNodes.append('text').attr('y', 2).attr('text-anchor', 'middle')
                    .text('+ Tambah').style('font-size', Math.max(10, Math.floor(NODE_W * 0.12)) + 'px').attr('fill',
                        '#fff');
            }

            /* =============== TOOLTIP =============== */
            function showTooltip(event, d) {
                const el = document.getElementById('tree-tooltip');
                if (!el || d.data.isAddButton) return;
                const aktif = isActiveOnSelected(d.data) ? 'Ya' : 'Tidak';
                el.innerHTML = `
          <strong>${d.data.name}</strong><br>
          Bagan P${window.currentBagan}: <b>${aktif}</b><br>
          Status: ${d.data.status}<br>
          Pairing: ${d.data.pairing_count ?? '-'}<br>
          Kiri: ${d.data.left_count ?? 0} • Kanan: ${d.data.right_count ?? 0}
        `;
                const box = document.getElementById('tree-scroll').getBoundingClientRect();
                el.style.left = `${event.clientX - box.left + 10}px`;
                el.style.top = `${event.clientY - box.top + 10}px`;
                el.classList.remove('hidden');
            }

            function hideTooltip() {
                document.getElementById('tree-tooltip')?.classList.add('hidden');
            }

            /* =============== NAV =============== */
            function realChild(side) {
                const kids = (lastLoadedData?.children || [])
                    .filter(c => c?.position === side && !c.isAddButton && Number.isFinite(c.id));
                return kids.length ? kids[0] : null;
            }

            function goDown(toId) {
                const to = toNum(toId);
                if (!to || to <= 0) return;
                if (Number.isFinite(window.currentRootId) && window.currentRootId !== to) {
                    upStack.push(window.currentRootId);
                }
                window.currentRootId = to;
                loadTree();
            }
            window.navUp = async function() {
                if (upStack.length) {
                    window.currentRootId = upStack.pop();
                    loadTree();
                    return;
                }
                const cur = toNum(window.currentRootId);
                if (!cur || cur <= 0) {
                    toastr?.info?.('Root tidak valid.');
                    return;
                }
                const pid = await resolveParentId(cur);
                if (!pid || pid <= 0) {
                    toastr?.info?.('Tidak ada upline.');
                    return;
                }
                if (pid === cur) {
                    toastr?.info?.('Sudah di upline yang sama.');
                    return;
                }
                window.currentRootId = pid;
                loadTree();
            };
            window.navLeft = () => {
                const L = realChild('left');
                if (!L) {
                    toastr?.info?.('Tidak ada anak kiri.');
                    return;
                }
                goDown(L.id);
            };
            window.navRight = () => {
                const R = realChild('right');
                if (!R) {
                    toastr?.info?.('Tidak ada anak kanan.');
                    return;
                }
                goDown(R.id);
            };
            window.navDown = () => {
                const L = realChild('left'),
                    R = realChild('right');
                const kids = [
                    ...((L?.children || []).filter(n => !n.isAddButton && Number.isFinite(n.id))),
                    ...((R?.children || []).filter(n => !n.isAddButton && Number.isFinite(n.id)))
                ];
                if (!kids.length) {
                    toastr?.info?.('Tidak ada cucu.');
                    return;
                }
                const mid = kids[Math.floor(kids.length / 2)] || kids[0];
                goDown(mid.id);
            };

            /* =============== MENU BAGAN =============== */
            function bindBaganMenu() {
                const items = document.querySelectorAll('.menu-bagan[data-bagan]');
                items.forEach(a => {
                    a.addEventListener('click', e => {
                        e.preventDefault();
                        const n = parseInt(a.dataset.bagan, 10);
                        if (!Number.isFinite(n)) return;
                        window.currentBagan = n;
                        localStorage.setItem('selectedBagan', String(n));
                        items.forEach(x => x.classList.toggle('active', x === a));
                        if (lastLoadedData) drawTree(lastLoadedData, true, currentZoomTransform);
                    });
                    a.classList.toggle('active', Number(a.dataset.bagan) === window.currentBagan);
                });
            }

            /* =============== TAMBAH (DAFTAR PENDING) =============== */
            function ensureAddUserModal() {
                return {
                    modal: document.getElementById('addUserModal'),
                    list: document.getElementById('userList')
                };
            }
            window.submitAddUser = function(userId, position, uplineId) {

                fetch(`/tree/${userId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            position,
                            upline_id: uplineId
                        })
                    })
                    .then(r => r.json())
                    .then(() => {
                        toastr?.success?.('User berhasil dipasang');
                        loadTree();
                        bootstrap.Modal.getInstance(document.getElementById('addUserModal'))?.hide();
                    })
                    .catch(() => toastr?.error?.('Gagal memasang user'));
            };

            /* =============== CLONE HELPERS =============== */
            async function refreshPendingBadge() {
                const badge = document.getElementById('pendingCountBadge');
                if (!badge) return;
                try {
                    const r = await fetch(`/tree/available-users/${AUTH_USER_ID}/count`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    const {
                        count
                    } = await r.json();
                    if (Number(count) > 0) {
                        badge.textContent = count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.textContent = '0';
                        badge.classList.add('d-none');
                    }
                } catch {
                    badge.classList.add('d-none');
                }
            }

            async function loadUnusedPinsIntoSelect() {
                const sel = document.getElementById('pinCodes');
                if (!sel) return;
                sel.innerHTML = '<option disabled>Memuat PIN...</option>';
                try {
                    const r = await fetch(`{{ route('pins.unused') }}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!r.ok) throw 0;
                    const {
                        pins
                    } = await r.json();
                    sel.innerHTML = '';
                    if (!pins || !pins.length) {
                        sel.innerHTML = '<option disabled>Tidak ada PIN tersedia</option>';
                    } else {
                        pins.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.code;
                            opt.textContent = p.code;
                            sel.appendChild(opt);
                        });
                    }
                } catch {
                    sel.innerHTML = '<option disabled>Gagal memuat PIN</option>';
                }
            }
            async function previewCloneCandidates() {
                const parentId = document.getElementById('cloneParentId').value;
                const useLogin = document.getElementById('cloneUseLogin').value === '1';
                const count = Array.from(document.getElementById('pinCodes').selectedOptions).length;

                if (!count) {
                    toastr?.info?.('Pilih PIN dulu');
                    return;
                }

                const params = new URLSearchParams({
                    count: String(count)
                });
                if (!useLogin) params.set('base_user_id', String(parentId));

                const url = `{{ route('tree.clone.preview') }}?${params.toString()}`;

                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                if (!res.ok) {
                    toastr?.error?.(data?.message || 'Gagal memuat preview');
                    return;
                }

                const box = document.getElementById('clonePreview');
                box.innerHTML = (data.candidates || [])
                    .map((c, i) =>
                        `${i+1}. <code>${c.username}</code> / <code>${c.sponsor_code ?? c.referral_code ?? '-'}</code>`
                    )
                    .join('<br>');
            }

            async function submitCloneForm(e) {
                e.preventDefault();
                const form = document.getElementById('cloneForm');
                const fd = new FormData(form);

                const res = await fetch(`{{ route('tree.clone.store') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: fd
                });

                if (!res.ok) {
                    const t = await res.text();
                    console.error('Clone failed:', t);
                    toastr?.error?.('Gagal clone: ' + t);
                    return;
                }
                const {
                    ok,
                    message
                } = await res.json();
                if (ok) {
                    toastr?.success?.(message || 'Berhasil clone & pasang');
                    loadTree();
                    bootstrap.Modal.getInstance(document.getElementById('addUserModal'))?.hide();
                }
            }

            // open modal terintegrasi
            function openAddModalUnified({
                parentId,
                position = 'left',
                mode = 'clone'
            }) {
                const modalEl = document.getElementById('addUserModal');
                if (!modalEl) return;

                // isi form clone
                document.getElementById('cloneParentId').value = parentId;
                document.getElementById('clonePosition').value = position;
                document.getElementById('clonePositionLabel').textContent =
                    position === 'right' ? 'Right' : 'Left';

                document.getElementById('cloneBagan').value = String(window.currentBagan || 1);
                document.getElementById('cloneUseLogin').value = '1';
                document.getElementById('clonePreview').innerHTML = '';

                // Update Add New Member tab info
                document.getElementById('addNewParentId').textContent = parentId;
                document.getElementById('addNewPosition').textContent = position === 'right' ? 'Right' : 'Left';
                document.getElementById('addNewBagan').textContent = `Bagan ${window.currentBagan || 1}`;

                document.getElementById('treeUplineId').value = parentId;
                document.getElementById('treePosition').value = position.toLowerCase();

                loadUnusedPinsIntoSelect();

                const loadPendingList = () => {
                    const sponsorId = AUTH_USER_ID;
                    const list = document.getElementById('userList');
                    list.innerHTML = 'Memuat...';
                    fetch(`/tree/available-users/${sponsorId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(r => r.json())
                        .then(users => {
                            list.innerHTML = '';
                            if (!Array.isArray(users) || !users.length) {
                                list.innerHTML =
                                    '<div class="text-center text-muted">Tidak ada user pending.</div>';
                                return;
                            }
                            users.forEach(u => {
                                const row = document.createElement('div');
                                row.className =
                                    'list-group-item d-flex justify-content-between align-items-center';
                                row.innerHTML = `<div><strong>${u.username}</strong><br><small>${u.name ?? ''}</small></div>
                         <button class="btn btn-sm btn-primary">Pasang</button>`;
                                row.querySelector('button').onclick = () => window.submitAddUser(u.id,
                                    position, parentId);
                                list.appendChild(row);
                            });
                        })
                        .catch(() => list.innerHTML =
                            '<div class="text-center text-danger">Gagal memuat data pending.</div>');
                };

                const tabCloneBtn = document.getElementById('tabCloneBtn');
                const tabTambahBtn = document.getElementById('tabTambahBtn');
                const tabAddNewBtn = document.getElementById('tabAddNewBtn');

                tabTambahBtn?.addEventListener('shown.bs.tab', () => {
                    loadPendingList();
                }, {
                    once: false
                });

                const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
                document.getElementById('addUserModal')
                    .addEventListener('shown.bs.modal', () => {
                        refreshPendingBadge();
                    }, {
                        once: true
                    });
                modalEl.addEventListener('shown.bs.modal', () => {
                    if (mode === 'tambah') {
                        bootstrap.Tab.getOrCreateInstance(tabTambahBtn).show();
                    } else if (mode === 'addnew') {
                        bootstrap.Tab.getOrCreateInstance(tabAddNewBtn).show();
                    } else {
                        bootstrap.Tab.getOrCreateInstance(tabCloneBtn).show();
                    }
                }, {
                    once: true
                });

                modal.show();
            }

            window.openAddModalUnified = openAddModalUnified;

            /* =============== ADD NEW MEMBER FUNCTIONS =============== */


            window.loadMemberTemplate = function() {
                console.log('Loading member template...');
                // Placeholder function - untuk load template form
                if (typeof toastr !== 'undefined') {
                    toastr.info('Template member dimuat');
                } else {
                    alert('Template member dimuat');
                }

                // TODO: Replace with your actual template loading
                // Example: loadFormTemplate();
            };

            /* =============== BIND EVENTS =============== */
            document.addEventListener('click', (ev) => {
                if (ev.target?.id === 'btnPreview') previewCloneCandidates();
            });
            document.addEventListener('submit', (ev) => {
                if (ev.target?.id === 'cloneForm') submitCloneForm(ev);
            });

            /* =============== BOOT =============== */
            document.addEventListener('DOMContentLoaded', () => {
                bindBaganMenu();
                loadTree();
            });

        })();

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
                                window.location.href = "{{ route('tree.index') }}";
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
