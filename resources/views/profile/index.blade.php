@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="container py-4">

  <div class="row g-4">

<div class="col-md-4">
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center">

      <div class="sjm-avatar mx-auto">
        <img id="sjmAvatar"
             src="{{ asset($user->photo ?? 'assets/img/profile.webp') }}"
             class="sjm-avatar-img"
             alt="Foto Profil">
       <button type="button" class="sjm-avatar-overlay"
        aria-label="Ubah foto"
        onclick="document.getElementById('sjmPhotoInput').click()">
  <i class="fa-solid fa-pen"></i>
</button>
      </div>

      <form id="sjmPhotoForm" class="mt-3"
            method="POST" action="{{ route('profile.update.photo') }}" enctype="multipart/form-data">
        @csrf
        <input id="sjmPhotoInput" name="photo" type="file"
               accept="image/png,image/jpeg,image/webp" hidden>
        <input id="sjmCropped" name="cropped_image" type="hidden">
        {{-- tombol disembunyikan; tetap ada untuk fallback/non-JS --}}
        <button id="sjmSaveBtn" type="submit" class="btn btn-primary d-none">Simpan Foto</button>
        <div class="form-text mt-2">PNG/JPG/WebP ≤ 2MB</div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL CROPPER --}}
<div class="modal fade" id="sjmCropperModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Atur & Pangkas Foto</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="sjm-cropper-area">
          <img id="sjmCropSrc" alt="Gambar" style="max-width:100%;display:block;">
        </div>
      </div>
      <div class="modal-footer gap-2 flex-wrap">
        <div class="btn-group me-auto">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-act="zoom-in">Zoom +</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-act="zoom-out">Zoom −</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-act="rotate">Rotate 90°</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-act="reset">Reset</button>
        </div>
        <button type="button" class="btn btn-primary" id="sjmApplyCrop">Terapkan</button>
      </div>
    </div>
  </div>
