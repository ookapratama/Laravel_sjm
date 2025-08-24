@extends('layouts.inv')
@section('title', 'Registrasi Member')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/css/formValidation.min.css"
        integrity="sha512-B9GRVQaYJ7aMZO3WC2UvS9xds1D+gWQoNiXiZYRlqIVszL073pHXi0pxWxVycBk0fnacKIE3UHuWfSeETDCe7w=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

@endpush

@section('content')
@php
  $inv   = $invitation;
  $gold  = $inv->primary_color   ?? '#C9A95D';
  $gold2 = '#F2D58A';
  $dark  = $inv->secondary_color ?? '#0B0B0B';
  $bg    = $inv->background_image ? asset('storage/'.$inv->background_image) : null;
  $lockRef = request()->has('ref');
@endphp

<style>
  :root{
    --gold:  {{ $gold }};
    --gold2: {{ $gold2 }};
    --dark:  {{ $dark }};
    --ov1:.28; --ov2:.46; --card-alpha:.26;
  }

  /* ====== LATAR & KARTU – SENADA DENGAN UNDANGAN ====== */
  .inv-page{
    min-height:100vh; padding:72px 20px; color:#EEE;
    background:
      radial-gradient(900px 600px at -10% -10%, rgba(255,255,255,.06), transparent 60%),
      radial-gradient(800px 500px at 110% 0%, rgba(255,255,255,.05), transparent 55%),
      linear-gradient(180deg,#0a0a0a 0%, #000 100%);
  }
  .inv-page.has-photo{
    background:
      linear-gradient(180deg, rgba(0,0,0,var(--ov1)), rgba(0,0,0,var(--ov2))),
      url('{{ $bg }}') center/cover fixed no-repeat;
  }
  @supports (-webkit-touch-callout:none){ .inv-page.has-photo{ background-attachment:scroll; } }

  .container-inv{ width:100%; max-width:1100px; margin:0 auto; }
  .card-inv{
    border-radius:28px; overflow:hidden;
    background: rgba(12,12,12,var(--card-alpha)); backdrop-filter: blur(12px);
    border:1px solid rgba(201,169,93,.30);
    box-shadow:0 40px 80px rgba(0,0,0,.45), inset 0 0 0 1px rgba(255,255,255,.02);
  }
  .card-inv__inner{ padding:44px 40px 50px; }

  .form-title{
    text-align:center; text-transform:uppercase; letter-spacing:.08em;
    font-weight:800; font-size:28px; margin:0 0 4px;
    background:linear-gradient(90deg,var(--gold),var(--gold2),var(--gold));
    -webkit-background-clip:text; background-clip:text; color:transparent;
  }
  .form-sub{
    text-align:center; color:#d9d9d9; opacity:.9; margin-bottom:22px; font-weight:600; font-size:12px; letter-spacing:.12em;
  }

  /* ====== INPUT GELAP ELEGAN ====== */
  .inv-form .form-label{
    color:#f2e9cc; font-weight:700; letter-spacing:.03em; margin-bottom:6px;
  }
  .inv-form .form-control, .inv-form textarea{
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    border:1px solid rgba(201,169,93,.28);
    color:#f5f5f5;
    border-radius:14px; padding:.8rem .95rem;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.03);
    transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
  }
  .inv-form .form-control::placeholder, .inv-form textarea::placeholder{ color:#cfcfcf; opacity:.55; }
  .inv-form .form-control:focus, .inv-form textarea:focus{
    color:#fff; background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
    border-color:var(--gold);
    box-shadow:0 0 0 .18rem rgba(201,169,93,.26), inset 0 0 0 1px rgba(255,255,255,.05);
    outline:0;
  }
  .inv-form .form-control[readonly]{ background:rgba(255,255,255,.05); color:#eaeaea; }
  .inv-form .form-text{ color:#e8ddbd; opacity:.85; }

  /* state error Bootstrap tetap oke */
  .inv-form .is-invalid{ border-color:#ff5e5e !important; box-shadow:0 0 0 .12rem rgba(255,94,94,.18); }
  .inv-form .invalid-feedback{ color:#ffb3b3; }

  /* ====== TOMBOL EMAS ====== */
  .btn-gold{
    display:inline-flex; align-items:center; gap:.5rem; text-decoration:none;
    padding:.95rem 1.6rem; border-radius:999px; font-weight:800; color:#111;
    border:1px solid rgba(201,169,93,.7);
    background:linear-gradient(90deg,var(--gold),var(--gold2),var(--gold));
    box-shadow:0 8px 26px rgba(201,169,93,.22), inset 0 0 0 1px rgba(255,255,255,.22);
  }
  .btn-gold:active{ transform:translateY(1px); }

  .g-14{ row-gap:14px; }
  @media (max-width:768px){ .card-inv__inner{ padding:32px 20px; } }
</style>

<div class="inv-page {{ $bg ? 'has-photo' : '' }}">
  <div class="container-inv">
    <div class="card-inv">
      <div class="card-inv__inner">

        <h1 class="form-title">Registrasi Kehadiran</h1>
        <div class="form-sub">
          {{ $inv->title }}
          @if($inv->event_datetime) · {{ $inv->event_datetime->translatedFormat('d M Y H:i') }} @endif
          @if($inv->venue_name) · {{ $inv->venue_name }} @endif
        </div>

        <form method="POST" action="{{ route('guest.store.inv',$inv->slug) }}" class="inv-form" novalidate>
          @csrf
          <div class="row g-14">
            <div class="col-md-6">
              <label class="form-label">Nama Lengkap *</label>
              <input name="name" class="form-control @error('name') is-invalid @enderror" required
                     value="{{ old('name') }}" placeholder="Tulis nama Anda">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">No. HP</label>
              <input name="phone" class="form-control" inputmode="tel" placeholder="08xxxxxxxxxx"
                     value="{{ old('phone') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                     value="{{ old('email') }}">
            </div>

            <div class="col-md-6">
  <label class="form-label">Kode Referral</label>
  <input name="referral_code" class="form-control"
         value="{{ old('referral_code', request('ref')) }}"
         @if($lockRef) readonly @endif
         placeholder="Masukkan kode (opsional)">
  <div class="form-text">Otomatis dari pengundang bila diakses dari tautan referral.</div>
</div>

            <div class="col-md-6">
                  <label class="form-label">Status Kehadiran *</label>
      <select name="attend_status"
              class="form-control @error('attend_status') is-invalid @enderror" required>
        @php $opt = old('attend_status','confirmed'); @endphp
        <option value="confirmed" {{ $opt==='confirmed' ? 'selected' : '' }}>Hadir (Confirmed)</option>
        <option value="maybe"     {{ $opt==='maybe' ? 'selected' : '' }}>Mungkin (Maybe)</option>
        <option value="declined"  {{ $opt==='declined' ? 'selected' : '' }}>Tidak Hadir (Declined)</option>
      </select>
      @error('attend_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
            <div class="col-12">
              <label class="form-label">Catatan</label>
              <textarea name="notes" rows="3" class="form-control" placeholder="Pesan/ketertarikan singkat">{{ old('notes') }}</textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button class="btn-gold" type="submit">Simpan Kehadiran</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection
