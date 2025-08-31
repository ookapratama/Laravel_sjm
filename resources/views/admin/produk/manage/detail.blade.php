@extends('layouts.app')

@section('title', 'Detail Paket: ' . $package->name)

@section('content')
<div class="page-inner">
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Kelola Paket</a></li>
                    <li class="breadcrumb-item active">{{ $package->name }}</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-3">Detail Paket: {{ $package->name }}</h3>
            <p class="mb-2">Informasi lengkap tentang paket dan produk di dalamnya</p>
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit"></i> Edit Paket
            </a>
            <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Package Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-{{ $package->is_active ? 'success' : 'secondary' }} text-white">
                    <div class="card-title mb-0">
                        <i class="fas fa-box-open"></i> Informasi Paket
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold text-muted">Nama Paket</label>
                        <div class="h5">{{ $package->name }}</div>
                    </div>

                    @if ($package->description)
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Deskripsi</label>
                            <div>{{ $package->description }}</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Status</label>
                        <div>
                            <span class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }} badge-lg">
                                {{ $package->is_active ? 'Aktif' : 'Non-aktif' }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Tanggal Dibuat</label>
                        <div>{{ $package->created_at->format('d M Y, H:i') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold text-muted">Terakhir Diupdate</label>
                        <div>{{ $package->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Package Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <div class="card-title">Statistik Paket</div>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-4">
                            <div class="h3 mb-1 text-primary">{{ $package->packageProducts->count() }}</div>
                            <div class="text-muted small">Jenis Produk</div>
                        </div>
                        <div class="col-4">
                            <div class="h3 mb-1 text-info">{{ $package->total_items }}</div>
                            <div class="text-muted small">Total Item</div>
                        </div>
                        <div class="col-4">
                            <div class="h3 mb-1 text-success">{{ $package->packageProducts->sum('quantity') }}</div>
                            <div class="text-muted small">Quantity</div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Selisih:</span>
                            <span class="fw-bold {{ $package->total_value > $package->max_value ? 'text-danger' : ($package->total_value < $package->max_value ? 'text-warning' : 'text-success') }}">
                                @if ($package->total_value > $package->max_value)
                                    +Rp {{ number_format($package->total_value - $package->max_value, 0, ',', '.') }}
                                @elseif($package->total_value < $package->max_value)
                                    -Rp {{ number_format($package->max_value - $package->total_value, 0, ',', '.') }}
                                @else
                                    Pas!
                                @endif
                            </span>
                        </div>

                        {{-- <div class="justify-content-between align-items-center mb-2">
                            <span>Nilai Maksimal:</span>
                            <span class="fw-bold">Rp {{ number_format($package->max_value, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Total Nilai:</span>
                            <span class="h5 mb-0 text-success">Rp {{ number_format($package->total_value, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex --}}

                        @php
                            $percentage = ($package->total_value / $package->max_value) * 100;
                            $progressColor = $percentage > 100 ? 'danger' : ($percentage > 90 ? 'warning' : 'success');
                        @endphp
                        
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-{{ $progressColor }}" 
                                 style="width: {{ min($percentage, 100) }}%"></div>
                        </div>
                        <div class="text-center">
                            <small class="text-{{ $progressColor }}">{{ number_format($percentage, 1) }}% dari batas</small>
                        </div>
                    </div>

                    @if ($package->total_value > $package->max_value)
                        <div class="alert alert-warning alert-sm">
                            <small><i class="fas fa-exclamation-triangle"></i> Nilai paket melebihi batas maksimal</small>
                        </div>
                    @elseif($package->total_value < $package->max_value)
                        <div class="alert alert-info alert-sm">
                            <small><i class="fas fa-info-circle"></i> Masih bisa tambah Rp {{ number_format($package->max_value - $package->total_value, 0, ',', '.') }}</small>
                        </div>
                    @else
                        <div class="alert alert-success alert-sm">
                            <small><i class="fas fa-check-circle"></i> Nilai paket pas dengan batas maksimal!</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <div class="card-title">Aksi</div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Paket
                        </a>
                        
                        <form action="{{ route('admin.packages.toggle-status', $package) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-{{ $package->is_active ? 'secondary' : 'success' }} w-100">
                                @if ($package->is_active)
                                    <i class="fas fa-pause"></i> Nonaktifkan
                                @else
                                    <i class="fas fa-play"></i> Aktifkan
                                @endif
                            </button>
                        </form>

                        <hr>

                        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" 
                              onsubmit="return confirm('Yakin ingin menghapus paket ini? Data tidak dapat dipulihkan!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> Hapus Paket
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Products -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="card-title mb-0">
                            Produk dalam Paket ({{ $package->packageProducts->count() }} produk)
                        </div>
                        <div class="badge badge-lg badge-primary">
                            Total: Rp {{ number_format($package->total_value, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($package->packageProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Produk</th>
                                        <th width="12%" class="text-center">Jumlah</th>
                                        <th width="18%" class="text-end">Harga Satuan</th>
                                        <th width="18%" class="text-end">Subtotal</th>
                                        <th width="7%" class="text-center">PV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($package->packageProducts as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3" style="background-color: #f1f2f6;">
                                                        <i class="fas fa-box text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $item->product->name }}</div>
                                                        <small class="text-muted">
                                                            SKU: {{ $item->product->sku }}
                                                        </small><br>
                                                        <small class="text-success">
                                                            <i class="fas fa-boxes"></i> Stok: {{ $item->product->stock }}
                                                        </small>
                                                        <small class="text-{{ $item->product->is_active ? 'success' : 'danger' }} ms-2">
                                                            <i class="fas fa-circle"></i>
                                                            {{ $item->product->is_active ? 'Aktif' : 'Non-aktif' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-outline-primary badge-lg">
                                                    {{ $item->quantity }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold">
                                                    Rp {{ number_format($item->product->price, 0, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold text-success">
                                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted">
                                                    {{ $item->product->pv * $item->quantity }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-end fw-bold">
                                            {{ $package->packageProducts->count() }} jenis
                                        </td>
                                        <td class="text-end fw-bold">
                                            <span class="text-success fs-5">
                                                Rp {{ number_format($package->total_value, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            {{ $package->packageProducts->sum(function($item) { return $item->product->pv * $item->quantity; }) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <div class="h4 text-primary mb-1">{{ $package->packageProducts->count() }}</div>
                                        <div class="text-muted small">Jenis Produk</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <div class="h4 text-info mb-1">{{ $package->packageProducts->sum('quantity') }}</div>
                                        <div class="text-muted small">Total Item</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <div class="h4 text-success mb-1">
                                            {{ $package->packageProducts->sum(function($item) { return $item->product->pv * $item->quantity; }) }}
                                        </div>
                                        <div class="text-muted small">Total PV</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <div class="h4 text-warning mb-1">
                                            Rp {{ number_format($package->packageProducts->avg(function($item) { return $item->product->price; }), 0, ',', '.') }}
                                        </div>
                                        <div class="text-muted small">Harga Rata-rata</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">Paket Kosong</h4>
                            <p class="text-muted mb-4">Belum ada produk dalam paket ini.</p>
                            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Produk
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-lg {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.badge-outline-primary {
    background-color: transparent;
    border: 1px solid #007bff;
    color: #007bff;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0;
    font-size: 0.875rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
    border-color: #dee2e6;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.card.bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef;
}

.card.bg-light .card-body {
    padding: 1rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.75rem;
    }
    
    .avatar {
        width: 30px;
        height: 30px;
    }
    
    .row.mt-4 .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .badge-lg {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Custom scrollbar */
.card-body::-webkit-scrollbar {
    width: 6px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

.fs-5 {
    font-size: 1.25rem;
}

/* Hover effects for action buttons */
.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Table row hover */
.table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
@endsection 