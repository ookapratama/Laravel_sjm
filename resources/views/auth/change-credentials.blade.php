@extends('layouts.app')

@section('content')
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12">
                <form action="{{ route('change.credentials.update') }}" method="POST" id="formWizard" novalidate>
                    @csrf
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Lengkapi Data Mitra & Akun Anda</h5>
                        </div>

                        <div class="card-body">
                            <!-- STEP NAVIGATION -->
                            <ul class="nav nav-pills mb-4">
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" id="step1-tab">1. Akun</button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" id="step2-tab">2. Data Mitra</button>
                                </li>
                            </ul>

                            <!-- STEP 1 -->
                            <div id="step1" class="step-content">
                                <h5 class="fw-bold text-primary mb-3">Akun Login</h5>

                                <div class="mb-3">
                                    <label class="form-label">Username Saat Ini</label>
                                    <input type="text" class="form-control" value="{{ $user->username }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Username Baru</label>
                                    <input type="text" name="username"
                                        class="form-control @error('username') is-invalid @enderror" required
                                        autocomplete="username" value="{{ old('username') }}">
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Password Baru</label>
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror" required minlength="6"
                                        autocomplete="new-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Konfirmasi Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required
                                        autocomplete="new-password">
                                </div>

                                <h5 class="fw-bold text-primary mt-4">Kontak Dasar</h5>

                                <div class="mb-3">
                                    <label class="form-label label-required">Nama Lengkap</label>
                                    <input type="text" name="name"
                                        class="form-control @error('name') is-invalid @enderror" required
                                        autocomplete="name" value="{{ old('name', $pre->name ?? '') }}">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $pre->email ?? '') }}" readonly>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">No. HP / WA</label>
                                    <input type="text" name="phone"
                                        class="form-control @error('phone') is-invalid @enderror" required
                                        autocomplete="tel" value="{{ old('phone', $pre->no_telp ?? ($pre->phone ?? '')) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-info" id="nextStep">Berikutnya</button>
                                </div>
                            </div>

                            <!-- STEP 2 -->
                            <div id="step2" class="step-content d-none">
                                <h5 class="fw-bold text-primary mb-3">Data Mitra Lengkap</h5>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No. KTP</label>
                                        <input type="text" name="no_ktp"
                                            class="form-control @error('no_ktp') is-invalid @enderror"
                                            value="{{ old('no_ktp') }}">
                                        @error('no_ktp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label label-required">Jenis Kelamin</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('jenis_kelamin') is-invalid @enderror"
                                                type="radio" name="jenis_kelamin" value="pria"
                                                {{ old('jenis_kelamin') === 'pria' ? 'checked' : '' }} required>
                                            <label class="form-check-label">Pria</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('jenis_kelamin') is-invalid @enderror"
                                                type="radio" name="jenis_kelamin" value="wanita"
                                                {{ old('jenis_kelamin') === 'wanita' ? 'checked' : '' }} required>
                                            <label class="form-check-label">Wanita</label>
                                        </div>
                                        @error('jenis_kelamin')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label label-required">Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir"
                                            class="form-control @error('tempat_lahir') is-invalid @enderror" required
                                            value="{{ old('tempat_lahir') }}">
                                        @error('tempat_lahir')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label label-required">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir"
                                            class="form-control @error('tanggal_lahir') is-invalid @enderror" required
                                            value="{{ old('tanggal_lahir') }}">
                                        @error('tanggal_lahir')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required d-block">Agama</label>
                                    @foreach (['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik', 'budha' => 'Budha', 'hindu' => 'Hindu', 'lainnya' => 'Lainnya'] as $key => $label)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input @error('agama') is-invalid @enderror"
                                                type="radio" name="agama" value="{{ $key }}"
                                                {{ old('agama') === $key ? 'checked' : '' }} required>
                                            <label class="form-check-label">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                    @error('agama')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Alamat Lengkap</label>
                                    <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" required rows="2">{{ old('alamat') }}</textarea>
                                    @error('alamat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">RT</label>
                                        <input type="text" name="rt"
                                            class="form-control @error('rt') is-invalid @enderror"
                                            value="{{ old('rt') }}">
                                        @error('rt')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">RW</label>
                                        <input type="text" name="rw"
                                            class="form-control @error('rw') is-invalid @enderror"
                                            value="{{ old('rw') }}">
                                        @error('rw')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label label-required">Desa/Kelurahan</label>
                                        <input type="text" name="desa"
                                            class="form-control @error('desa') is-invalid @enderror" required
                                            value="{{ old('desa') }}">
                                        @error('desa')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label label-required">Kecamatan</label>
                                        <input type="text" name="kecamatan"
                                            class="form-control @error('kecamatan') is-invalid @enderror" required
                                            value="{{ old('kecamatan') }}">
                                        @error('kecamatan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label label-required">Kota/Kabupaten</label>
                                        <input type="text" name="kota"
                                            class="form-control @error('kota') is-invalid @enderror" required
                                            value="{{ old('kota') }}">
                                        @error('kota')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" name="kode_pos"
                                            class="form-control @error('kode_pos') is-invalid @enderror"
                                            value="{{ old('kode_pos') }}">
                                        @error('kode_pos')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <h5 class="fw-bold text-primary mt-4">Data Rekening</h5>

                                <div class="mb-3">
                                    <label class="form-label label-required">Nama di Rekening</label>
                                    <input type="text" name="nama_rekening"
                                        class="form-control @error('nama_rekening') is-invalid @enderror" required
                                        value="{{ old('nama_rekening') }}">
                                    @error('nama_rekening')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Nama Bank</label>
                                    <input type="text" name="nama_bank" list="bankList"
                                        class="form-control @error('nama_bank') is-invalid @enderror"
                                        placeholder="Pilih / ketik nama bank" required value="{{ old('nama_bank') }}">
                                    <datalist id="bankList"></datalist>
                                    @error('nama_bank')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label label-required">Nomor Rekening</label>
                                    <input type="text" name="nomor_rekening"
                                        class="form-control @error('nomor_rekening') is-invalid @enderror" required
                                        value="{{ old('nomor_rekening') }}">
                                    @error('nomor_rekening')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <h5 class="fw-bold text-primary mt-4">Ahli Waris (Opsional)</h5>

                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama_ahli_waris"
                                        class="form-control @error('nama_ahli_waris') is-invalid @enderror"
                                        value="{{ old('nama_ahli_waris') }}">
                                    @error('nama_ahli_waris')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Hubungan</label>
                                    <input type="text" name="hubungan_ahli_waris"
                                        class="form-control @error('hubungan_ahli_waris') is-invalid @enderror"
                                        value="{{ old('hubungan_ahli_waris') }}">
                                    @error('hubungan_ahli_waris')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary" id="prevStep">Sebelumnya</button>
                                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan &
                                        Lanjut</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .label-required::after {
            content: " *";
            color: #dc3545;
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step1Tab = document.getElementById('step1-tab');
            const step2Tab = document.getElementById('step2-tab');

            document.getElementById('nextStep').addEventListener('click', () => {
                step1.classList.add('d-none');
                step2.classList.remove('d-none');
                step1Tab.classList.remove('active');
                step2Tab.classList.add('active');
                toggleRequired(step1, false);
                toggleRequired(step2, true);
            });

            document.getElementById('prevStep').addEventListener('click', () => {
                step2.classList.add('d-none');
                step1.classList.remove('d-none');
                step2Tab.classList.remove('active');
                step1Tab.classList.add('active');
                toggleRequired(step2, false);
                toggleRequired(step1, true);
            });

            function toggleRequired(container, on) {
                container.querySelectorAll('[data-required="1"]').forEach(el => {
                    if (on) el.setAttribute('required', 'required');
                    else el.removeAttribute('required');
                });
            }

            // mark all current required as data-required so we can toggle per step
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('#step1 [required], #step2 [required]').forEach(el => el.setAttribute(
                    'data-required', '1'));
                toggleRequired(step1, true);
                toggleRequired(step2, false);

                // Setup bank list
                setupBankList();
            });

            function setupBankList() {
                const datalist = document.getElementById('bankList');
                if (!datalist) return;

                const BUMN = [
                    'Bank Mandiri',
                    'Bank Rakyat Indonesia (BRI)',
                    'Bank Negara Indonesia (BNI)',
                    'Bank Tabungan Negara (BTN)',
                    'Bank Syariah Indonesia (BSI)'
                ];

                const UMUM = [
                    'Bank Central Asia (BCA)',
                    'CIMB Niaga',
                    'Bank Danamon',
                    'OCBC NISP',
                    'Permata Bank',
                    'Panin Bank',
                    'Maybank Indonesia',
                    'KB Bukopin',
                    'Bank BTPN',
                    'Bank Mega',
                    'Bank Sinarmas',
                    'UOB Indonesia',
                    'HSBC Indonesia',
                    'Standard Chartered Indonesia',
                    'Citibank N.A. Indonesia',
                    'Bank Neo Commerce (BNC)',
                    'Allo Bank Indonesia',
                    'SeaBank Indonesia',
                    'Bank Jago',
                    'BCA Digital (blu)',
                    'Bank Muamalat Indonesia',
                    'BTPN Syariah',
                    'Bank Mega Syariah'
                ];

                const BPD = [
                    'Bank DKI',
                    'Bank BJB (Jawa Barat & Banten)',
                    'Bank Jateng',
                    'Bank Jatim',
                    'Bank DIY',
                    'Bank BPD Bali',
                    'Bank NTB Syariah',
                    'Bank NTT',
                    'Bank BPD Sumut',
                    'Bank Sumsel Babel',
                    'Bank Nagari (Sumbar)',
                    'Bank Riau Kepri',
                    'Bank Jambi',
                    'Bank Bengkulu',
                    'Bank Lampung',
                    'Bank Kalbar',
                    'Bank Kalteng',
                    'Bank Kalsel',
                    'Bank Kaltimtara',
                    'Bank Kaltara',
                    'Bank Sulselbar',
                    'Bank Sultra',
                    'Bank Sulteng',
                    'Bank SulutGo',
                    'Bank Maluku Malut',
                    'Bank Papua'
                ];

                const banks = [...BUMN, ...UMUM.sort((a, b) => a.localeCompare(b)), ...BPD.sort((a, b) => a
                    .localeCompare(b))];
                datalist.innerHTML = banks.map(b => `<option value="${b}"></option>`).join('');
            }

            document.getElementById('formWizard').addEventListener('submit', async function(e) {
                e.preventDefault();

                // clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                try {
                    const res = await fetch(this.action, {
                        method: 'POST',
                        body: new FormData(this),
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        redirect: 'manual'
                    });

                    const ct = res.headers.get('content-type') || '';
                    const data = ct.includes('application/json') ? await res.json() : {
                        html: await res.text()
                    };

                    if (res.ok) {
                        toastr.success(data.success || 'Berhasil disimpan');
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                        return;
                    }

                    if (res.status === 422 && data.errors) {
                        toastr.error('Periksa kembali data yang Anda masukkan.');
                        // highlight invalids
                        Object.entries(data.errors).forEach(([field, msgs]) => {
                            const input = document.querySelector(`[name="${field}"]`);
                            if (!input) return;
                            input.classList.add('is-invalid');
                            const fb = document.createElement('div');
                            fb.className = 'invalid-feedback';
                            fb.textContent = msgs[0];
                            // for radios, place after group
                            if (input.type === 'radio') {
                                const group = input.closest('.mb-3') || input.parentElement;
                                group.appendChild(fb);
                            } else {
                                input.parentElement.appendChild(fb);
                            }
                        });
                        // jump to the step that has first error
                        const firstErr = document.querySelector('.is-invalid');
                        if (firstErr && firstErr.closest('#step2')) {
                            step1.classList.add('d-none');
                            step2.classList.remove('d-none');
                            step1Tab.classList.remove('active');
                            step2Tab.classList.add('active');
                            toggleRequired(step1, false);
                            toggleRequired(step2, true);
                        }
                        return;
                    }

                    console.error('Unexpected response', data);
                    toastr.error('Terjadi kesalahan jaringan.');
                } catch (err) {
                    console.error(err);
                    toastr.error('Terjadi kesalahan jaringan.');
                }
            });
        })();
    </script>
@endpush
