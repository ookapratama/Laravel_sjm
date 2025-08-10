@extends('layouts.front')
@section('title','Registrasi Member')

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

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Banner Sponsor --}}
          <div id="sponsorBanner" class="alert alert-info {{ request('ref') ? '' : 'd-none' }}">
            <strong>Sponsor:</strong>
            <span id="sponsorText">{{ request('ref') ? 'Kode: '.request('ref') : '' }}</span>
          </div>

          <form id="ref-register-form" action="{{ route('referral.register.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="ref" id="ref" value="{{ old('ref', request('ref')) }}">

            <div class="row g-3">
              {{-- PIN Aktivasi --}}
              <div class="col-md-6">
                <label class="form-label">PIN Aktivasi</label>
                <input type="text" name="pin_aktivasi" class="form-control @error('pin_aktivasi') is-invalid @enderror" required value="{{ old('pin_aktivasi') }}">
                @error('pin_aktivasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-muted">Diperoleh dari upline/admin saat membeli PIN.</small>
              </div>

              {{-- Kode Sponsor (readonly display) --}}
              <div class="col-md-6">
                <label class="form-label">Kode Sponsor</label>
                <input type="text" id="sponsor_code_display" class="form-control" value="{{ request('ref') }}" required readonly>
                @error('ref') <div class="text-danger small">{{ $message }}</div> @enderror
              </div>

              {{-- Nama / Phone / Email --}}
              <div class="col-md-6">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       required autocomplete="name" value="{{ old('name') }}">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">No. HP / WA</label>
                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                       required autocomplete="tel" value="{{ old('phone') }}">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Email (opsional)</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" value="{{ old('email') }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Username / Password --}}
              <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                       required autocomplete="username" value="{{ old('username') }}">
                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required minlength="6" autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required minlength="6" autocomplete="new-password">
              </div>

              {{-- Data Mitra (singkat, sisanya boleh dilengkapi nanti juga) --}}
              <div class="col-md-6">
                <label class="form-label">Jenis Kelamin</label><br>
                <div class="form-check form-check-inline">
                  <input class="form-check-input @error('jenis_kelamin') is-invalid @enderror" type="radio" name="jenis_kelamin" value="pria" {{ old('jenis_kelamin')==='pria'?'checked':'' }} required>
                  <label class="form-check-label">Pria</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input @error('jenis_kelamin') is-invalid @enderror" type="radio" name="jenis_kelamin" value="wanita" {{ old('jenis_kelamin')==='wanita'?'checked':'' }} required>
                  <label class="form-check-label">Wanita</label>
                </div>
                @error('jenis_kelamin') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">No. KTP (opsional)</label>
                <input type="text" name="no_ktp" class="form-control @error('no_ktp') is-invalid @enderror" value="{{ old('no_ktp') }}">
                @error('no_ktp') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror" required value="{{ old('tempat_lahir') }}">
                @error('tempat_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror" required value="{{ old('tanggal_lahir') }}">
                @error('tanggal_lahir') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-12">
                <label class="form-label">Agama</label><br>
                @foreach(['islam'=>'Islam','kristen'=>'Kristen','katolik'=>'Katolik','budha'=>'Budha','hindu'=>'Hindu','lainnya'=>'Lainnya'] as $k=>$v)
                  <div class="form-check form-check-inline">
                    <input class="form-check-input @error('agama') is-invalid @enderror" type="radio" name="agama" value="{{ $k }}" {{ old('agama')===$k?'checked':'' }} required>
                    <label class="form-check-label">{{ $v }}</label>
                  </div>
                @endforeach
                @error('agama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-12">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="2" required>{{ old('alamat') }}</textarea>
                @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-2">
                <label class="form-label">RT</label>
                <input type="text" name="rt" class="form-control @error('rt') is-invalid @enderror" value="{{ old('rt') }}">
                @error('rt') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">RW</label>
                <input type="text" name="rw" class="form-control @error('rw') is-invalid @enderror" value="{{ old('rw') }}">
                @error('rw') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-8">
                <label class="form-label">Desa/Kelurahan</label>
                <input type="text" name="desa" class="form-control @error('desa') is-invalid @enderror" required value="{{ old('desa') }}">
                @error('desa') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Kecamatan</label>
                <input type="text" name="kecamatan" class="form-control @error('kecamatan') is-invalid @enderror" required value="{{ old('kecamatan') }}">
                @error('kecamatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label">Kota/Kabupaten</label>
                <input type="text" name="kota" class="form-control @error('kota') is-invalid @enderror" required value="{{ old('kota') }}">
                @error('kota') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Kode Pos</label>
                <input type="text" name="kode_pos" class="form-control @error('kode_pos') is-invalid @enderror" value="{{ old('kode_pos') }}">
                @error('kode_pos') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Nama di Rekening</label>
                <input type="text" name="nama_rekening" class="form-control @error('nama_rekening') is-invalid @enderror" required value="{{ old('nama_rekening') }}">
                @error('nama_rekening') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Nomor Rekening</label>
                <input type="text" name="nomor_rekening" class="form-control @error('nomor_rekening') is-invalid @enderror" required value="{{ old('nomor_rekening') }}">
                @error('nomor_rekening') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Nama Bank</label>
                <input type="text" name="nama_bank" list="bankList" class="form-control @error('nama_bank') is-invalid @enderror" required value="{{ old('nama_bank') }}" placeholder="Pilih / ketik nama bank">
                @error('nama_bank') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Nama Ahli Waris (opsional)</label>
                <input type="text" name="nama_ahli_waris" class="form-control @error('nama_ahli_waris') is-invalid @enderror" value="{{ old('nama_ahli_waris') }}">
                @error('nama_ahli_waris') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Hubungan Ahli Waris (opsional)</label>
                <input type="text" name="hubungan_ahli_waris" class="form-control @error('hubungan_ahli_waris') is-invalid @enderror" value="{{ old('hubungan_ahli_waris') }}">
                @error('hubungan_ahli_waris') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <div class="form-check mb-2">
                  <input class="form-check-input @error('agree') is-invalid @enderror" type="checkbox" name="agree" id="agree" value="1" {{ old('agree') ? 'checked' : '' }}>
                  <label class="form-check-label" for="agree">Saya menyetujui Syarat & Ketentuan</label>
                  @error('agree') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-warning w-100">Daftar Sekarang</button>
              </div>
            </div>
          </form>

          <div class="text-center mt-3">
            Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
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
(function(){
  // isi sponsor display dari ?ref
  const urlParams = new URLSearchParams(window.location.search);
  const ref = urlParams.get('ref') || "{{ request('ref') ?? '' }}";
  const refInput = document.getElementById('ref');
  const sponsorDisp = document.getElementById('sponsor_code_display');
  const sponsorBanner = document.getElementById('sponsorBanner');
  const sponsorText = document.getElementById('sponsorText');

  if (ref) {
    refInput.value = ref;
    if (sponsorDisp) sponsorDisp.value = ref;
    if (sponsorBanner) sponsorBanner.classList.remove('d-none');
    if (sponsorText) sponsorText.textContent = 'Kode: ' + ref + ' (validasi saat submit)';
  }

  // datalist Bank (BUMN dulu)
  const datalist = document.getElementById('bankList');
  if (datalist) {
    const BUMN = ['Bank Mandiri','Bank Rakyat Indonesia (BRI)','Bank Negara Indonesia (BNI)','Bank Tabungan Negara (BTN)','Bank Syariah Indonesia (BSI)'];
    const UMUM = ['Bank Central Asia (BCA)','CIMB Niaga','Bank Danamon','OCBC NISP','Permata Bank','Panin Bank','Maybank Indonesia','KB Bukopin','Bank BTPN','Bank Mega','Bank Sinarmas','UOB Indonesia','HSBC Indonesia','Standard Chartered Indonesia','Citibank N.A. Indonesia','ICBC Indonesia','Bank China Construction Bank Indonesia (CCB Indonesia)','Bank Commonwealth','QNB Indonesia','Bank Woori Saudara','Bank Shinhan Indonesia','Bank JTrust Indonesia','Bank MNC Internasional','Bank Artha Graha Internasional','Bank Capital Indonesia','Bank Maspion Indonesia','Bank Ina Perdana','Bank Index Selindo','Bank Victoria International','Bank Mayora','Bank Oke Indonesia','Bank Sahabat Sampoerna','Krom Bank Indonesia','Bank Fama Internasional','Bank Neo Commerce (BNC)','Allo Bank Indonesia','SeaBank Indonesia','Bank Jago','BCA Digital (blu)','Bank Muamalat Indonesia','BTPN Syariah','Bank Mega Syariah'];
    const BPD = ['Bank DKI','Bank BJB (Jawa Barat & Banten)','Bank Jateng','Bank Jatim','Bank DIY','Bank BPD Bali','Bank NTB Syariah','Bank NTT','Bank BPD Sumut','Bank Sumsel Babel','Bank Nagari (Sumbar)','Bank Riau Kepri','Bank Jambi','Bank Bengkulu','Bank Lampung','Bank Kalbar','Bank Kalteng','Bank Kalsel','Bank Kaltimtara','Bank Kaltara','Bank Sulselbar','Bank Sultra','Bank Sulteng','Bank SulutGo','Bank Maluku Malut','Bank Papua'];
    const banks = [...BUMN, ...UMUM.sort((a,b)=>a.localeCompare(b)), ...BPD.sort((a,b)=>a.localeCompare(b))];
    datalist.innerHTML = banks.map(b => `<option value="${b}"></option>`).join('');
  }

  // submit via fetch → balas JSON (ok untuk SPA feel)
  const form = document.getElementById('ref-register-form');
  form.addEventListener('submit', async function(e){
    e.preventDefault();

    // bersihkan error lama
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
      const data = ct.includes('application/json') ? await res.json() : { html: await res.text() };

      if (res.ok) {
        if (window.toastr) toastr.success(data.success || 'Registrasi berhasil');
        window.location.href = data.redirect || "{{ route('member') }}";
        return;
      }

      if (res.status === 422 && data.errors) {
        if (window.toastr) toastr.error('Periksa kembali data Anda.');
        Object.entries(data.errors).forEach(([field, msgs]) => {
          const input = document.querySelector(`[name="${field}"]`);
          if (!input) return;
          input.classList.add('is-invalid');
          const fb = document.createElement('div');
          fb.className = 'invalid-feedback';
          fb.textContent = msgs[0];
          (input.parentElement || input).appendChild(fb);
        });
        const firstErr = document.querySelector('.is-invalid');
        if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'});
        return;
      }

      console.error('Unexpected response', data);
      if (window.toastr) toastr.error('Terjadi kesalahan jaringan.');
    } catch (err) {
      console.error(err);
      if (window.toastr) toastr.error('Terjadi kesalahan jaringan.');
    }
  });
})();
</script>
@endpush
