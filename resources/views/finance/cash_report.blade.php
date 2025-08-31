
@extends('layouts.app')

@section('content')
<div class="page-inner">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Report Kas</h3>
    <div class="badge bg-dark fs-6">Saldo akhir: <b>Rp {{ number_format($saldoAkhir,0,',','.') }}</b></div>
  </div>

  {{-- Filter tanggal --}}
  <form class="card card-body mb-3" method="get" action="{{ route('finance.cash.report') }}">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Dari Tanggal</label>
        <input type="date" name="from" class="form-control" value="{{ $from }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Sampai Tanggal</label>
        <input type="date" name="to" class="form-control" value="{{ $to }}">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100">Terapkan Filter</button>
      </div>
      <div class="col-md-3">
        <a href="{{ route('finance.cash.report') }}" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </div>
  </form>

  {{-- Form Pengeluaran Lain-lain --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <b>Input Pengeluaran Lain-lain</b>
    </div>
    <form method="post" action="{{ route('finance.expense.store') }}" class="card-body">
      @csrf
      @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
      @endif
      @error('date')               <div class="text-danger small">{{ $message }}</div>@enderror
      @error('amount')             <div class="text-danger small">{{ $message }}</div>@enderror
      @error('notes')              <div class="text-danger small">{{ $message }}</div>@enderror
      @error('payment_channel')    <div class="text-danger small">{{ $message }}</div>@enderror
      @error('payment_reference')  <div class="text-danger small">{{ $message }}</div>@enderror

      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Tanggal</label>
          <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Jumlah (Rp)</label>
          <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="0" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Channel (opsional)</label>
          <input type="text" name="payment_channel" class="form-control" placeholder="Cash / Bank / E-Wallet">
        </div>
        <div class="col-md-3">
          <label class="form-label">Reference (opsional)</label>
          <input type="text" name="payment_reference" class="form-control" placeholder="No. bukti/transfer">
        </div>
        <div class="col-12">
          <label class="form-label">Keterangan</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Tulis keterangan pengeluaran..."></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-danger">Simpan Pengeluaran</button>
        </div>
      </div>
      <input type="hidden" name="source" value="lain-lain"><!-- opsional, dikunci di controller -->
    </form>
  </div>

  <div class="row">
    {{-- Ringkasan Harian --}}
    <div class="col-lg-6">
      <div class="card mb-4">
        <div class="card-header"><b>Ringkasan Harian</b></div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr>
              <th>Tanggal</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Saldo</th>
            </tr></thead>
            <tbody>
              @forelse($daily as $d)
              <tr>
                <td>{{ $d->tanggal }}</td>
                <td class="text-end">Rp {{ number_format($d->total_in,0,',','.') }}</td>
                <td class="text-end">Rp {{ number_format($d->total_out,0,',','.') }}</td>
                <td class="text-end"><b>Rp {{ number_format($d->saldo,0,',','.') }}</b></td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-muted">Belum ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Ringkasan Bulanan --}}
    <div class="col-lg-6">
      <div class="card mb-4">
        <div class="card-header"><b>Ringkasan Bulanan</b></div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr>
              <th>Bulan</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Saldo</th>
            </tr></thead>
            <tbody>
              @forelse($monthly as $m)
              <tr>
                <td>{{ $m->bulan }}</td>
                <td class="text-end">Rp {{ number_format($m->total_in,0,',','.') }}</td>
                <td class="text-end">Rp {{ number_format($m->total_out,0,',','.') }}</td>
                <td class="text-end"><b>Rp {{ number_format($m->saldo,0,',','.') }}</b></td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-muted">Belum ada data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Detail terbaru --}}
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <b>Transaksi Terbaru</b>
    <small class="text-muted">Paginator 50/baris (server-side)</small>
  </div>
  <div class="table-responsive">
    <table id="txTable" class="table table-sm mb-0 w-100">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Type</th>
          <th>Sumber</th>
          <th class="text-end">Jumlah</th>
          <th>Channel</th>
          <th>Ref</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>


</div>

@push('scripts')
<script>
  $(function () {
    const rupiah = new Intl.NumberFormat('id-ID');

    $('#txTable').DataTable({
      processing: true,
      serverSide: true,
      searching: true,
      ordering: true,
      lengthChange: true,
      pageLength: 50,
      lengthMenu: [[50, 100, 200], [50, 100, 200]],
      ajax: {
        url: "{{ route('finance.cash.report.data') }}",
        type: 'GET',
        data: function(d){
          d.from = $('input[name="from"]').val();
          d.to   = $('input[name="to"]').val();
        },
        error: function(xhr){
          console.error('AJAX error', xhr.status, xhr.responseText);
          alert('Gagal memuat data transaksi. Cek Console/Network.');
        }
      },
      columns: [
        { data: 'created_at',
          render: function(val){
            const ts = Date.parse(val);
            return isNaN(ts) ? (val ?? '') : new Date(ts).toLocaleString('id-ID');
          }
        },
        { data: 'type',
          render: function(val){
            if (val === 'in')  return '<span class="badge bg-success">IN</span>';
            if (val === 'out') return '<span class="badge bg-danger">OUT</span>';
            return val ?? '';
          }
        },
        { data: 'source', defaultContent: '' },
        { data: 'amount', className: 'text-end',
          render: function(val){ return 'Rp ' + rupiah.format(val ?? 0); }
        },
        { data: 'payment_channel',   defaultContent: '' },
        { data: 'payment_reference', defaultContent: '' },
        { data: 'notes',
          render: function(val){
            if (!val) return '';
            const safe = String(val)
              .replaceAll('&','&amp;').replaceAll('<','&lt;')
              .replaceAll('>','&gt;').replaceAll('"','&quot;')
              .replaceAll("'","&#39;");
            return safe.length > 120 ? `<span title="${safe}">${safe.slice(0,120)}…</span>` : safe;
          }
        }
      ],
      order: [[0,'desc']]
    });
  });
</script>
@endpush


@endsection
