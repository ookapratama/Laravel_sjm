@extends('layouts.app')

@section('content')
    <div class="page-inner">

        {{-- Banner info --}}
        @if ($hasOpen)
            <div class="alert alert-info mb-3">
                Masih ada permintaan PIN berjalan. Tombol pembelian dikunci sampai proses selesai.
            </div>
        @endif

        {{-- Tombol beli / muted --}}
        @if (!$hasOpen)
            <button class="btn btn-warning mb-3" data-bs-toggle="modal" data-bs-target="#requestPinModal">
                Beli PIN (Rp750.000/pcs)
            </button>
        @else
            <button class="btn btn-secondary mb-3" disabled data-bs-toggle="tooltip"
                data-bs-title="Sedang diproses oleh Finance/Admin">
                Beli PIN (terkunci)
            </button>
        @endif


        {{-- Modal Request PIN --}}
        <div class="modal fade" id="requestPinModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('member.pin.request') }}"
                    enctype="multipart/form-data" id="pinRequestForm">
                    @csrf
                    <div class="modal-header bg-dark text-warning">
                        <h5 class="modal-title">Request PIN Aktivasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Jumlah PIN</label>
                                <input type="number" name="qty" class="form-control" min="1" max="100"
                                    value="1" required>
                                <div class="form-text">1 PIN = Rp750.000</div>
                            </div>

                            <div class="col-md-9">
                                <label class="form-label d-block">Metode Pembayaran</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input pay-method" type="radio" name="payment_method"
                                        value="qris" id="payQris" required>
                                    <label class="form-check-label" for="payQris">QRIS (statis)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input pay-method" type="radio" name="payment_method"
                                        value="transfer" id="payTf">
                                    <label class="form-check-label" for="payTf">Transfer Rekening</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input pay-method" type="radio" name="payment_method"
                                        value="cash" id="payCash">
                                    <label class="form-check-label" for="payCash">Cash</label>
                                </div>

                                {{-- QRIS statis --}}
                                <div id="qrisSection" class="border rounded p-2 mt-2 d-none">
                                    <div class="small mb-2">Scan QR berikut, lalu unggah bukti:</div>
                                    {{-- <img src="{{ asset('images/qris.jpg') }}" alt="QRIS" style="max-height:160px"> --}}
                                </div>

                                {{-- Rekening perusahaan --}}
                                <div id="tfSection" class="border rounded p-2 mt-2 d-none">
                                    <div class="small mb-2">Transfer ke rekening perusahaan:</div>
                                    <ul class="mb-2 small">
                                        <li>Bank MANDIRI • 1740011176609 a.n. PT Sair Jaya Mandiri</li>
                                    </ul>
                                    <label class="form-label">No/Ref Transaksi (opsional)</label>
                                    <input type="text" name="payment_reference" class="form-control"
                                        placeholder="Mis. No. Ref / berita">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Bukti Pembayaran (foto kamera)</label>
                                <input type="file" name="payment_proof" id="payment_proof" class="form-control"
                                    accept="image/*,.pdf" capture="environment" />
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

        {{-- Modal Transfer PIN --}}
        <div class="modal fade" id="transferPinModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('member.pin.transfer') }}" id="pinTransferForm">
                    @csrf
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-paper-plane me-2"></i>Transfer PIN ke Downline
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="pin_id" id="transferPinId">

                        <!-- PIN Info -->
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-key fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="alert-heading mb-1">PIN yang akan ditransfer</h6>
                                    <div class="mb-1">
                                        <strong>Kode:</strong> <span id="transferPinCode" class="badge bg-dark">-</span>
                                    </div>
                                    <div>
                                        <strong>Nilai:</strong> <span id="transferPinValue"
                                            class="text-success fw-bold">Rp 750.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Select Downline -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-users me-2"></i>Pilih Downline Tujuan
                            </label>
                            <select name="downline_id" id="downlineSelect" class="form-select" required>
                                <option value="">-- Pilih Downline --</option>
                                @foreach ($downlines as $downline)
                                    <option value="{{ $downline->id }}" data-username="{{ $downline->username }}"
                                        data-name="{{ $downline->name }}">
                                        {{ $downline->name }} ({{ $downline->username }})
                                        -
                                        {{ $downline->position === 'left' ? 'Kiri' : ($downline === 'right' ? 'Kanan' : 'Jaringan belum ada') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Pilih downline yang akan menerima PIN ini</div>
                        </div>

                        <!-- Downline Preview -->
                        <div id="downlinePreview" class="card border-success d-none">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1" id="previewName">-</h6>
                                        <small class="text-muted">@<span id="previewUsername">-</span></small>
                                        <br>
                                        <small class="badge bg-info" id="previewPosition">-</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle text-success fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer Notes -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-comment me-2"></i>Catatan (Opsional)
                            </label>
                            <textarea name="transfer_notes" class="form-control" rows="3"
                                placeholder="Tambahkan catatan untuk downline..."></textarea>
                        </div>

                        <!-- Confirmation -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmTransfer" required>
                            <label class="form-check-label" for="confirmTransfer">
                                Saya yakin akan mentransfer PIN ini ke downline yang dipilih.
                                <strong class="text-warning">Aksi ini tidak dapat dibatalkan.</strong>
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitTransfer" disabled>
                            <i class="fas fa-paper-plane me-2"></i>Transfer PIN
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Bulk Transfer PIN --}}
        <div class="modal fade" id="bulkTransferPinModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <form class="modal-content" id="bulkTransferForm">
                    @csrf
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-share-alt me-2"></i>Bulk Transfer PIN ke Downline
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Progress Bar --}}
                        <div id="bulkProgress" class="progress mb-3 d-none">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: 0%">0%</div>
                        </div>

                        {{-- PIN Selection Section --}}
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-key me-2"></i>Pilih PIN yang akan ditransfer
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllPins">
                                                <label class="form-check-label fw-bold" for="selectAllPins">
                                                    Pilih Semua PIN
                                                </label>
                                            </div>
                                        </div>

                                        <div id="availablePinsList" class="border rounded p-2"
                                            style="max-height: 300px; overflow-y: auto;">
                                            <div class="text-center p-3">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 mb-0">Memuat PIN available...</p>
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <span id="selectedPinCount">0</span> PIN dipilih
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-users me-2"></i>Pengaturan Transfer
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        {{-- Transfer Method --}}
                                        <div class="mb-3">
                                            <label class="form-label">Mode Transfer</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="transferMode"
                                                    id="modeSequential" value="sequential" checked>
                                                <label class="form-check-label" for="modeSequential">
                                                    <strong>Sequential</strong> - Satu PIN ke satu downline
                                                </label>
                                            </div>
                                            {{-- <div class="form-check">
                                                <input class="form-check-input" type="radio" name="transferMode"
                                                    id="modeDistribute" value="distribute">
                                                <label class="form-check-label" for="modeDistribute">
                                                    <strong>Distribute</strong> - Bagikan ke multiple downlines
                                                </label>
                                            </div> --}}
                                        </div>

                                        {{-- Sequential Mode --}}
                                        <div id="sequentialSection">
                                            <label class="form-label">Pilih Downline</label>
                                            <select id="singleDownlineSelect" class="form-select">
                                                <option value="">-- Pilih Downline --</option>
                                                @foreach ($downlines as $downline)
                                                    <option value="{{ $downline->id }}"
                                                        data-username="{{ $downline->username }}"
                                                        data-name="{{ $downline->name }}">
                                                        {{ $downline->name }} ({{ $downline->username }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Semua PIN akan ditransfer ke downline ini</div>
                                        </div>

                                        {{-- Distribute Mode --}}
                                        <div id="distributeSection" class="d-none">
                                            <div class="alert alert-info">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Mode distribute akan membagi PIN secara merata ke downlines yang dipilih
                                                </small>
                                            </div>

                                            <label class="form-label">Pilih Downlines</label>
                                            <div id="downlineCheckboxes" style="max-height: 150px; overflow-y: auto;">
                                                @foreach ($downlines as $downline)
                                                    <div class="form-check">
                                                        <input class="form-check-input downline-checkbox" type="checkbox"
                                                            value="{{ $downline->id }}"
                                                            id="downline_{{ $downline->id }}"
                                                            data-username="{{ $downline->username }}"
                                                            data-name="{{ $downline->name }}">
                                                        <label class="form-check-label"
                                                            for="downline_{{ $downline->id }}">
                                                            {{ $downline->name }} ({{ $downline->username }})
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Global Notes --}}
                                        <div class="mt-3">
                                            <label class="form-label">
                                                <i class="fas fa-comment me-1"></i>Catatan untuk semua transfer
                                            </label>
                                            <textarea id="bulkTransferNotes" class="form-control" rows="3"
                                                placeholder="Catatan yang akan ditambahkan ke semua transfer..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Transfer Preview --}}
                        <div id="transferPreview" class="mt-3 d-none">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-eye me-2"></i>Preview Transfer
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="transferPreviewContent" style="max-height: 200px; overflow-y: auto;">
                                        <!-- Preview content will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Confirmation --}}
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmBulkTransfer" required>
                            <label class="form-check-label" for="confirmBulkTransfer">
                                Saya yakin akan mentransfer PIN yang dipilih ke downline terpilih.
                                <strong class="text-danger">Aksi ini tidak dapat dibatalkan.</strong>
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="button" id="generatePreview" class="btn btn-info" disabled>
                            <i class="fas fa-eye me-2"></i>Preview Transfer
                        </button>
                        <button type="submit" id="submitBulkTransfer" class="btn btn-success" disabled>
                            <i class="fas fa-share-alt me-2"></i>Mulai Bulk Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Modal --}}
        <div class="modal fade" id="bulkResultModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-chart-bar me-2"></i>Hasil Bulk Transfer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="bulkResultContent">
                            <!-- Results will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="fas fa-check me-2"></i>Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>


        {{-- Tabel Status Permintaan --}}
        <div class="card mb-3">
            <div class="card-header bg-dark text-warning">
                Status Permintaan

            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tgl</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            <tr>
                                <td>{{ $r->id }}</td>
                                <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $r->qty }}</td>
                                <td>Rp{{ number_format($r->total_price, 0, ',', '.') }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', $r->status)) }}</td>
                                <td>{{ $r->finance_notes ?? $r->admin_notes }}</td>
                                <td>
                                    @if (!empty($r->payment_proof_path))
                                        <a href="{{ asset('storage/' . $r->payment_proof_path) }}" target="_blank"
                                            class="btn btn-outline-secondary btn-sm">Lihat</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">Belum ada request.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Enhanced Tabel PIN --}}
        <div class="card">
            <div class="card-header bg-dark text-warning d-flex justify-content-between align-items-center flex-wrap">
                <span>PIN Saya</span>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    {{-- Status badges --}}
                    <span class="badge bg-success">Available: {{ $pins->where('status', 'unused')->count() }}</span>
                    <span class="badge bg-info">Transferred: {{ $pins->where('status', 'transferred')->count() }}</span>
                    <span class="badge bg-secondary">Used: {{ $pins->where('status', 'used')->count() }}</span>

                    {{-- Bulk Transfer Button --}}
                    @if ($pins->where('status', 'unused')->count() > 1)
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                            data-bs-target="#bulkTransferPinModal" title="Transfer Multiple PIN Sekaligus">
                            <i class="fas fa-share-alt me-1"></i>Bulk Transfer
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Status</th>
                            <th>Transfer ke</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pins as $p)
                            <tr>
                                <td><code>{{ $p->code }}</code></td>
                                <td>
                                    @if ($p->status === 'unused')
                                        <span class="badge bg-success">AVAILABLE</span>
                                    @elseif($p->status === 'transferred')
                                        <span class="badge bg-info">TRANSFERRED</span>
                                    @elseif($p->status === 'used')
                                        <span class="badge bg-secondary">USED</span>
                                    @else
                                        <span class="badge bg-warning">{{ strtoupper($p->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->status === 'transferred')
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width: 25px; height: 25px;">
                                                <i class="fas fa-user text-white" style="font-size: 10px;"></i>
                                            </div>
                                            <div>
                                                <small class="fw-bold">{{ $p->transferred_name ?? '-' }}</small><br>
                                                <small
                                                    class="text-muted">Username:{{ $p->transferred_username ?? '-' }}</small>
                                            </div>
                                        </div>
                                    @elseif($p->status === 'used')
                                        <div class="d-flex align-items-center">
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2"
                                                style="width: 25px; height: 25px;">
                                                <i class="fas fa-check text-white" style="font-size: 10px;"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted">{{ $p->used_name ?? '-' }}</small><br>
                                                <small class="text-muted">Username:{{ $p->used_username ?? '-' }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->transferred_date)
                                        {{ date('d/M/Y H:i', strtotime($p->transferred_date)) }}
                                    @elseif($p->created_at)
                                        <small class="text-muted">Activated:
                                            {{ date('d/M/Y H:i', strtotime($p->created_at)) }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-secondary"
                                            onclick="navigator.clipboard.writeText('{{ $p->code }}')"
                                            data-bs-toggle="tooltip" title="Copy PIN Code">
                                            <i class="fas fa-copy"></i>
                                            Copy
                                        </button>

                                        @if ($p->status === 'unused')
                                            <button class="btn btn-outline-primary"
                                                onclick="openTransferModal({{ $p->id }}, '{{ $p->code }}')"
                                                data-bs-toggle="tooltip" title="Transfer ke Downline">
                                                <i class="fas fa-paper-plane"></i>
                                                Transfer Pin
                                            </button>
                                        @endif

                                        {{-- <button class="btn btn-outline-info"
                                            onclick="showPinHistory({{ $p->id }})" data-bs-toggle="tooltip"
                                            title="Lihat History">
                                            <i class="fas fa-history"></i>
                                            History
                                        </button> --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                    Belum ada PIN.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // Original PIN request functionality
        (function() {
            // Toggle QRIS/Transfer sections
            document.querySelectorAll('.pay-method').forEach(r => {
                r.addEventListener('change', () => {
                    const v = document.querySelector('.pay-method:checked')?.value;
                    document.getElementById('qrisSection').classList.toggle('d-none', v !== 'qris');
                    document.getElementById('tfSection').classList.toggle('d-none', v !== 'transfer');
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
                    } catch (e) {
                        console.warn('Compress fail:', e);
                    }
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

                let q = 0.9,
                    blob = await toBlob(canvas, 'image/jpeg', q);
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
                return new File([blob], renameToJpg(file.name), {
                    type: 'image/jpeg'
                });
            }

            function toBlob(canvas, type, quality) {
                return new Promise(res => canvas.toBlob(b => res(b), type, quality));
            }

            function readFileAsDataURL(file) {
                return new Promise((res, rej) => {
                    const fr = new FileReader();
                    fr.onload = () => res(fr.result);
                    fr.onerror = rej;
                    fr.readAsDataURL(file);
                });
            }

            function loadImage(src) {
                return new Promise((res, rej) => {
                    const i = new Image();
                    i.onload = () => res(i);
                    i.onerror = rej;
                    i.src = src;
                });
            }

            function renameToJpg(name) {
                return name.replace(/\.[^.]+$/, '') + '.jpg';
            }

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

        // PIN Transfer functionality
        function openTransferModal(pinId, pinCode) {
            document.getElementById('transferPinId').value = pinId;
            document.getElementById('transferPinCode').textContent = pinCode;

            // Reset form
            document.getElementById('downlineSelect').value = '';
            document.getElementById('downlinePreview').classList.add('d-none');
            document.getElementById('confirmTransfer').checked = false;
            document.getElementById('submitTransfer').disabled = true;
            document.querySelector('textarea[name="transfer_notes"]').value = '';

            new bootstrap.Modal(document.getElementById('transferPinModal')).show();
        }

        // Downline selection handler
        document.getElementById('downlineSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const preview = document.getElementById('downlinePreview');

            if (this.value) {
                document.getElementById('previewName').textContent = selectedOption.dataset.name;
                document.getElementById('previewUsername').textContent = selectedOption.dataset.username;

                // Get position from option text
                const position = selectedOption.text.includes('Kiri') ? 'Downline Kiri' : (selectedOption.text
                    .includes('Kanan') ? 'Downline Kanan' : 'Belum ada jaringan');
                document.getElementById('previewPosition').textContent = position;

                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }

            updateSubmitButton();
        });

        // Confirmation checkbox handler
        document.getElementById('confirmTransfer').addEventListener('change', updateSubmitButton);

        function updateSubmitButton() {
            const downlineSelected = document.getElementById('downlineSelect').value;
            const confirmed = document.getElementById('confirmTransfer').checked;
            document.getElementById('submitTransfer').disabled = !(downlineSelected && confirmed);
        }

        // Transfer form submission
        document.getElementById('pinTransferForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitTransfer');
            const originalText = submitBtn.innerHTML;

            // Cek jika sedang dalam proses
            if (submitBtn.dataset.processing === 'true') {
                return; // Prevent double submission
            }

            // Mark as processing
            submitBtn.dataset.processing = 'true';
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mentransfer...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(data.message || 'PIN berhasil ditransfer!');
                        } else {
                            alert('PIN berhasil ditransfer!');
                        }

                        bootstrap.Modal.getInstance(document.getElementById('transferPinModal')).hide();

                        // PERBAIKAN: Reload otomatis untuk refresh data
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        throw new Error(data.message || 'Transfer gagal');
                    }
                })
                .catch(error => {
                    console.error('Transfer error:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(error.message || 'Terjadi kesalahan saat transfer');
                    } else {
                        alert(error.message || 'Terjadi kesalahan saat transfer');
                    }

                    // PERBAIKAN: Hanya restore button jika error
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.dataset.processing = 'false';
                });
            // HAPUS .finally() yang me-restore button pada success
        });

        // Show PIN history function
        function showPinHistory(pinId) {
            // You can implement this to show detailed history of the PIN
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'PIN History',
                    html: `
                            <div class="text-start">
                            <div class="timeline">
                                <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">PIN Created</h6>
                                    <p class="timeline-info">PIN berhasil dibuat</p>
                                    <small class="text-muted">2 hari lalu</small>
                                </div>
                                </div>
                            </div>
                            <p class="text-muted mt-3">History lengkap dalam pengembangan...</p>
                            </div>
                        `,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#6c757d'
                });
            } else {
                alert('Fitur history dalam pengembangan');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            let availablePins = [];
            let selectedPins = [];

            // Load available pins when modal opens
            $('#bulkTransferPinModal').on('shown.bs.modal', function() {
                loadAvailablePins();
            });

            // Transfer mode change handler
            document.querySelectorAll('input[name="transferMode"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const isDistribute = this.value === 'distribute';
                    document.getElementById('sequentialSection').classList.toggle('d-none',
                        isDistribute);
                    document.getElementById('distributeSection').classList.toggle('d-none', !
                        isDistribute);
                    updateButtons();
                });
            });

            // Select all pins handler
            document.getElementById('selectAllPins').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.pin-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                updateSelectedPins();
            });

            // Generate preview handler
            document.getElementById('generatePreview').addEventListener('click', generateTransferPreview);

            // Form submit handler
            document.getElementById('bulkTransferForm').addEventListener('submit', function(e) {
                e.preventDefault();
                performBulkTransfer();
            });

            // Confirmation checkbox handler
            document.getElementById('confirmBulkTransfer').addEventListener('change', updateButtons);

            // Downline select handler
            document.getElementById('singleDownlineSelect').addEventListener('change', updateButtons);

            // Downline checkboxes handler
            document.querySelectorAll('.downline-checkbox').forEach(cb => {
                cb.addEventListener('change', updateButtons);
            });

            function loadAvailablePins() {
                fetch('{{ route('member.pin.available-for-bulk') }}', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            availablePins = data.pins;
                            renderAvailablePins();
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        document.getElementById('availablePinsList').innerHTML =
                            `<div class="alert alert-danger">Gagal memuat PIN: ${error.message}</div>`;
                    });
            }

            function renderAvailablePins() {
                const container = document.getElementById('availablePinsList');

                if (availablePins.length === 0) {
                    container.innerHTML = '<div class="text-center p-3 text-muted">Tidak ada PIN available</div>';
                    return;
                }

                const html = availablePins.map(pin => `
            <div class="form-check">
                <input class="form-check-input pin-checkbox" type="checkbox" 
                       value="${pin.id}" id="pin_${pin.id}"
                       data-code="${pin.code}">
                <label class="form-check-label" for="pin_${pin.id}">
                    <code>${pin.code}</code>
                    <small class="text-muted ms-2">
                        ${new Date(pin.created_at).toLocaleDateString('id-ID')}
                    </small>
                </label>
            </div>
        `).join('');

                container.innerHTML = html;

                // Add change listeners to checkboxes
                container.querySelectorAll('.pin-checkbox').forEach(cb => {
                    cb.addEventListener('change', updateSelectedPins);
                });
            }

            function updateSelectedPins() {
                const checkboxes = document.querySelectorAll('.pin-checkbox:checked');
                selectedPins = Array.from(checkboxes).map(cb => ({
                    id: cb.value,
                    code: cb.dataset.code
                }));

                document.getElementById('selectedPinCount').textContent = selectedPins.length;
                updateButtons();

                // Update select all checkbox state
                const selectAll = document.getElementById('selectAllPins');
                const totalPins = document.querySelectorAll('.pin-checkbox').length;
                selectAll.indeterminate = selectedPins.length > 0 && selectedPins.length < totalPins;
                selectAll.checked = selectedPins.length === totalPins && totalPins > 0;
            }

            function updateButtons() {
                const hasPins = selectedPins.length > 0;
                const hasDownlines = getSelectedDownlines().length > 0;
                const confirmed = document.getElementById('confirmBulkTransfer').checked;

                document.getElementById('generatePreview').disabled = !hasPins || !hasDownlines;
                document.getElementById('submitBulkTransfer').disabled = !hasPins || !hasDownlines || !confirmed;
            }

            function getSelectedDownlines() {
                const mode = document.querySelector('input[name="transferMode"]:checked').value;

                if (mode === 'sequential') {
                    const select = document.getElementById('singleDownlineSelect');
                    if (select.value) {
                        return [{
                            id: select.value,
                            name: select.options[select.selectedIndex].dataset.name,
                            username: select.options[select.selectedIndex].dataset.username
                        }];
                    }
                } else {
                    const checkboxes = document.querySelectorAll('.downline-checkbox:checked');
                    return Array.from(checkboxes).map(cb => ({
                        id: cb.value,
                        name: cb.dataset.name,
                        username: cb.dataset.username
                    }));
                }

                return [];
            }

            function generateTransferPreview() {
                const downlines = getSelectedDownlines();
                const mode = document.querySelector('input[name="transferMode"]:checked').value;
                const notes = document.getElementById('bulkTransferNotes').value;

                let transfers = [];

                if (mode === 'sequential') {
                    // All PINs to one downline
                    transfers = selectedPins.map(pin => ({
                        pin_id: pin.id,
                        pin_code: pin.code,
                        downline_id: downlines[0].id,
                        downline_name: downlines[0].name,
                        downline_username: downlines[0].username,
                        notes: notes
                    }));
                } else {
                    // Distribute PINs among downlines
                    for (let i = 0; i < selectedPins.length; i++) {
                        const downline = downlines[i % downlines.length];
                        transfers.push({
                            pin_id: selectedPins[i].id,
                            pin_code: selectedPins[i].code,
                            downline_id: downline.id,
                            downline_name: downline.name,
                            downline_username: downline.username,
                            notes: notes
                        });
                    }
                }

                // Render preview
                const previewHtml = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>PIN</th>
                            <th>Transfer ke</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transfers.map(t => `
                                <tr>
                                    <td><code>${t.pin_code}</code></td>
                                    <td>
                                        <strong>${t.downline_name}</strong><br>
                                        <small class="text-muted">@${t.downline_username}</small>
                                    </td>
                                    <td><small>${t.notes || '-'}</small></td>
                                </tr>
                            `).join('')}
                    </tbody>
                </table>
            </div>
        `;

                document.getElementById('transferPreviewContent').innerHTML = previewHtml;
                document.getElementById('transferPreview').classList.remove('d-none');

                // Store transfers for later use
                window.bulkTransfers = transfers;
            }

            function performBulkTransfer() {
                if (!window.bulkTransfers) {
                    alert('Harap generate preview terlebih dahulu');
                    return;
                }

                const submitBtn = document.getElementById('submitBulkTransfer');
                const progressBar = document.getElementById('bulkProgress');
                const progressBarInner = progressBar.querySelector('.progress-bar');

                // Show progress and disable button
                progressBar.classList.remove('d-none');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';

                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                // Kirim sebagai array, bukan JSON string
                selectedPins.forEach((pin, index) => {
                    formData.append(`pin_ids[${index}]`, pin.id);
                });

                window.bulkTransfers.forEach((transfer, index) => {
                    formData.append(`transfers[${index}][pin_id]`, transfer.pin_id);
                    formData.append(`transfers[${index}][downline_id]`, transfer.downline_id);
                    formData.append(`transfers[${index}][notes]`, transfer.notes || '');
                });

                fetch('{{ route('member.pin.bulk-transfer') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Hide progress and show results
                        progressBar.classList.add('d-none');
                        showBulkResults(data);

                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('bulkTransferPinModal')).hide();

                            // Reload page after showing results
                            setTimeout(() => location.reload(), 3000);
                        }
                    })
                    .catch(error => {
                        progressBar.classList.add('d-none');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-share-alt me-2"></i>Mulai Bulk Transfer';

                        if (typeof toastr !== 'undefined') {
                            toastr.error(error.message || 'Terjadi kesalahan');
                        } else {
                            alert(error.message || 'Terjadi kesalahan');
                        }
                    });
            }

            function showBulkResults(data) {
                const resultHtml = `
            <div class="row text-center mb-3">
                <div class="col-4">
                    <div class="bg-success text-white rounded p-3">
                        <h3>${data.result.success_count}</h3>
                        <small>Berhasil</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-danger text-white rounded p-3">
                        <h3>${data.result.failed_count}</h3>
                        <small>Gagal</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-info text-white rounded p-3">
                        <h3>${data.result.success_count + data.result.failed_count}</h3>
                        <small>Total</small>
                    </div>
                </div>
            </div>
            
            ${data.result.failed_transfers.length > 0 ? `
                    <div class="alert alert-warning">
                        <h6>Transfer Gagal:</h6>
                        <ul class="mb-0">
                            ${data.result.failed_transfers.map(f => `
                            <li>PIN ID ${f.pin_id}: ${f.reason}</li>
                        `).join('')}
                        </ul>
                    </div>
                ` : ''}
            
            <div class="alert alert-success">
                ${data.message}
            </div>
        `;

                document.getElementById('bulkResultContent').innerHTML = resultHtml;
                new bootstrap.Modal(document.getElementById('bulkResultModal')).show();
            }
        });
    </script>

    <style>
        /* Timeline styles for PIN history */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .timeline-title {
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 600;
        }

        .timeline-info {
            margin-bottom: 5px;
            font-size: 13px;
            color: #6c757d;
        }

        /* Enhanced button styles */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }

        .card-header .badge {
            font-size: 0.75rem;
        }

        /* Modal enhancements */
        .modal-header {
            border-bottom: none;
        }

        .modal-body .alert {
            border-left: 4px solid #0066cc;
        }

        #downlinePreview {
            transition: all 0.3s ease;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush
