@extends('layouts.app')

@section('title', 'Kelola Produk')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Kelola Produk</h3>
                <p class="mb-2">Manajemen produk untuk sistem MLM</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.pos.dashboard') }}" class="btn btn-success me-2">
                    <i class="fas fa-package"></i> Kelola Paket
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Daftar Produk</div>
                <div class="card-tools">
                    <div class="input-group input-group-sm">
                        <input type="text" id="searchProduct" class="form-control" placeholder="Cari produk...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="productsTable">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama Produk</th>
                                <th width="10%">SKU</th>
                                <th width="12%">Harga</th>
                                <th width="8%">PV</th>
                                <th width="8%">Stok</th>
                                <th width="10%">Status</th>
                                <th width="12%">Dibuat</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $product->name }}</strong>
                                            @if (!$product->is_active)
                                                <span class="badge badge-secondary ms-1">Non-aktif</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td><code>{{ $product->sku }}</code></td>
                                    <td class="text-end">
                                        <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $product->pv }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($product->stock <= 10)
                                            <span class="badge badge-danger">{{ $product->stock }}</span>
                                        @elseif($product->stock <= 50)
                                            <span class="badge badge-warning">{{ $product->stock }}</span>
                                        @else
                                            <span class="badge badge-success">{{ $product->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($product->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td>{{ $product->created_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            {{-- <button class="btn btn-sm btn-info" onclick="viewProduct({{ $product->id }})"
                                                title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </button> --}}
                                            <button class="btn btn-sm btn-warning"
                                                onclick="editProduct({{ $product->id }})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="deleteProduct({{ $product->id }})" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Belum ada produk</h5>
                                            <p class="text-muted">Mulai dengan menambah produk pertama!</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.products.store') }}" method="POST" id="addProductForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="">
                            <div class="form-text">Contoh: Parfum Rose Premium 100ml</div>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" required value="">
                            <div class="form-text">Kode unik produk. Contoh: PRF-ROSE-100</div>
                            @error('sku')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price" class="form-control" required
                                            min="0" step="1000" value="">
                                    </div>
                                    @error('price')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Point Value (PV)</label>
                                    <input type="number" name="pv" class="form-control" min="0"
                                        step="0.5" value="">
                                    <div class="form-text">Point untuk bonus MLM</div>
                                    @error('pv')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok Awal</label>
                            <input type="number" name="stock" class="form-control" min="0"
                                value="0">
                            @error('stock')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }} id="isActive">
                                <label class="form-check-label" for="isActive">
                                    Produk Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProductForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" id="edit_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" id="edit_sku" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="price" class="form-control" id="edit_price"
                                            required min="0" step="1000">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Point Value (PV)</label>
                                    <input type="number" name="pv" class="form-control" id="edit_pv"
                                        min="0" step="0.5">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stock" class="form-control" id="edit_stock" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Produk Aktif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="successMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>

        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="errorMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>

        <div id="warningToast" class="toast align-items-center text-white bg-warning border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="warningMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        // Toast functions
        function showToast(type, message) {
            const toastElement = document.getElementById(type + 'Toast');
            const messageElement = document.getElementById(type + 'Message');

            if (toastElement && messageElement) {
                messageElement.textContent = message;
                const toast = type === 'success' ? toastr.success(message) : toastr.error(message)
                toast.show();
            }
        }

        // Show toast on page load if there are session messages
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                showToast('success', '{{ session('success') }}');
            @endif

            @if (session('error'))
                showToast('error', '{{ session('error') }}');
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    showToast('error', '{{ $error }}');
                @endforeach
            @endif

            // Reopen modal if there are validation errors
            @if ($errors->any() && old('_method') !== 'PUT')
                new bootstrap.Modal(document.getElementById('addProductModal')).show();
            @endif
        });

        // Search functionality
        document.getElementById('searchProduct').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#productsTable tbody tr');

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // View product
        // function viewProduct(id) {
        //     window.location.href = `/products/${id}/edit`;
        // }

        // Edit product
        function editProduct(id) {
            fetch(`products/${id}/edit`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('edit_name').value = data.name || '';
                    document.getElementById('edit_sku').value = data.sku || '';
                    document.getElementById('edit_price').value = data.price || '';
                    document.getElementById('edit_pv').value = data.pv || '';
                    document.getElementById('edit_stock').value = data.stock || '';
                    document.getElementById('edit_is_active').checked = data.is_active;

                    document.getElementById('editProductForm').action = `products/update/${id}`;
                    new bootstrap.Modal(document.getElementById('editProductModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Gagal memuat data produk');
                });
        }

        // Delete product
        function deleteProduct(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus produk?',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    const note = result.value;

                    fetch(`products/delete/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('success', data.message || 'Produk berhasil dihapus');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showToast('error', data.message || 'Gagal menghapus produk');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', 'Terjadi kesalahan saat menghapus produk');
                        });

                }
            });

        }

        // Auto generate SKU from name
        document.querySelector('#addProductModal input[name="name"]').addEventListener('input', function() {
            const name = this.value;
            const sku = name.toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .replace(/\s+/g, '-')
                .substring(0, 20);

            document.querySelector('#addProductModal input[name="sku"]').value = sku;
        });

        // Format price input
        document.querySelectorAll('input[name="price"]').forEach(input => {
            input.addEventListener('input', function() {
                // Remove non-numeric characters and format
                let value = this.value.replace(/[^0-9]/g, '');
                if (value) {
                    this.value = parseInt(value);
                }
            });
        });

        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const name = document.querySelector('#addProductModal input[name="name"]').value.trim();
            const sku = document.querySelector('#addProductModal input[name="sku"]').value.trim();
            const price = document.querySelector('#addProductModal input[name="price"]').value;

            if (!name) {
                e.preventDefault();
                showToast('error', 'Nama produk harus diisi');
                return;
            }

            if (!sku) {
                e.preventDefault();
                showToast('error', 'SKU harus diisi');
                return;
            }

            if (!price || price <= 0) {
                e.preventDefault();
                showToast('error', 'Harga produk harus lebih dari 0');
                return;
            }
        });

        // Clear form when modal closes
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addProductForm').reset();
        });
    </script>

    <style>
        .empty-state {
            padding: 2rem;
        }

        .badge {
            font-size: 0.75rem;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        .form-text {
            font-size: 0.875em;
            color: #6c757d;
        }

        .table th {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.875rem;
        }

        #searchProduct {
            border-radius: 4px;
        }

        .card-tools {
            margin-left: auto;
        }

        /* Toast styling */
        .toast-container {
            z-index: 1055;
        }

        .toast {
            min-width: 300px;
        }

        .toast-body {
            padding: 0.75rem;
        }

        /* Loading state */
        .btn.loading {
            position: relative;
            pointer-events: none;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            width: 1rem;
            height: 1rem;
            top: 50%;
            left: 50%;
            margin-left: -0.5rem;
            margin-top: -0.5rem;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Error styling */
        .text-danger.small {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                margin-bottom: 2px;
                margin-right: 0;
            }

            .table-responsive {
                font-size: 0.8rem;
            }
        }
    </style>
@endsection
