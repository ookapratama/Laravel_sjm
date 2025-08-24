@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:820px">
  <div class="text-center mb-3">
    <h4 class="mb-1">{{ $invitation->title }}</h4>
    <div class="text-muted">
      @if($invitation->event_datetime) {{ $invitation->event_datetime->format('d M Y H:i') }} · @endif
      {{ $invitation->venue_name }}
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body text-center">
      @php $ref = $ref ?? (auth()->user()->referral_code ?? auth()->user()->username); @endphp
{{-- Link Publik --}}
<div class="mb-3">
  <a href="{{ route('inv.public', $invitation->slug).'?ref='.$ref }}" target="_blank">
    {{ route('inv.public', $invitation->slug).'?ref='.$ref }}
  </a>
</div>

      <div class="mb-2">Link Form Buku Tamu (ref: <b>{{ $ref }}</b>):</div>
      <div class="mb-3"><a href="{{ $formUrl }}" target="_blank">{{ $formUrl }}</a></div>

      {{-- gunakan ReferralQrController milikmu --}}
      <img class="img-fluid mb-3"
     src="{{ route('inv.qr.show', $invitation->slug) }}?size=420"
     alt="QR Code">



      <div class="d-flex justify-content-center gap-2">
        <a class="btn btn-dark"
   href="{{ route('inv.qr.dl', $invitation->slug) }}?size=1000">
   Unduh PNG
</a>
        <a class="btn btn-success" target="_blank"
           href="https://wa.me/?text={{ urlencode('Halo, undangan '.$invitation->title.' '.$publicUrl) }}">
          Bagikan via WhatsApp
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
