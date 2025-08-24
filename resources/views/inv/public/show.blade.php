@extends('layouts.inv')
@section('title', 'Undangan - '.$invitation->title)

@php
  /** @var \App\Models\Invitation $invitation */
  $inv = $invitation;

  // Aset
  $bg          = $inv->background_image ? asset('storage/'.$inv->background_image) : null;
  $companyLogo = asset('images/logo1.png'); // ganti jika path logomu berbeda

  // Palet warna (fallback aman)
  $gold  = $inv->primary_color   ?? '#C9A95D';
  $gold2 = '#F2D58A';
  $dark  = $inv->secondary_color ?? '#0B0B0B';

  // Tanggal (aman terhadap null)
  $dt  = $inv->event_datetime ?? null;
  $dow = $dt ? mb_strtoupper($dt->translatedFormat('l')) : null;   // FRIDAY
  $day = $dt ? $dt->format('d') : null;                            // 08
  $mon = $dt ? mb_strtoupper($dt->translatedFormat('F')) : null;   // AUGUST
  $yr  = $dt ? $dt->format('Y') : null;                            // 2025
  $tm  = $dt ? $dt->translatedFormat('H:i') : null;                // 02:32
@endphp

@push('styles')
  {{-- Font: judul = Cinzel, angka besar = Bebas Neue, isi = Inter --}}
  <link rel="preconnect" href="https://fonts.googleapis.com"> 
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> 
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
@endpush

@section('content')
<style>
  :root{
    --gold:  {{ $gold }};
    --gold2: {{ $gold2 }};
    --dark:  {{ $dark }};
    --ov1: .28;                  /* overlay bg atas (lebih terang) */
    --ov2: .46;                  /* overlay bg bawah */
    --card-alpha: .26;           /* transparansi kartu gelas */
  }

  /* ====== LAYOUT DASAR ====== */
  .inv-page{
    min-height:100vh; padding:72px 20px; position:relative; color:#EEE;
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
  @supports (-webkit-touch-callout:none) { .inv-page.has-photo{ background-attachment:scroll; } }

  .container{
    width:100%; max-width:1100px; margin:0 auto;
  }

  .card{
    position:relative; border-radius:28px; overflow:hidden;
    background: rgba(12,12,12,var(--card-alpha)); backdrop-filter: blur(12px);
    border: 1px solid rgba(201,169,93,.30);
    box-shadow: 0 40px 80px rgba(0,0,0,.45), inset 0 0 0 1px rgba(255,255,255,.02);
  }
  .card--inner{ padding:44px 40px 50px; }

  /* ====== TIPOGRAFI ====== */
  .body-font{
    font-family:'Inter', system-ui, Segoe UI, Arial, sans-serif;
    font-feature-settings:"tnum" 1, "lnum" 1;  /* angka sejajar rapi */
    font-variant-numeric: tabular-nums lining-nums;
  }
  .title{
    font-family:'Cinzel', serif; text-transform:uppercase;
    font-weight:800; font-size:40px; line-height:1.15; letter-spacing:.08em;
    margin:.25rem 0 1.25rem; text-align:center;
    background: linear-gradient(90deg,var(--gold),var(--gold2),var(--gold));
    -webkit-background-clip:text; background-clip:text; color:transparent;
    text-shadow: 0 1px 0 rgba(0,0,0,.05);
  }

  /* ====== HEADER (LOGO + TITLE) ====== */
  .logo-wrap{ display:flex; justify-content:center; margin-top:-30px; margin-bottom:10px; }
  .logo{
    width:100px; height:100px; border-radius:20px; padding:12px; object-fit:contain;
    background:#0f0f0f;
    border:1px solid rgba(201,169,93,.55);
    box-shadow:0 10px 30px rgba(201,169,93,.22), inset 0 0 0 1px rgba(255,255,255,.10);
  }

  /* ====== BLOK TANGGAL BESAR ====== */
  .date-block{
    margin:22px auto 18px; width:min(680px, 96%);
    border-radius:20px; padding:22px 18px;
    background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,0));
    border:1px solid rgba(201,169,93,.35);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.03);
    text-align:center;
  }
  .date-dow{
    font-family:'Inter',sans-serif; font-weight:800; letter-spacing:.20em; font-size:13px; color:#f0e4c1;
  }
  .date-day{
    font-family:'Bebas Neue', system-ui, sans-serif; font-weight:800;
    font-size:92px; line-height:.92; letter-spacing:.01em;
    background:linear-gradient(90deg,var(--gold),var(--gold2)); -webkit-background-clip:text; color:transparent;
    margin:.15rem 0 .35rem;
  }
  .date-month-year{
    font-family:'Inter',sans-serif; font-weight:800; letter-spacing:.18em; font-size:16px; color:#f3e6bf;
  }
  .date-time{
    margin-top:8px; font-weight:700; color:#fff; opacity:.92;
  }

  /* ====== PANEL INFORMASI ====== */
  .panel{
    margin:14px auto 0; width:min(820px,96%);
    border-radius:18px; padding:20px 18px;
    background: rgba(255,255,255,.04);
    border:1px solid rgba(201,169,93,.32);
  }
  .panel__title{
    font-family:'Cinzel', serif; text-transform:uppercase;
    font-weight:800; letter-spacing:.10em; color:#f6e7bd;
    text-align:center; margin:0 0 8px; font-size:16px;
  }
  .panel__content{
    display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px;
  }
  .panel__item{
    font-weight:600; color:#eaeaea;
  }

  /* ====== CTA ====== */
  .cta{
    display:flex; justify-content:center; margin-top:18px;
  }
  .btn-gold{
    display:inline-flex; align-items:center; gap:.5rem; text-decoration:none;
    padding: .95rem 1.6rem; border-radius:999px; font-weight:800; color:#111;
    border:1px solid rgba(201,169,93,.7);
    background:linear-gradient(90deg,var(--gold),var(--gold2),var(--gold));
    box-shadow:0 8px 26px rgba(201,169,93,.22), inset 0 0 0 1px rgba(255,255,255,.22);
  }

  /* ====== RESPONSIVE ====== */
  @media (max-width: 768px){
    .title{ font-size:32px; letter-spacing:.06em; }
    .date-day{ font-size:72px; }
    .card--inner{ padding:36px 20px 40px; }
  }
