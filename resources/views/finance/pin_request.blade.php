@extends('layouts.app')

@section('content')
<div class="page-inner">
  <div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-bold">Verifikasi Pembelian PIN</div>
        <div class="text-muted small">
          Tinjau bukti pembayaran, lalu Approve atau Reject.
        </div>
      </div>
      <div class="text-end">
        @php
          $totalRequested = $list->sum('qty');
          $totalRupiah = $list->sum('total_price');
        @endphp
        <div class="fw-bold">{{ $totalRequested }} PIN</div>
        <div class="text-muted">Total: Rp{{ number_format($totalRupiah,0,',','.') }}</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-info text-white">Daftar Permintaan</div>
    <div class="card-body table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Requester</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Metode</th>
            <th>Ref</th>
            <th>Bukti</th>
            <th>Status</th>
            <th style="width:210px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                {{ $r->requester->name }}
                <div class="text-muted small">{{ $r->requester->email }}</div>
                <div class="text-muted small">{{ $r->requester->phone }}</div>
              </td>
              <td>{{ $r->qty }}</td>
              <td>Rp{{ number_format($r->total_price,0,',','.') }}</td>
              <td class="text-uppercase">{{ $r->payment_method ?: '-' }}</td>
              <td class="small">{{ $r->payment_reference ?: '-' }}</td>
              <td>
                @if(!empty($r->payment_proof))
                  @php $url = asset('storage/'.$r->payment_proof); @endphp
                  <button type="button" class="btn btn-outline-secondary btn-sm"
                          data-bs-toggle="modal" data-bs-target="#proofModal"
                          data-title="Bukti #{{ $r->id }}"
                          data-url="{{ $url }}">
                    Lihat
                  </button>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                <span class="badge bg-{{ [
                  'requested'=>'secondary',
                  'finance_approved'=>'info',
                  'finance_rejected'=>'danger',
                  'generated'=>'success'
                ][$r->status] ?? 'secondary' }}">
                  {{ strtoupper(str_replace('_',' ',$r->status)) }}
                </span>
                @if($r->finance_notes)
                  <div class="small text-muted mt-1">{{ $r->finance_notes }}</div>
                @endif
              </td>
              <td class="d-flex gap-1">
                @if($r->status==='requested')
                  {{-- APPROVE --}}
                  <form method="POST" action="{{ route('finance.pin.approve', $r->id) }}"
                        class="d-inline" onsubmit="return confirm('Approve request #{{ $r->id }}?')">
                    @csrf @method('PUT')
                    <input type="hidden" name="payment_method" value="{{ $r->payment_method ?: 'transfer' }}">
                    <input type="hidden" name="payment_reference" value="{{ $r->payment_reference ?: ('REF-'.$r->id.'-'.now()->format('YmdHis')) }}">
                    <button class="btn btn-success btn-sm">Approve</button>
                  </form>

                  {{-- REJECT (modal textarea cepat) --}}
                  <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                          data-bs-target="#rejectModal" data-id="{{ $r->id }}">
                    Reject
                  </button>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-muted">Belum ada permintaan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Modal Preview Bukti --}}
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="proofTitle">Bukti</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center" id="proofBody">
        {{-- dynamically injected --}}
      </div>
    </div>
  </div>
</div>

{{-- Modal Reject --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="rejectForm">
      @csrf @method('PUT')
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title">Tolak Permintaan</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Alasan penolakan</label>
        <textarea name="finance_notes" class="form-control" rows="3" required></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger">Kirim Penolakan</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  // Preview bukti: image/pdf
  const proofModal = document.getElementById('proofModal');
  proofModal?.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    const url = btn.getAttribute('data-url');
    const title = btn.getAttribute('data-title') || 'Bukti';
    document.getElementById('proofTitle').textContent = title;
    const body = document.getElementById('proofBody');
    body.innerHTML = '';
    if (url.endsWith('.pdf')) {
      body.innerHTML = `<iframe src="${url}" style="width:100%;height:70vh;border:0;"></iframe>`;
    } else {
      body.innerHTML = `<img src="${url}" style="max-width:100%;max-height:70vh;border-radius:8px;">`;
    }
  });

  // Reject modal: set action ke route reject
  const rejectModal = document.getElementById('rejectModal');
  rejectModal?.addEventListener('show.bs.modal', e => {
    const id = e.relatedTarget.getAttribute('data-id');
    const form = document.getElementById('rejectForm');
    form.action = "{{ url('/finance/pin-requests') }}/" + id + "/reject";
  });
})();
</script>
@endpush
