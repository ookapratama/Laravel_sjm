@extends('layouts.app')

@section('content')
<div class="page-inner">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="page-title">Undangan Saya</h4>
    <a href="{{ route('inv.create') }}" class="btn btn-dark">Buat Undangan</a>
  </div>

  <div class="row g-3">
    @forelse($invitations as $inv)
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          @if($inv->background_image)
            <img src="{{ asset('storage/'.$inv->background_image) }}" class="card-img-top" style="height:160px;object-fit:cover;">
          @endif
          <div class="card-body">
            <h5 class="mb-1">{{ $inv->title }}</h5>
            <div class="text-muted small mb-2">
              @if($inv->event_datetime) {{ $inv->event_datetime->format('d M Y H:i') }} · @endif
              {{ $inv->venue_name }}
            </div>
            <div class="d-flex gap-2">
              <a class="btn btn-sm btn-outline-dark" href="{{ route('inv.qr',$inv) }}">Bagikan QR</a>
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('inv.edit',$inv) }}">Edit</a>
              <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('inv.public',$inv->slug) }}">Lihat Publik</a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-muted">Belum ada undangan.</div>
    @endforelse
  </div>

  <div class="mt-3">{{ $invitations->links() }}</div>
</div>
@endsection
