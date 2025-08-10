@extends('layouts.app')

@section('content')
<div class="page-inner">

  {{-- Banner info --}}
@if($hasOpen)
  <div class="alert alert-info mb-3">
    Masih ada permintaan PIN berjalan. Tombol pembelian dikunci sampai proses selesai.
  </div>
@endif

{{-- Tombol beli / muted --}}
@if(!$hasOpen)
  <button class="btn btn-warning mb-3" data-bs-toggle="modal" data-bs-target="#requestPinModal">
    Beli PIN (Rp1.500.000/pcs)
  </button>
@else
  <button class="btn btn-secondary mb-3" disabled
          data-bs-toggle="tooltip" data-bs-title="Sedang diproses oleh Finance/Admin">
    Beli PIN (terkunci)
  </button>
@endif


  {{-- Modal Request PIN --}}
  <div class="modal fade" id="requestPinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <form class="modal-content" method="POST" action="{{ route('member.pin.request') }}" enctype="multipart/form-data" id="pinRequestForm">
        @csrf
        <div class="modal-header bg-dark text-warning">
          <h5 class="modal-title">Request PIN Aktivasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Jumlah PIN</label>
              <input type="number" name="qty" class="form-control" min="1" max="100" value="1" required>
              <div class="form-text">1 PIN = Rp1.500.000</div>
            </div>

            <div class="col-md-9">
              <label class="form-label d-block">Metode Pembayaran</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input pay-method" type="radio" name="payment_method" value="qris" id="payQris" required>
                <label class="form-check-label" for="payQris">QRIS (statis)</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input pay-method" type="radio" name="payment_method" value="transfer" id="payTf">
                <label class="form-check-label" for="payTf">Transfer Rekening</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input pay-method" type="radio" name="payment_method" value="cash" id="payCash">
                <label class="form-check-label" for="payCash">Cash</label>
              </div>

              {{-- QRIS statis --}}
              <div id="qrisSection" class="border rounded p-2 mt-2 d-none">
                <div class="small mb-2">Scan QR berikut, lalu unggah bukti:</div>
                <img src="{{ asset('images/qris.jpg') }}" alt="QRIS" style="max-height:160px">
              </div>

              {{-- Rekening perusahaan --}}
              <div id="tfSection" class="border rounded p-2 mt-2 d-none">
                <div class="small mb-2">Transfer ke rekening perusahaan:</div>
                <ul class="mb-2 small">
                  <li>Bank BNI • 1234567890 a.n. PT Sair Jaya Mandiri</li>
                  <li>Bank BRI • 777888999 a.n. PT Sair Jaya Mandiri</li>
                </ul>
                <label class="form-label">No/Ref Transaksi (opsional)</label>
                <input type="text" name="payment_reference" class="form-control" placeholder="Mis. No. Ref / berita">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Bukti Pembayaran (foto kamera)</label>
              <input
                type="file"
                name="payment_proof"
                id="payment_proof"
                class="form-control"
                accept="image/*,.pdf"
                capture="environment" />
              <div class="form-text">Wajib untuk QRIS/Transfer. JPG/PNG/PDF, maks 300KB.</div>
              <img id="proof_preview" class="mt-2 d-none" style="max-width: 220px; border-radius: 6px;">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-warning">Kirim Permintaan</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Tabel Status Permintaan --}}
  <div class="card mb-3">
    <div class="card-header bg-dark text-warning">Status Permintaan</div>
    <div class="card-body table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr>
          <th>#</th><th>Tgl</th><th>Qty</th><th>Total</th><th>Status</th><th>Keterangan</th><th>Bukti</th>
        </tr></thead>
        <tbody>
          @forelse($requests as $r)
          <tr>
            <td>{{ $r->id }}</td>
            <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $r->qty }}</td>
            <td>Rp{{ number_format($r->total_price,0,',','.') }}</td>
            <td>{{ strtoupper(str_replace('_',' ', $r->status)) }}</td>
            <td>{{ $r->finance_notes ?? $r->admin_notes }}</td>
            <td>
              @if(!empty($r->payment_proof_path))
                <a href="{{ asset('storage/'.$r->payment_proof_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Lihat</a>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-muted">Belum ada request.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Tabel PIN --}}
  <div class="card">
    <div class="card-header bg-dark text-warning">PIN Saya</div>
    <div class="card-body table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Kode</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse($pins as $p)
          <tr>
            <td><code>{{ $p->code }}</code></td>
            <td>{{ strtoupper($p->status) }}</td>
            <td>
              <button class="btn btn-outline-warning btn-sm" onclick="navigator.clipboard.writeText('{{ $p->code }}')">Copy</button>
            </td>
          </tr>
          @empty
          <tr><td colspan="3" class="text-muted">Belum ada PIN.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{
  new bootstrap.Tooltip(el);
});

