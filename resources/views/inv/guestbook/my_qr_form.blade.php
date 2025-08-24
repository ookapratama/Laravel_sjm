
@extends('layouts.inv')
@section('title','Ambil QR Check‑In')
@section('content')
<div class="inv-page"><div class="container-inv"><div class="card-inv"><div class="card-inv__inner">
  <h2 class="form-title">Ambil QR Check‑In</h2>
  <p class="form-sub">Masukkan salah satu: No. HP atau Kode Referral (atau Nama).</p>
  <form method="POST" action="{{ route('guest_entries.myqr.fetch',$invitation->slug) }}" class="inv-form">
    @csrf
    <div class="row g-14">
      <div class="col-md-4">
        <label class="form-label">No. HP</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}" placeholder="08xxxx">
      </div>
      <div class="col-md-4">
        <label class="form-label">Kode Referral</label>
        <input name="code" class="form-control" value="{{ old('code') }}" placeholder="Opsional">
      </div>
      <div class="col-md-4">
        <label class="form-label">Nama</label>
        <input name="name" class="form-control" value="{{ old('name') }}" placeholder="Jika tidak isi HP/Kode">
      </div>
    </div>
    @error('notfound')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
    <div class="d-flex justify-content-end mt-4">
      <button class="btn-gold">Tampilkan QR</button>
    </div>
  </form>
</div></div></div></div>
@endsection
