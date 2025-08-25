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
                    <div class="text-muted">Total: Rp{{ number_format($totalRupiah, 0, ',', '.') }}</div>
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
                                <td>Rp{{ number_format($r->total_price, 0, ',', '.') }}</td>
                                <td class="text-uppercase">{{ $r->payment_method ?: '-' }}</td>
                                <td class="small">{{ $r->payment_reference ?: '-' }}</td>
                                <td>
                                    @if (!empty($r->payment_proof))
                                        @php $url = asset('storage/'.$r->payment_proof); @endphp
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#proofModal"
                                            data-title="Bukti #{{ $r->id }}" data-url="{{ $url }}">
                                            Lihat
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="badge bg-{{ [
                                            'requested' => 'secondary',
                                            'finance_approved' => 'info',
                                            'finance_rejected' => 'danger',
                                            'generated' => 'success',
                                        ][$r->status] ?? 'secondary' }}">
                                        {{ strtoupper(str_replace('_', ' ', $r->status)) }}
                                    </span>
                                    @if ($r->finance_notes)
                                        <div class="small text-muted mt-1">{{ $r->finance_notes }}</div>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @if ($r->status === 'requested')
                                        {{-- ✅ APPROVE FORM - Fixed data-id --}}
                                        <form method="POST" action="{{ route('finance.pin.approve', $r->id) }}"
                                            class="d-inline approve-form" data-id="{{ $r->id }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="payment_method"
                                                value="{{ $r->payment_method ?: 'transfer' }}" />
                                            <input type="hidden" name="payment_reference"
                                                value="{{ $r->payment_reference ?: 'REF-' . $r->id . '-' . now()->format('YmdHis') }}" />
                                            <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                        </form>

                                        {{-- ✅ REJECT BUTTON - Pastikan data-id ada --}}
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal" data-id="{{ $r->id }}"
                                            data-requester="{{ $r->requester->name }}">
                                            Reject
                                        </button>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted">Belum ada permintaan.</td>
                            </tr>
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

    {{-- ✅ MODAL REJECT - Tambahkan action placeholder --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" id="rejectForm" action="">
                @csrf @method('PUT')
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Tolak Permintaan</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="requester-info" class="mb-3" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-user me-2"></i>
                            <strong>Requester:</strong> <span id="requester-name"></span>
                        </div>
                    </div>
                    <label class="form-label">Alasan penolakan</label>
                    <textarea name="finance_notes" class="form-control" rows="3" required
                        placeholder="Masukkan alasan penolakan yang jelas..."></textarea>
                    <small class="text-muted">Alasan ini akan dikirim ke requester</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Kirim Penolakan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ✅ PROOF MODAL - Preview bukti pembayaran
            const proofModal = document.getElementById('proofModal');
            if (proofModal) {
                proofModal.addEventListener('show.bs.modal', function(e) {
                    const btn = e.relatedTarget;
                    const url = btn.getAttribute('data-url');
                    const title = btn.getAttribute('data-title') || 'Bukti';

                    document.getElementById('proofTitle').textContent = title;
                    const body = document.getElementById('proofBody');
                    body.innerHTML = '';

                    if (url.endsWith('.pdf')) {
                        body.innerHTML =
                            `<iframe src="${url}" style="width:100%;height:70vh;border:0;"></iframe>`;
                    } else {
                        body.innerHTML =
                            `<img src="${url}" style="max-width:100%;max-height:70vh;border-radius:8px;">`;
                    }
                });
            }

            // ✅ REJECT MODAL - Set form action dan info
            const rejectModal = document.getElementById('rejectModal');
            if (rejectModal) {
                rejectModal.addEventListener('show.bs.modal', function(e) {
                    const button = e.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const requesterName = button.getAttribute('data-requester');
                    const form = document.getElementById('rejectForm');

                    console.group('🔍 Reject Modal Debug');
                    console.log('Button clicked:', button);
                    console.log('PIN Request ID:', id);
                    console.log('Requester name:', requesterName);
                    console.log('Form element:', form);
                    console.groupEnd();

                    if (!form) {
                        console.error('❌ Form #rejectForm not found!');
                        return;
                    }

                    if (!id) {
                        console.error('❌ No data-id found on button!');
                        Swal.fire('Error!', 'ID tidak ditemukan. Refresh halaman dan coba lagi.', 'error');
                        return;
                    }

                    // ✅ Set form action menggunakan route helper
                    const routeTemplate = "{{ route('finance.pin.reject', ':id') }}";
                    form.action = routeTemplate.replace(':id', id);

                    console.log('✅ Form action set to:', form.action);

                    // ✅ Set requester info jika ada
                    if (requesterName) {
                        document.getElementById('requester-name').textContent = requesterName;
                        document.getElementById('requester-info').style.display = 'block';
                    } else {
                        document.getElementById('requester-info').style.display = 'none';
                    }

                    // ✅ Clear textarea
                    const textarea = form.querySelector('textarea[name="finance_notes"]');
                    if (textarea) {
                        textarea.value = '';
                        textarea.focus();
                    }
                });
            }

            // ✅ APPROVE FORM - Fixed event handler
            document.querySelectorAll('.approve-form').forEach((form) => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default submit

                    // ✅ Get ID from form data attribute
                    const requestId = this.getAttribute('data-id');

                    console.log('Approve request ID:', requestId);

                    if (!requestId) {
                        console.error('❌ No request ID found');
                        Swal.fire('Error!', 'ID permintaan tidak ditemukan.', 'error');
                        return;
                    }

                    Swal.fire({
                        title: 'Approve Request #' + requestId + '?',
                        text: 'Anda yakin ingin menyetujui permintaan ini?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        confirmButtonText: '<i class="fas fa-check me-1"></i>Ya, Setujui!',
                        cancelButtonText: '<i class="fas fa-times me-1"></i>Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Sedang memproses persetujuan',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                willOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            this.submit(); // Submit form
                        }
                    });
                });
            });

            // ✅ REJECT FORM - Add submit handler with confirmation
            const rejectForm = document.getElementById('rejectForm');
            if (rejectForm) {
                rejectForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const textarea = this.querySelector('textarea[name="finance_notes"]');
                    const reason = textarea.value.trim();

                    if (reason.length < 10) {
                        Swal.fire('Perhatian!', 'Alasan penolakan minimal 10 karakter.', 'warning');
                        textarea.focus();
                        return;
                    }

                    Swal.fire({
                        title: 'Tolak Permintaan?',
                        text: 'Anda yakin ingin menolak permintaan ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-times me-1"></i>Ya, Tolak!',
                        cancelButtonText: '<i class="fas fa-arrow-left me-1"></i>Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Sedang memproses penolakan',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                willOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            this.submit(); // Submit form
                        }
                    });
                });
            }
        });

        // ✅ SHOW SUCCESS/ERROR MESSAGES
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}',
                confirmButtonColor: '#dc3545'
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal!',
                html: '<ul class="text-start">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                confirmButtonColor: '#dc3545'
            });
        @endif
    </script>
@endpush