</style>

<div class="inv-page {{ $bg ? 'has-photo' : '' }}">
  <div class="container body-font">
    <div class="card" role="region" aria-label="Kartu undangan">
      <div class="card--inner">

        {{-- Logo & Title --}}
        <div class="logo-wrap" aria-hidden="{{ $companyLogo ? 'false' : 'true' }}">
          <img class="logo" src="{{ $companyLogo }}" alt="Logo PT. Sair Jaya Mandiri">
        </div>
        <h1 class="title">{{ $inv->title }}</h1>

        {{-- Date Block (opsional) --}}
        @if($dt)
          <section class="date-block" aria-label="Waktu acara">
            @if($dow)<div class="date-dow">{{ $dow }}</div>@endif
            @if($day)<div class="date-day">{{ $day }}</div>@endif
            @if($mon && $yr)<div class="date-month-year">{{ $mon }} {{ $yr }}</div>@endif
            @if($tm)<div class="date-time">🕒 {{ $tm }} WIB</div>@endif
          </section>
        @endif

        {{-- Panel: Tema (pakai description bila ada) --}}
        @if($inv->description)
          <section class="panel" aria-label="Tema acara">
            <h2 class="panel__title">TEMA ACARA</h2>
            <div class="panel__content">
              <div class="panel__item">{{ $inv->description }}</div>
            </div>
          </section>
        @endif

        {{-- Panel: Lokasi --}}
        @if($inv->venue_name || $inv->venue_address || $inv->city)
          <section class="panel" aria-label="Lokasi acara">
            <h2 class="panel__title">ALAMAT ACARA</h2>
            <div class="panel__content">
              @if($inv->venue_name)<div class="panel__item">Lokasi: {{ $inv->venue_name }}</div>@endif
              @if($inv->venue_address)<div class="panel__item">Alamat: {{ $inv->venue_address }}</div>@endif
              @if($inv->city)<div class="panel__item">Kota: {{ $inv->city }}</div>@endif
            </div>
          </section>
        @endif

       @php
  // ambil dari controller -> variabel $ref, kalau kosong cek query/cookie
  $refParam = ($ref ?? null) ?: request('ref') ?: request()->cookie('inv_ref_'.$inv->slug);
  $formUrl  = route('guest.form.inv', $inv->slug) . ($refParam ? ('?ref='.urlencode($refParam).'&src=INV') : '');
@endphp

<div class="text-center">
  <br>
  <a href="{{ $formUrl }}" class="btn-gold">Isi Buku Tamu</a>
</div>


      </div>
    </div>
  </div>
</div>
@endsection