</div>
    {{-- FORM PROFIL + BANK --}}
    <div class="col-md-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex align-items-center">
          <i class="bi bi-person-lines-fill me-2"></i>
          <span>Data Profil</span>
        </div>
        <div class="card-body">
          {{-- Alert Validasi & Status --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
          @endif

          <form action="{{ route('profile.update') }}" method="POST" autocomplete="on">
            @csrf
           

            {{-- ====== Data Bank (editable hanya jika kosong) ====== --}}
            @php
              $bankLockedNama = $mitra && !empty($mitra->nama_bank);
              $bankLockedNo   = $mitra && !empty($mitra->nomor_rekening);
              $bankLockedAtas = $mitra && !empty($mitra->nama_rekening);
              $anyLocked      = $bankLockedNama || $bankLockedNo || $bankLockedAtas;
            @endphp

            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-0 fw-bold">Data Rekening Bank</h6>
              <span class="badge {{ $anyLocked ? 'bg-secondary' : 'bg-success' }}">
                {{ $anyLocked ? 'Terkunci' : 'Belum diisi' }}
              </span>
            </div>
            <hr class="mt-2">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Nama Bank</label>
                <input type="text" name="nama_bank" class="form-control"
                       value="{{ old('nama_bank', $mitra->nama_bank ?? '') }}"
                       {{ $bankLockedNama ? 'readonly' : '' }}
                       placeholder="Contoh: BCA" @if(!$bankLockedNama) required @endif>
                @if($bankLockedNama) <small class="text-muted">* Tidak dapat diubah</small> @endif
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Nomor Rekening</label>
                <input type="text" name="nomor_rekening" class="form-control"
                       value="{{ old('nomor_rekening', $mitra->nomor_rekening ?? '') }}"
                       {{ $bankLockedNo ? 'readonly' : '' }}
                       placeholder="1234567890" @if(!$bankLockedNo) required @endif>
                @if($bankLockedNo) <small class="text-muted">* Tidak dapat diubah</small> @endif
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Atas Nama</label>
                <input type="text" name="nama_rekening" class="form-control"
                       value="{{ old('nama_rekening', $mitra->nama_rekening ?? '') }}"
                       {{ $bankLockedAtas ? 'readonly' : '' }}
                       placeholder="Nama pemilik" @if(!$bankLockedAtas) required @endif>
                @if($bankLockedAtas) <small class="text-muted">* Tidak dapat diubah</small> @endif
              </div>
            </div>

            <div class="mt-4"></div>

            {{-- ====== Data Akun (editable kapan pun) ====== --}}
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" name="username" class="form-control"
                       value="{{ old('username', $user->username) }}" autocomplete="username">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">No. Telp / WhatsApp</label>
                <input type="tel" name="phone" class="form-control"
                       value="{{ old('phone', $mitra->phone ?? $user->no_telp) }}"
                       placeholder="08xxxxxxxxxx" autocomplete="tel">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">Alamat</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap">{{ old('address', $mitra->alamat ?? $user->address) }}</textarea>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button class="btn btn-success"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
              <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>

      {{-- GANTI PASSWORD --}}
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning text-white d-flex align-items-center">
          <i class="bi bi-key-fill me-2"></i>
          <span>Ganti Password</span>
        </div>
        <div class="card-body">
          <form action="{{ route('profile.update-password') }}" method="POST" autocomplete="new-password">
            @csrf
        
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Password Lama</label>
                <input type="password" name="old_password" class="form-control" required autocomplete="current-password">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Password Baru</label>
                <input type="password" name="new_password" class="form-control" minlength="4" required autocomplete="new-password">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation" class="form-control" minlength="4" required autocomplete="new-password">
              </div>
            </div>
            <button class="btn btn-warning text-white mt-3"><i class="bi bi-arrow-repeat me-1"></i> Ubah Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
<!-- CSS -->
<link href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet"/>
<!-- JS -->
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<style>
  /* Ukuran avatar fix agar tidak membesar */
  .sjm-avatar{ width:180px; height:180px; position:relative; border-radius:9999px; overflow:hidden; }
  .sjm-avatar-img{ width:100%; height:100%; object-fit:cover; border:4px solid #f1f1f1; box-shadow:0 2px 10px rgba(0,0,0,.08); border-radius:9999px; }

  /* Overlay ikon edit saat hover */
  .sjm-avatar-overlay{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    border:0; background:rgba(0,0,0,.45); color:#fff; border-radius:9999px;
    opacity:0; transition:opacity .2s; cursor:pointer;
  }
  .sjm-avatar-overlay i{ font-size:1.25rem; }
  .sjm-avatar:hover .sjm-avatar-overlay{ opacity:1; }

  /* Modal crop rapi */
  .sjm-cropper-area{
    min-height:360px; max-height:65vh; overflow:hidden; background:#fafafa;
    border:1px dashed #e5e5e5; border-radius:.5rem; display:flex; align-items:center; justify-content:center;
  }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(() => {
  'use strict';
  const avatar   = document.getElementById('sjmAvatar');
  const input    = document.getElementById('sjmPhotoInput');
  const form     = document.getElementById('sjmPhotoForm');
  const hidden   = document.getElementById('sjmCropped');
  const saveBtn  = document.getElementById('sjmSaveBtn'); // fallback non-JS (disembunyikan)

  const modalEl  = document.getElementById('sjmCropperModal');
  const cropImg  = document.getElementById('sjmCropSrc');
  const applyBtn = document.getElementById('sjmApplyCrop');
  const bsModal  = new bootstrap.Modal(modalEl);

  let cropper = null; let submitting = false;

  const destroyCropper = () => { try { cropper?.destroy(); } catch(_) {} cropper = null; };
  const okType = t => ['image/png','image/jpeg','image/webp'].includes(t);
  const validate = f => {
    if (!okType(f.type)) { alert('Gunakan PNG/JPG/WebP'); return false; }
    if (f.size > 2*1024*1024) { alert('Ukuran file > 2MB'); return false; }
    return true;
  };

  // pilih file -> tampilkan modal (tanpa init dulu)
  input.addEventListener('change', e => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!validate(file)) { input.value=''; return; }
    const rd = new FileReader();
    rd.onload = () => { cropImg.src = rd.result; bsModal.show(); };
    rd.readAsDataURL(file);
  });

  // init/destroy cropper mengikuti modal
  modalEl.addEventListener('shown.bs.modal', () => {
    destroyCropper();
    cropper = new Cropper(cropImg, {
      aspectRatio: 1,
      viewMode: 1,
      dragMode: 'move',
      autoCropArea: 1,
      background: false,
    });
  });
  modalEl.addEventListener('hidden.bs.modal', destroyCropper);

  // toolbar
  modalEl.addEventListener('click', ev => {
    if (!cropper) return;
    switch (ev.target.getAttribute('data-act')) {
      case 'zoom-in':  cropper.zoom(0.1); break;
      case 'zoom-out': cropper.zoom(-0.1); break;
      case 'rotate':   cropper.rotate(90); break;
      case 'reset':    cropper.reset(); break;
    }
  });

  // Terapkan -> update avatar (ukuran tetap 180x180) + AUTO SUBMIT
  applyBtn.addEventListener('click', () => {
    if (!cropper || submitting) return;
    cropper.getCroppedCanvas({ width: 512, height: 512 }).toBlob(blob => {
      const rd = new FileReader();
      rd.onloadend = () => {
        const base64 = rd.result;              // data:image/...
        hidden.value  = base64;                // kirim ke server
        avatar.src    = base64;                // update UI langsung (object-fit cover menjaga ukuran)
        bsModal.hide();

        // auto-submit (tanpa butuh klik "Simpan Foto")
        submitting = true;
        form.submit();
      };
      rd.readAsDataURL(blob);
    }, 'image/jpeg', 0.9);
  });

  // guard submit manual (fallback)
  form.addEventListener('submit', (e) => {
    if (submitting) return;
    if (input.files.length && !hidden.value) { e.preventDefault(); bsModal.show(); }
  });
})();
</script>
@endpush


