@extends('layouts.app')

@section('title', 'Kelola Paket')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Kelola Paket</h3>
                <p class="mb-2">Atur paket-paket produk untuk sistem membership</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-box"></i> Kelola Produk
                </a>
                <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Paket Baru
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-check-circle"></i></strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-circle"></i></strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="card-title mb-0">Daftar Paket ({{ $packages->count() }} paket)</div>
                            <div class="card-tools">
                                <input type="text" id="searchPackages" class="form-control" placeholder="Cari paket..."
                                    style="width: 250px;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($packages->count() > 0)
                            <div class="row" id="packagesContainer">
                                @foreach ($packages as $package)
                                    <div class="col-md-6 col-lg-4 mb-4 package-card"
                                        data-name="{{ strtolower($package->name) }}"
                                        data-description="{{ strtolower($package->description ?? '') }}">
                                        <div
                                            class="card h-100 {{ $package->is_active ? 'border-success' : 'border-secondary' }}">
                                            <div
                                                class="card-header bg-{{ $package->is_active ? 'success' : 'secondary' }} text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="card-title mb-0">
                                                        <i class="fas fa-box-open"></i> {{ $package->name }}
                                                    </h6>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-light" type="button"
                                                            data-bs-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.packages.show', $package) }}">
                                                                    <i class="fas fa-eye text-info"></i> Detail
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.packages.edit', $package) }}">
                                                                    <i class="fas fa-edit text-warning"></i> Edit
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <form
                                                                    action="{{ route('admin.packages.toggle-status', $package) }}"
                                                                    method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="dropdown-item">
                                                                        @if ($package->is_active)
                                                                            <i class="fas fa-pause text-warning"></i>
                                                                            Nonaktifkan
                                                                        @else
                                                                            <i class="fas fa-play text-success"></i>
                                                                            Aktifkan
                                                                        @endif
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <form
                                                                    action="{{ route('admin.packages.destroy', $package) }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('Yakin ingin menghapus paket ini? Data tidak dapat dipulihkan!')"
                                                                    class="d-inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash"></i> Hapus
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                @if ($package->description)
                                                    <p class="text-muted mb-3">{{ $package->description }}</p>
                                                @endif

                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <div class="text-center">
                                                            <div class="h4 mb-1 text-primary">
                                                                {{ $package->package_products_count }}</div>
                                                            <div class="text-muted small">Jenis Produk</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-center">
                                                            <div class="h4 mb-1 text-info">{{ $package->total_items }}
                                                            </div>
                                                            <div class="text-muted small">Total Item</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="border rounded p-3 mb-3 bg-light">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="fw-bold">Total Nilai:</span>
                                                        <span class="h5 mb-0 text-success">
                                                            Rp {{ number_format($package->calculated_total, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">Batas Maksimal:</span>
                                                        <span class="small">
                                                            Rp {{ number_format($package->max_value, 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    @php
                                                        $percentage =
                                                            ($package->calculated_total / $package->max_value) * 100;
                                                        $progressColor =
                                                            $percentage > 90
                                                                ? 'danger'
                                                                : ($percentage > 75
                                                                    ? 'warning'
                                                                    : 'success');
                                                    @endphp

                                                    <div class="progress mt-2" style="height: 6px;">
                                                        <div class="progress-bar bg-{{ $progressColor }}"
                                                            style="width: {{ min($percentage, 100) }}%"></div>
                                                    </div>
                                                    <div class="text-center mt-1">
                                                        <small
                                                            class="text-{{ $progressColor }}">{{ number_format($percentage, 1) }}%
                                                            dari batas</small>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span
                                                        class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">
                                                        {{ $package->is_active ? 'Aktif' : 'Non-aktif' }}
                                                    </span>
                                                    <small class="text-muted">
                                                        {{ $package->created_at->format('d/m/Y H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="btn-group w-100" role="group">
                                                    <a href="{{ route('admin.packages.show', $package) }}"
                                                        class="btn btn-outline-info btn-sm">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                    <a href="{{ route('admin.packages.edit', $package) }}"
                                                        class="btn btn-outline-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="noResultsFound" class="text-center py-5" style="display: none;">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada paket yang ditemukan</h5>
                                <p class="text-muted">Coba ubah kata kunci pencarian Anda</p>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                                <h4 class="text-muted">Belum Ada Paket</h4>
                                <p class="text-muted mb-4">Mulai dengan membuat paket pertama Anda!</p>
                                <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Buat Paket Pertama
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchPackages').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const packageCards = document.querySelectorAll('.package-card');
            const container = document.getElementById('packagesContainer');
            const noResults = document.getElementById('noResultsFound');
            let visibleCount = 0;

            packageCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const description = card.getAttribute('data-description');

                if (name.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0 && searchTerm !== '') {
                container.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                container.style.display = '';
                noResults.style.display = 'none';
            }
        });

        // Auto dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                }
            });
        }, 5000);
    </script>

    <style>
        .card-header {
            border-bottom: none;
        }

        .package-card .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 2px solid;
        }

        .package-card .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .progress {
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .badge {
            font-size: 0.8em;
            padding: 0.4em 0.8em;
        }

        .btn-group .btn {
            border-radius: 0;
        }

        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }

        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }

        .dropdown-menu {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
        }

        @media (max-width: 768px) {
            .card-tools {
                margin-top: 1rem;
            }

            .card-tools input {
                width: 100% !important;
            }

            .package-card {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection
