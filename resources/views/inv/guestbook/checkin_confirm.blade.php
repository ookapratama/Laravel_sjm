@extends('layouts.inv')
@section('title','Konfirmasi Check-in')
@section('content')
<div class="container py-5" style="max-width:560px">
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center">
      <h4 class="mb-2">Check-in — {{ $invitation->title }}</h4>
      <div class="text-muted mb-4">{{ $entry->name }}</div>
      <p class="small text-muted">Tekan tombol di bawah saat Anda sudah berada di lokasi acara.</p>
      <a class="btn btn-dark btn-lg" href="{{ $confirmUrl }}">Check-in Sekarang</a>
      <div class="small text-muted mt-3">Tautan ini aman & bertanda tangan.</div>
    </div>
  </div>
</div>
@endsection
