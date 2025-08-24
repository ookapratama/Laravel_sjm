@extends('layouts.inv')
@section('title','Terima Kasih')
@section('content')
<div class="inv-page"><div class="container-inv"><div class="card-inv"><div class="card-inv__inner" style="text-align:center">
  <h2 class="form-title">Terima kasih!</h2>
  <p class="form-sub">{{ session('ok') }}</p>

  @php $qrPng = session('qrPng'); $qrUrl = session('qrUrl'); $waLink = session('waLink'); @endphp

  @if($qrPng && $qrUrl)
    <p class="form-sub">Atas nama: <b>{{ session('entryName') }}</b></p>
    <img src="data:image/png;base64,{{ $qrPng }}" alt="QR" style="width:240px;height:240px;border:1px solid rgba(201,169,93,.3);padding:6px;background:#fff;border-radius:12px">
    <div class="mt-3">
      <a href="{{ $qrUrl }}" class="btn-gold" target="_blank" rel="noopener"><i class="fas fa-qrcode"></i> Tautan</a>
      @if($waLink)
        <a href="{{ $waLink }}" class="btn-gold" style="margin-left:8px" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp"></i> Kirim ke WhatsApp
        </a>
      @endif
      <button class="btn-gold" onclick="window.print()" style="margin-left:8px"><i class="fas fa-print"></i> Cetak</button>
    </div>
  @else
    <p class="form-sub">Jika QR tidak muncul, silakan ambil ulang di halaman berikut:</p>
    <a class="btn-gold" href="{{ route('guest_entries.myqr.form',$invitation->slug) }}">Ambil QR Saya</a>
  @endif
</div></div></div></div>
@endsection
