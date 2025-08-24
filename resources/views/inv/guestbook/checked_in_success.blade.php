@extends('layouts.inv')
@section('title','Check-In Berhasil')

@section('content')
<div class="inv-page">
  <div class="container-inv">
    <div class="card-inv">
      <div class="card-inv__inner" style="text-align:center">

        <h2 class="form-title">Check-In Berhasil</h2>
        <p class="form-sub">
          Terima kasih <strong>{{ $entry->name }}</strong>.<br>
          Kehadiran Anda untuk acara <b>{{ $invitation->title }}</b> sudah tercatat.
        </p>

        <div style="margin-top:12px;color:#e8ddbd">
          Waktu check-in: {{ $entry->check_in_at?->translatedFormat('d M Y H:i') }}
        </div>

        <div style="margin-top:18px">
          <a href="{{ route('guest.form.inv',$invitation->slug) }}" class="btn-gold">
            <i class="fas fa-home"></i> Kembali ke Halaman Utama
          </a>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
