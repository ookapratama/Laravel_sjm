@extends('layouts.app')

@section('title', 'History POS')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">History POS</h3>
                <p class="mb-2">Riwayat transaksi POS yang telah selesai</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.pos.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                Completed
                            </option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                Cancelled
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('admin.pos.history') }}" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- History Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Riwayat Transaksi</div>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $sessions->total() }} transaksi</span>
                </div>
            </div>
            <div class="card-body">
                @if($sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="12%">PIN Code</th>
                                    <th width="15%">Member</th>
                                    <th width="10%">Admin</th>
                                    <th width="8%">Status</th>
                                    <th width="10%">Budget</th>
                                    <th width="8%">Items</th>
                                    <th width="8%">Total PV</th>
                                    <th width="12%">Selesai</th>
                                    <th width="12%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td>{{ $loop->iteration + ($sessions->currentPage() - 1) * $sessions->perPage() }}</td>
                                        <td>
                                            <code>{{ $session->activationPin->code }}</code>
                                            <br>{!! $session->activationPin->bagan_badge !!}
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $session->member->name ?? 'N/A' }}</strong>
                                                <br><small class="text-muted">{{ $session->member->email ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <small>{{ $session->admin->name ?? 'System' }}</small>
                                        </td>
                                        <td>
                                            {!! $session->status_badge !!}
                                        </td>
                                        <td class="text-end">
                                            <div>
                                                <strong>Rp {{ number_format($session->used_budget, 0, ',', '.') }}</strong>
                                                <br><small class="text-muted">
                                                    dari Rp {{ number_format($session->total_budget, 0, ',', '.') }}
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $session->products_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $session->total_pv }}</span>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $session->completed_at ? $session->completed_at->format('d M Y') : '-' }}
                                                <br><small class="text-muted">
                                                    {{ $session->completed_at ? $session->completed_at->format('H:i') : '' }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="viewSessionDetail({{ $session->id }})">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Menampilkan {{ $sessions->firstItem() }} - {{ $sessions->lastItem() }} 
                                dari {{ $sessions->total() }} transaksi
                            </small>
                        </div>
                        <div>
                            {{ $sessions->appends(request()->query())->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada riwayat transaksi</h5>
                        <p class="text-muted">Transaksi POS yang selesai akan muncul di sini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Session Detail Modal -->
    <div class="modal fade" id="sessionDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi POS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sessionDetailContent">
                    <div class="text-center py-3">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewSessionDetail(sessionId) {
            const modal = new bootstrap.Modal(document.getElementById('sessionDetailModal'));
            modal.show();
            
            // Reset content
            document.getElementById('sessionDetailContent').innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`;
            
            // Fetch session detail
            fetch(`/admin/pos/session/${sessionId}/detail`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySessionDetail(data.session);
                    } else {
                        document.getElementById('sessionDetailContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.message}
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('sessionDetailContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Gagal memuat detail session
                        </div>`;
                });
        }

        function displaySessionDetail(session) {
            let itemsHtml = '';
            session.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.product.name}</td>
                        <td><code>${item.product.sku}</code></td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">Rp ${number_format(item.unit_price, 0, ',', '.')}</td>
                        <td class="text-end">Rp ${number_format(item.total_price, 0, ',', '.')}</td>
                        <td class="text-center">${item.total_pv}</td>
                    </tr>`;
            });

            const html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informasi Session</h6>
                        <table class="table table-sm">
                            <tr><td>PIN Code:</td><td><code>${session.activation_pin.code}</code></td></tr>
                            <tr><td>Member:</td><td>${session.member.name}</td></tr>
                            <tr><td>Email:</td><td>${session.member.email}</td></tr>
                            <tr><td>Bagan:</td><td>${session.activation_pin.bagan_badge}</td></tr>
                            <tr><td>Admin:</td><td>${session.admin ? session.admin.name : 'System'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Transaksi</h6>
                        <table class="table table-sm">
                            <tr><td>Status:</td><td>${session.status_badge}</td></tr>
                            <tr><td>Total Budget:</td><td>Rp ${number_format(session.total_budget, 0, ',', '.')}</td></tr>
                            <tr><td>Budget Terpakai:</td><td>Rp ${number_format(session.used_budget, 0, ',', '.')}</td></tr>
                            <tr><td>Durasi:</td><td>${session.duration}</td></tr>
                            <tr><td>Selesai:</td><td>${session.completed_at}</td></tr>
                        </table>
                    </div>
                </div>

                <h6>Detail Produk</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th>SKU</th>
                                <th>Qty</th>
                                <th>Harga Satuan</th>
                                <th>Total Harga</th>
                                <th>PV</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="text-end">Rp ${number_format(session.used_budget, 0, ',', '.')}</th>
                                <th class="text-center">${session.total_pv}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                ${session.notes ? `
                    <div class="mt-3">
                        <h6>Catatan</h6>
                        <div class="alert alert-info">
                            ${session.notes}
                        </div>
                    </div>` : ''}
            `;

            document.getElementById('sessionDetailContent').innerHTML = html;
        }

        function number_format(number, decimals, dec_point, thousands_sep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    </script>

    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        .modal-lg {
            max-width: 900px;
        }
        
        .table-sm td, .table-sm th {
            padding: 0.3rem;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .row.g-3 > * {
                margin-bottom: 0.5rem;
            }
        }
    </style>
@endsection
{{-- 

        // Update cart summary
        function updateCartSummary() {
            // Update budget display
            document.getElementById('remainingBudget').textContent = 
                'Rp ' + number_format(currentSession.remaining_budget, 0, ',', '.');
            
            // Update progress bar
            const progress = currentSession.total_budget > 0 ? 
                (currentSession.used_budget / currentSession.total_budget) * 100 : 0;
            document.getElementById('budgetProgress').style.width = progress + '%';
            
            // Update cart totals
            const cartTotal = document.getElementById('cartTotal');
            const cartPv = document.getElementById('cartPv');
            const itemCount = document.getElementById('itemCount');
            const productCount = document.getElementById('productCount');
            const completeBtn = document.getElementById('completeBtn');
            
            if (cartTotal) {
                cartTotal.textContent = 'Rp ' + number_format(currentSession.used_budget, 0, ',', '.');
            }
            
            if (cartPv) {
                cartPv.textContent = currentSession.total_pv + ' PV';
            }
            
            itemCount.textContent = currentSession.products_count + ' item';
            
            if (productCount && currentSession.max_products) {
                productCount.textContent = currentSession.products_count + '/' + currentSession.max_products;
            }
            
            // Enable/disable complete button
            completeBtn.disabled = currentSession.products_count == 0;
        }

        // Complete session
        function completeSession() {
            if (currentSession.products_count == 0) {
                Swal.fire('Error!', 'Minimal harus ada 1 produk untuk menyelesaikan session', 'error');
                return;
            }

            Swal.fire({
                title: 'Selesaikan Session?',
                text: 'Session akan diselesaikan dan member akan mendapat produk',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan',
                cancelButtonText: 'Batal',
                input: 'textarea',
                inputPlaceholder: 'Catatan (opsional)...'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('admin.pos.complete-session', $session->id) }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            notes: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', data.message, 'success');
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Terjadi kesalahan sistem', 'error');
                    });
                }
            });
        }--}}