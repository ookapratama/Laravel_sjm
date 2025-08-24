@extends('layouts.app')
@section('title','Guest Entries')

@push('styles')
<style>
  .stat { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08);
          border-radius:14px; padding:14px 16px; color:#000000 }
  .stat b{ color:#F2D58A; font-size:1.15rem }
  .badge-status{ padding:6px 20px; border-radius:999px; font-weight:500; font-size:.8rem ;white-space: nowrap; }
  .st-confirmed{ background:#1f6f43; color:#eafff2 }
  .st-maybe{ background:#6c5a2f; color:#fff6d9 }
  .st-declined{ background:#6b1f24; color:#ffe6e8 }
  .st-checked_in{ background:#2d4b8a; color:#e8f0ff }
  .table thead th{ white-space:nowrap }
  .notes { max-width:420px }
</style>
@endpush

@section('content')
<div class="page-inner">

  <h2 class="mb-3">Semua Buku Tamu</h2>

  {{-- Filter --}}
  <form class="card mb-3 p-3" method="GET" action="{{ route('guestbook.index') }}">
    <div class="row g-2">
      <div class="col-md-3">
        <label class="form-label">Acara/Undangan</label>
        <select name="invitation_id" class="form-select">
          <option value="">Semua</option>
          @foreach($invitations as $inv)
            <option value="{{ $inv->id }}" @selected(request('invitation_id')==$inv->id)>{{ $inv->title }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="attend_status" class="form-select">
          <option value="">Semua</option>
          @foreach($statuses as $k=>$v)
            <option value="{{ $k }}" @selected(request('attend_status')==$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Dari</label>
        <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label">Sampai</label>
        <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Cari (nama/telp/email/catatan/kode)</label>
        <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Kata kunci...">
      </div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-dark">Terapkan</button>
      <a href="{{ route('guestbook.export')}}" class="btn btn-outline-secondary">Reset</a>
      <a href="{{ route('guestbook.export', request()->query()) }}" class="btn btn-warning ms-auto">
        Export CSV
      </a>
    </div>
  </form>

  {{-- Statistik --}}
  <div class="row g-3 mb-3">
    <div class="col-md-2"><div class="stat">Total<br><b>{{ number_format($stats['total']) }}</b></div></div>
    <div class="col-md-2"><div class="stat">Confirmed<br><b>{{ number_format($stats['confirmed']) }}</b></div></div>
    <div class="col-md-2"><div class="stat">Maybe<br><b>{{ number_format($stats['maybe']) }}</b></div></div>
    <div class="col-md-2"><div class="stat">Declined<br><b>{{ number_format($stats['declined']) }}</b></div></div>
    <div class="col-md-2"><div class="stat">Checked In<br><b>{{ number_format($stats['checked_in']) }}</b></div></div>
  </div>

  {{-- Tabel --}}
  <div class="card">
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Acara</th>
            <th>Nama</th>
            <th>Status</th>
            <th>Checked In</th>
            <th>Telepon</th>
            <th>Email</th>
            <th>Referral Code</th>
            <th>Referrer</th>
            <th class="notes">Catatan</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $m)
            @php
              $statusClass = 'st-'.str_replace('-', '_', $m->attend_status);
            @endphp
            <tr>
              <td>{{ $m->created_at?->format('d M Y H:i') }}</td>
              <td>{{ $m->invitation->title ?? '-' }}</td>
              <td>{{ $m->name }}</td>
              <td><span class="badge-status {{ $statusClass }}">{{ ucfirst(str_replace('_',' ', $m->attend_status)) }}</span></td>
              <td>{{ $m->check_in_at?->format('d M Y H:i') ?? '-' }}</td>
              <td>{{ $m->phone ?? '-' }}</td>
              <td>{{ $m->email ?? '-' }}</td>
              <td>{{ $m->referral_code ?? '-' }}</td>
              <td>{{ $m->referrer->username ?? $m->referrer->name ?? '-' }}</td>
              <td class="notes">{{ \Illuminate\Support\Str::limit($m->notes, 160) }}</td>
              <td>{{ $m->ip_address ?? '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">
      {{ $rows->links() }}
    </div>
  </div>
</div>
@endsection
