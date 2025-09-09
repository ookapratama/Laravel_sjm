@extends('layouts.app')

@section('title', 'History Produk')

@section('content')
    <div class="page-inner">
        <!-- Header -->
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">History Produk</h3>
                <p class="mb-2">Riwayat barang keluar dari gudang</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.stock.index') }}" class="btn btn-primary me-2">
                    <i class="fas fa-arrow-left"></i> Kembali ke Produk
                </a>
                {{-- <button class="btn btn-success" onclick="exportData()">
                    <i class="fas fa-download"></i> Export Excel
                </button> --}}
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    {{-- <i class="far fa-calendar-day"></i> --}}
                                    <i class="fas fa-cart-arrow-down"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Hari Ini</p>
                                    <h4 class="card-title" id="todayCount">
                                        {{ $outgoings->where('created_at', '>=', today())->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    {{-- <i class="fas fa-retweet"></i>  --}}
                                    <i class="fas fa-cart-arrow-down"></i>
                                    {{-- <i class="fas fa-calendar-week"></i> --}}
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Minggu Ini</p>
                                    <h4 class="card-title">
                                        {{ $outgoings->where('created_at', '>=', now()->startOfWeek())->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Bulan Ini</p>
                                    <h4 class="card-title">
                                        {{ $outgoings->where('created_at', '>=', now()->startOfMonth())->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Nilai</p>
                                    <h4 class="card-title">Rp
                                        {{ number_format($outgoings->sum('total_price'), 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="card-title">Filter Data</div>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"
                            id="startDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"
                            id="endDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Produk</label>
                        <select name="product_id" class="form-control" id="productFilter">
                            <option value="">Semua Produk</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- <div class="col-md-2">
                        <label class="form-label">Referensi</label>
                        <input type="text" name="reference" class="form-control" 
                               placeholder="PIN, Kode..." value="{{ request('reference') }}" id="referenceFilter">
                    </div> --}}
                    <div class="col-md-2">
                        <label class="form-label">Admin</label>
                        <select name="admin_id" class="form-control" id="adminFilter">
                            <option value="">Semua Admin</option>
                            @foreach ($outgoings->pluck('createdBy')->unique()->filter() as $admin)
                                <option value="{{ $admin->id }}"
                                    {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Riwayat Transaksi</div>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $outgoings->total() }} record</span>
                    <div class="btn-group ms-2">
                        <button class="btn btn-sm btn-outline-primary active" onclick="toggleView('table')"
                            id="tableViewBtn">
                            <i class="fas fa-table"></i> Table
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleView('card')" id="cardViewBtn">
                            <i class="fas fa-th-large"></i> Card
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if ($outgoings->count() > 0)
                    <!-- Table View -->
                    <div id="tableView">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="12%">Tanggal/Waktu</th>
                                        <th width="25%">Produk</th>
                                        <th width="8%">Qty</th>
                                        <th width="12%">Harga Satuan</th>
                                        <th width="12%">Total Harga</th>
                                        <th width="8%">PV</th>
                                        {{-- <th width="10%">Referensi</th> --}}
                                        <th width="8%">Created By</th>
                                        <th width="8%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($outgoings as $outgoing)
                                        <tr>
                                            <td>{{ $loop->iteration + ($outgoings->currentPage() - 1) * $outgoings->perPage() }}
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $outgoing->created_at->format('d M Y') }}</strong>
                                                    <br><small
                                                        class="text-muted">{{ $outgoing->created_at->format('H:i:s') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $outgoing->product->name }}</strong>
                                                    <br><small class="text-muted">SKU:
                                                        {{ $outgoing->product->sku }}</small>
                                                    @if ($outgoing->notes)
                                                        <br><small
                                                            class="text-info">{{ Str::limit($outgoing->notes, 30) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary fs-6">{{ $outgoing->quantity }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-muted">Rp
                                                    {{ number_format($outgoing->unit_price, 0, ',', '.') }}</span>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">Rp
                                                    {{ number_format($outgoing->total_price, 0, ',', '.') }}</strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info">{{ $outgoing->total_pv }}</span>
                                            </td>
                                            <td class="text-center">
                                                {{ $outgoing->createdBy->name }}
                                            </td>
                                            {{-- <td>
                                                @if ($outgoing->reference_code)
                                                    <code class="bg-light">{{ $outgoing->reference_code }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td> --}}
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-info"
                                                        onclick="viewDetail({{ $outgoing->id }})" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if ($outgoing->created_at->isToday())
                                                        <button class="btn btn-sm btn-warning"
                                                            onclick="rollbackTransaction({{ $outgoing->id }})"
                                                            title="Refund Stock">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="5" class="text-end">Total Halaman Ini:</th>
                                        <th class="text-end">Rp
                                            {{ number_format($outgoings->sum('total_price'), 0, ',', '.') }}</th>
                                        <th class="text-center">{{ $outgoings->sum('total_pv') }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Card View -->
                    <div id="cardView" style="display: none;">
                        <div class="row">
                            @foreach ($outgoings as $outgoing)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-left-primary">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="card-title mb-1">{{ $outgoing->product->name }}</h6>
                                                    <small class="text-muted">{{ $outgoing->product->sku }}</small>
                                                </div>
                                                <span class="badge badge-primary">{{ $outgoing->quantity }}x</span>
                                            </div>

                                            <div class="row text-center mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">Total Harga</small>
                                                    <div class="fw-bold text-success">Rp
                                                        {{ number_format($outgoing->total_price, 0, ',', '.') }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Total PV</small>
                                                    <div class="fw-bold text-info">{{ $outgoing->total_pv }}</div>
                                                </div>
                                            </div>

                                            @if ($outgoing->reference_code)
                                                <div class="mb-2">
                                                    <small class="text-muted">Referensi:</small>
                                                    <code class="bg-light">{{ $outgoing->reference_code }}</code>
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-between align-items-center">
                                                <small
                                                    class="text-muted">{{ $outgoing->created_at->format('d M Y H:i') }}</small>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-info"
                                                        onclick="viewDetail({{ $outgoing->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if ($outgoing->created_at->isToday())
                                                        <button class="btn btn-sm btn-warning"
                                                            onclick="rollbackTransaction({{ $outgoing->id }})">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <small class="text-muted">
                                Menampilkan {{ $outgoings->firstItem() }} - {{ $outgoings->lastItem() }}
                                dari {{ $outgoings->total() }} record
                            </small>
                        </div>
                        <div>
                            {{ $outgoings->appends(request()->query())->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data ditemukan</h5>
                        <p class="text-muted">Coba ubah filter atau periode waktu</p>
                        <button class="btn btn-primary" onclick="clearFilters()">
                            <i class="fas fa-eraser"></i> Clear Filter
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
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
        // Toggle view between table and card
        function toggleView(viewType) {
            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');
            const tableBtn = document.getElementById('tableViewBtn');
            const cardBtn = document.getElementById('cardViewBtn');

            if (viewType === 'table') {
                tableView.style.display = 'block';
                cardView.style.display = 'none';
                tableBtn.classList.add('active');
                cardBtn.classList.remove('active');
            } else {
                tableView.style.display = 'none';
                cardView.style.display = 'block';
                cardBtn.classList.add('active');
                tableBtn.classList.remove('active');
            }

            // Save preference
            localStorage.setItem('inventory_view_preference', viewType);
        }

        // Load saved view preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('inventory_view_preference') || 'table';
            toggleView(savedView);
        });

        // Clear filters
        function clearFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = '{{ route('admin.stock.history') }}';
        }

        // View detail
        function viewDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();

            // Reset content
            document.getElementById('detailContent').innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`;

            // Fetch detail data
            fetch(`{{ route('admin.stock.detail', '') }}/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDetail(data.outgoing);
                    } else {
                        document.getElementById('detailContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.message}
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('detailContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Gagal memuat detail
                        </div>`;
                });
        }

        // Display detail in modal
        function displayDetail(outgoing) {
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Produk</h6>
                        <table class="table table-sm">
                            <tr><td>Nama:</td><td><strong>${outgoing.product.name}</strong></td></tr>
                            <tr><td>SKU:</td><td><code>${outgoing.product.sku}</code></td></tr>
                            <tr><td>Kategori:</td><td>${outgoing.product.category || '-'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Transaksi</h6>
                        <table class="table table-sm">
                            <tr><td>Tanggal:</td><td>${outgoing.created_at}</td></tr>
                            <tr><td>Admin:</td><td>${outgoing.created_by ? outgoing.created_by.name : 'System'}</td></tr>
                        </table>
                    </div>
                </div>

                <h6>Detail Item</h6>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Quantity</th>
                            <th>Harga Satuan</th>
                            <th>Total Harga</th>
                            <th>PV Satuan</th>
                            <th>Total PV</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center"><span class="badge badge-primary">${outgoing.quantity}</span></td>
                            <td class="text-end">Rp ${number_format(outgoing.unit_price, 0, ',', '.')}</td>
                            <td class="text-end"><strong>Rp ${number_format(outgoing.total_price, 0, ',', '.')}</strong></td>
                            <td class="text-center">${outgoing.unit_pv}</td>
                            <td class="text-center"><strong>${outgoing.total_pv}</strong></td>
                        </tr>
                    </tbody>
                </table>

                ${outgoing.notes ? `
                            <h6>Catatan</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-sticky-note"></i> ${outgoing.notes}
                            </div>` : ''}
            `;

            document.getElementById('detailContent').innerHTML = html;
        }

        // Rollback transaction
        function rollbackTransaction(id) {
            Swal.fire({
                title: 'Refund Stock?',
                text: 'Stok produk akan dikembalikan dan record akan dihapus',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Refund',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#f39c12'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('admin.stock.destroy', '') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    location.reload();
                                });
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
        }

        // Export data
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            const exportUrl = '{{ route('admin.stock.export') }}?' + params.toString();
            window.open(exportUrl, '_blank');
        }

        // Utility function
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

        // Auto-refresh every 5 minutes
        setInterval(() => {
            const todayElement = document.getElementById('todayCount');
            if (todayElement) {
                fetch('{{ route('admin.stock.stats') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            todayElement.textContent = data.stats.today_count;
                        }
                    })
                    .catch(error => console.log('Stats refresh failed:', error));
            }
        }, 300000); // 5 minutes
    </script>

    <style>
        .border-left-primary {
            border-left: 4px solid #007bff !important;
        }

        .card-stats {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .icon-big {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .bubble-shadow-small {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn.active {
            background-color: #007bff;
            color: white;
        }

        .fs-6 {
            font-size: 0.875rem;
        }

        .bg-light {
            background-color: #f8f9fa !important;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .col-md-6,
            .col-lg-4 {
                margin-bottom: 1rem;
            }

            .card-body {
                padding: 0.75rem;
            }
        }
    </style>
@endsection