if ({{ $hasOpen ? 'true' : 'false' }}) {
  setInterval(async ()=>{
    try{
      const res = await fetch("{{ route('member.pin.status') }}", {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const json = await res.json();
      if(!json.hasOpen) location.reload(); // reload agar tombol aktif & data terbaru tampil
    }catch(e){}
  }, 8000); // cek tiap 8 detik
}

(function() {
  // Toggle QRIS/Transfer sections
  document.querySelectorAll('.pay-method').forEach(r => {
    r.addEventListener('change', () => {
      const v = document.querySelector('.pay-method:checked')?.value;
      document.getElementById('qrisSection').classList.toggle('d-none', v!=='qris');
      document.getElementById('tfSection').classList.toggle('d-none', v!=='transfer');
    });
  });

  // Client-side compression (≤300KB) & preview
  const input = document.getElementById('payment_proof');
  const preview = document.getElementById('proof_preview');
  const MAX_BYTES = 300 * 1024;
  const MAX_W = 1600;

  input.addEventListener('change', async () => {
    const file = input.files?.[0];
    if (!file) return;

    // preview cepat untuk PDF
    if (file.type === 'application/pdf') {
      preview.classList.add('d-none');
      return;
    }

    if (file.type.startsWith('image/')) {
      try {
        const compressed = await compressImageFile(file, MAX_BYTES, MAX_W);
        if (compressed) {
          preview.src = URL.createObjectURL(compressed);
          preview.classList.remove('d-none');

          const dt = new DataTransfer();
          dt.items.add(compressed);
          input.files = dt.files;
        }
      } catch(e){ console.warn('Compress fail:', e); }
    }
  });

  async function compressImageFile(file, maxBytes, maxW) {
    const dataUrl = await readFileAsDataURL(file);
    const img = await loadImage(dataUrl);

    const scale = Math.min(1, maxW / Math.max(img.width, img.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.width * scale);
    canvas.height = Math.round(img.height * scale);

    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    let q = 0.9, blob = await toBlob(canvas, 'image/jpeg', q);
    while (blob.size > maxBytes && q > 0.3) {
      q -= 0.1;
      blob = await toBlob(canvas, 'image/jpeg', q);
    }
    if (blob.size > maxBytes) {
      const s2 = 0.85;
      const tmp = document.createElement('canvas');
      tmp.width = Math.round(canvas.width * s2);
      tmp.height = Math.round(canvas.height * s2);
      tmp.getContext('2d').drawImage(canvas, 0, 0, tmp.width, tmp.height);
      blob = await toBlob(tmp, 'image/jpeg', 0.8);
    }
    return new File([blob], renameToJpg(file.name), { type: 'image/jpeg' });
  }

  function toBlob(canvas, type, quality){ return new Promise(res => canvas.toBlob(b => res(b), type, quality)); }
  function readFileAsDataURL(file){ return new Promise((res,rej)=>{ const fr=new FileReader(); fr.onload=()=>res(fr.result); fr.onerror=rej; fr.readAsDataURL(file); }); }
  function loadImage(src){ return new Promise((res,rej)=>{ const i=new Image(); i.onload=()=>res(i); i.onerror=rej; i.src=src; }); }
  function renameToJpg(name){ return name.replace(/\.[^.]+$/, '') + '.jpg'; }

  // Wajib bukti untuk QRIS/Transfer di submit
  document.getElementById('pinRequestForm').addEventListener('submit', (e) => {
    const method = document.querySelector('.pay-method:checked')?.value;
    const file = document.getElementById('payment_proof').files[0];
    if ((method === 'qris' || method === 'transfer') && !file) {
      e.preventDefault();
      alert('Bukti pembayaran wajib diunggah untuk QRIS/Transfer.');
      return;
    }
    if (file && file.size > MAX_BYTES) {
      e.preventDefault();
      alert('Ukuran bukti pembayaran melebihi 300KB.');
    }
  });
})();
</script>
@endpush
