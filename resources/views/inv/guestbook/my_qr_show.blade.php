@extends('layouts.inv')
@section('title','QR Check‑In')
@section('content')
<div class="inv-page"><div class="container-inv"><div class="card-inv"><div class="card-inv__inner" style="text-align:center">
  <h2 class="form-title">QR Check‑In</h2>
  <p class="form-sub">Atas nama: <b>{{ $entry->name }}</b></p>
  <img src="data:image/png;base64,{{ $qrPng }}" alt="QR" style="width:240px;height:240px;border:1px solid rgba(201,169,93,.3);padding:6px;background:#fff;border-radius:12px">
  <div class="mt-3">
    <a href="{{ $qrUrl }}" class="btn-gold" target="_blank" rel="noopener"><i class="fas fa-qrcode"></i> Tautan</a>
    <button class="btn-gold" onclick="window.print()" style="margin-left:8px"><i class="fas fa-print"></i> Cetak</button>
  </div>
  <p class="form-sub" style="margin-top:10px">Tunjukkan QR ini saat di lokasi untuk proses check‑in.</p>
</div></div></div></div>
@endsection
