@extends('layouts.app')

@section('content')
<div class="page-inner">
  <div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-bold">Generate PIN</div>
        <div class="text-muted small">Approve oleh Finance akan muncul di sini untuk digenerate.</div>
      </div>
      <div class="text-end">
        @php
          $toGen = $list->whereIn('status',['finance_approved','generated'])->sum(fn($r)=> $r->qty - $r->generated_count);
        @endphp
        <div class="fw-bold">{{ $toGen }} PIN belum tergenerate</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-dark text-warning">Daftar Permintaan</div>
    <div class="card-body table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Requester</th>
            <th>Qty</th>
            <th>Generated</th>
            <th>Sisa</th>
            <th>Status</th>
            <th>Bukti</th>
            <th style="width:160px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($list as $r)
            @php $sisa = max(0, $r->qty - $r->generated_count); @endphp
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                {{ $r->requester->name }}
                <div class="small text-muted">{{ $r->requester->email }}</div>
              </td>
              <td>{{ $r->qty }}</td>
              <td>{{ $r->generated_count }}</td>
              <td>{{ $sisa }}</td>
              <td>
                <span class="badge bg-{{ [
                  'requested'=>'secondary',
                  'finance_approved'=>'info',
                  'finance_rejected'=>'danger',
                  'generated'=>'success'
                ][$r->status] ?? 'secondary' }}">
                  {{ strtoupper(str_replace('_',' ',$r->status)) }}
                </span>
              </td>
              <td>
                @if(!empty($r->payment_proof))
                  <button class="btn btn-outline-secondary btn-sm"
                          data-bs-toggle="modal" data-bs-target="#proofModal"
                          data-title="Bukti #{{ $r->id }}"
                          data-url="{{ asset('storage/'.$r->payment_proof) }}">
                    Lihat
                  </button>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              @php $sisa = max(0, $r->qty - $r->generated_count); @endphp
                <td>
                @if($sisa > 0 && in_array($r->status, ['finance_approved','generated']))
                    <form method="POST" action="{{ route('admin.pin.generate', $r->id) }}"
                        onsubmit="return confirm('Generate PIN untuk request #{{ $r->id }}?')">
                    @csrf
                    <button class="btn btn-warning btn-sm">Generate</button>
                    </form>
                @elseif($sisa === 0 && $r->status === 'generated')
                    <button class="btn btn-secondary btn-sm" disabled
                            data-bs-toggle="tooltip" data-bs-title="Semua PIN sudah terbit">
                    Sudah Terbit
                    </button>
                @else
                    <span class="text-muted small">-</span>
                @endif
                </td>
            </tr>

            {{-- (Opsional) tampilkan kode yang sudah dibuat untuk request ini --}}
            @if($r->status === 'generated' && $r->pins()->exists())
              <tr>
                <td></td>
                <td colspan="7" class="small">
                  <div class="fw-bold mb-1">PIN yang terbit:</div>
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($r->pins as $p)
                      <code class="px-2 py-1 bg-light rounded d-inline-block">{{ $p->code }}</code>
                    @endforeach
                  </div>
                </td>
              </tr>
            @endif

          @empty
            <tr><td colspan="8" class="text-muted">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Modal Preview Bukti (reuse dari Finance) --}}
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="proofTitle">Bukti</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center" id="proofBody"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
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
})();
</script>
@endpush
