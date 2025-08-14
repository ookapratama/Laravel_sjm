@extends('layouts.app')

@section('content')
    <div class="page-inner">
        <h4>Daftar Withdraw Menunggu Pencairan</h4>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>User</th>
                    <th>No. Rekening</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Detail Pembayaran</th>
                    <th>Catatan Admin</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($withdraws as $w)
                    <tr id="row-{{ $w->id }}">
                        <td>
                            <strong>{{ $w->user->name }}</strong><br>
                            <small class="text-muted">{{ $w->user->email }}</small><br>
                            <small class="text-info">Member ID: {{ $w->user->id ?? '-' }}</small>
                        </td>
                        <td>
                            <strong>{{ $w->user->mitra->nomor_rekening ?? 'Tidak ada rekening' }}</strong><br>
                            <span class="text-muted">{{ $w->user->mitra->nama_bank ?? 'Bank tidak diketahui' }}</span><br>
                            <small>a.n {{ $w->user->mitra->nama_rekening ?? 'Nama tidak diketahui' }}</small>
                        </td>
                        <td>
                            <strong class="text-primary">Rp {{ number_format($w->amount, 0, ',', '.') }}</strong><br>
                            <small class="text-muted">Admin: Rp {{ number_format($w->tax ?? 0, 0, ',', '.') }}</small><br>
                            <small class="text-success">Net: Rp
                                {{ number_format($w->amount - $w->tax, 0, ',', '.') }}</small>
                        </td>
                        <td>{{ $w->type ?? 'Bonus' }}</td>
                        <td>{{ $w->transfer_reference ?? '-' }}</td>
                        <td>{{ $w->admin_notes ?? '-' }}</td>
                        <td>
                            <div class="btn-group-vertical" role="group">
                                <button class="btn btn-success btn-sm approve-btn" data-id="{{ $w->id }}"
                                    data-user="{{ $w->user->name }}"
                                    data-amount="{{ number_format($w->amount-$w->tax, 0, ',', '.') }}">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-danger btn-sm reject-btn" data-id="{{ $w->id }}"
                                    data-user="{{ $w->user->name }}"
                                    data-amount="{{ number_format($w->amount-$w->tax, 0, ',', '.') }}">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                {{-- <button class="btn btn-info btn-sm detail-btn" data-id="{{ $w->id }}">
                                    <i class="fas fa-eye"></i> Detail
                                </button> --}}
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if ($withdraws->isEmpty())
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i><br>
                            Tidak ada withdrawal yang menunggu pencairan
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Modal Detail Withdrawal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Detail Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Approve Button Handler
            document.querySelectorAll('.approve-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const userName = btn.dataset.user;
                    const amount = btn.dataset.amount;

                    Swal.fire({
                        title: 'Approve Withdrawal',
                        html: `
                            <div class="text-left">
                                <p><strong>User:</strong> ${userName}</p>
                                <p><strong>Jumlah:</strong> Rp ${amount}</p>
                                <hr>
                                <label for="transfer_reference" class="form-label">Nomor Referensi Transfer:</label>
                                <input type="text" id="transfer_reference" class="swal2-input" placeholder="Misal: TRF12345678">
                                
                                <label for="admin_notes" class="form-label">Catatan Admin (opsional):</label>
                                <textarea id="admin_notes" class="swal2-textarea" placeholder="Catatan untuk member..."></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check"></i> Approve & Transfer',
                        confirmButtonColor: '#28a745',
                        cancelButtonText: 'Batal',
                        focusConfirm: false,
                        preConfirm: () => {
                            const transferRef = document.getElementById(
                                'transfer_reference').value;
                            const adminNotes = document.getElementById('admin_notes')
                                .value;



                            return {
                                transfer_reference: transferRef,
                                admin_notes: adminNotes
                            };
                        }
                    }).then(result => {
                        if (result.isConfirmed) {
                            processWithdrawal(id, 'approve', result.value);
                        }
                    });
                });
            });

            // Reject Button Handler
            document.querySelectorAll('.reject-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const userName = btn.dataset.user;
                    const amount = btn.dataset.amount;

                    Swal.fire({
                        title: 'Reject Withdrawal',
                        html: `
                            <div class="text-left">
                                <p><strong>User:</strong> ${userName}</p>
                                <p><strong>Jumlah:</strong> Rp ${amount}</p>
                                <hr>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Withdrawal akan ditolak dan saldo akan dikembalikan ke user.
                                </div>
                                <label for="reject_reason" class="form-label">Alasan Penolakan:</label>
                                <textarea id="reject_reason" class="swal2-textarea" placeholder="Jelaskan alasan penolakan withdrawal ini..." required></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-times"></i> Reject Withdrawal',
                        confirmButtonColor: '#dc3545',
                        cancelButtonText: 'Batal',
                        focusConfirm: false,
                        preConfirm: () => {
                            const rejectReason = document.getElementById(
                                'reject_reason').value;

                            if (!rejectReason || rejectReason.trim().length < 10) {
                                Swal.showValidationMessage(
                                    'Alasan penolakan harus diisi minimal 10 karakter!'
                                    );
                                return false;
                            }

                            return {
                                admin_notes: rejectReason
                            };
                        }
                    }).then(result => {
                        if (result.isConfirmed) {
                            processWithdrawal(id, 'reject', result.value);
                        }
                    });
                });
            });

            // Detail Button Handler
            document.querySelectorAll('.detail-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    loadWithdrawalDetail(id);
                });
            });

            // Function to process withdrawal (approve/reject)
            function processWithdrawal(id, action, data) {
                const loadingText = action === 'approve' ? 'Memproses approval...' : 'Memproses penolakan...';

                Swal.fire({
                    title: loadingText,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/finance/withdraws/${id}/process`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: action,
                            ...data
                        })
                    })
                    .then(async res => {
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const responseData = isJson ? await res.json() : await res.text();

                        if (!res.ok) {
                            throw new Error(isJson ? (responseData.message ?? 'Gagal memproses') :
                                'Respon bukan JSON:\n' + responseData);
                        }

                        const successTitle = action === 'approve' ? 'Withdrawal Disetujui!' :
                            'Withdrawal Ditolak!';
                        const successIcon = action === 'approve' ? 'success' : 'info';

                        Swal.fire({
                            title: successTitle,
                            text: responseData.message,
                            icon: successIcon,
                            confirmButtonText: 'OK'
                        });

                        // Remove row from table
                        document.getElementById(`row-${id}`).remove();

                        // Check if table is empty
                        const tbody = document.querySelector('tbody');
                        if (tbody.children.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        Tidak ada withdrawal yang menunggu pencairan
                                    </td>
                                </tr>
                            `;
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire({
                            title: 'Error!',
                            text: err.message || 'Terjadi kesalahan saat memproses withdrawal',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }

            
        });
    </script>

    <style>
        .btn-group-vertical .btn {
            margin-bottom: 2px;
        }

        .swal2-html-container {
            text-align: left !important;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75em;
        }

        .modal-lg {
            max-width: 800px;
        }

        .card {
            margin-bottom: 10px;
        }

        .card h4,
        .card h5 {
            margin: 0;
        }
    </style>
@endsection
