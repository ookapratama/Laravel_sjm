@extends('layouts.app')

@section('title', 'Edit Paket: ' . $package->name)

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Kelola Paket</a></li>
                        <li class="breadcrumb-item active">Edit: {{ $package->name }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-3">Edit Paket: {{ $package->name }}</h3>
                <p class="mb-2">Edit informasi dan produk dalam paket</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.packages.show', $package) }}" class="btn btn-info me-2">
                    <i class="fas fa-eye"></i> Detail
                </a>
                <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('admin.packages.update', $package) }}" method="POST" id="packageForm">
            @csrf
            @method('PUT')
            <div class="row">
                <!-- Package Info -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Informasi Paket</div>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Nama Paket <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $package->name) }}"
                                    placeholder="Contoh: Paket Basic, Paket Premium">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Deskripsi singkat tentang paket ini...">{{ old('description', $package->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="max_value" class="form-label">Nilai Maksimal Paket <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="max_value" id="max_value"
                                        class="form-control @error('max_value') is-invalid @enderror"
                                        value="{{ old('max_value', $package->max_value) }}" step="1000">
                                </div>
                                @error('max_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Total nilai produk tidak boleh melebihi batas
                                    ini</small>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                    {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Aktifkan paket
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Package Statistics -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="card-title">Statistik Paket</div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Nilai Maksimal:</span>
                                <strong id="statsMaxValue">Rp
                                    {{ number_format($package->max_value, 0, ',', '.') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Nilai:</span>
                                <strong id="statsTotal">Rp {{ number_format($package->total_value, 0, ',', '.') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Sisa:</span>
                                <strong id="statsRemaining"
                                    class="{{ $package->max_value - $package->total_value >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($package->max_value - $package->total_value, 0, ',', '.') }}
                                </strong>
                            </div>

                            @php
                                $percentage = ($package->total_value / $package->max_value) * 100;
                                $progressColor =
                                    $percentage > 100 ? 'danger' : ($percentage > 90 ? 'warning' : 'success');
                            @endphp

                            <div class="progress mb-2" style="height: 8px;">
                                <div id="valueProgressBar" class="progress-bar bg-{{ $progressColor }}"
                                    style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                            <div class="text-center">
                                <small id="progressText" class="text-{{ $progressColor }}">
                                    {{ number_format($percentage, 1) }}% dari batas maksimal
                                </small>
                            </div>

                            <div id="packageAlert" class="mt-3">
                                @if ($package->total_value > $package->max_value)
                                    <div class="alert alert-danger alert-sm">
                                        <small><i class="fas fa-exclamation-triangle"></i> Nilai paket melebihi batas
                                            maksimal!</small>
                                    </div>
                                @elseif($package->total_value === 0)
                                    <div class="alert alert-info alert-sm">
                                        <small><i class="fas fa-info-circle"></i> Paket kosong</small>
                                    </div>
                                @elseif($package->max_value - $package->total_value < $package->max_value * 0.1)
                                    <div class="alert alert-warning alert-sm">
                                        <small><i class="fas fa-exclamation-circle"></i> Mendekati batas maksimal</small>
                                    </div>
                                @else
                                    <div class="alert alert-success alert-sm">
                                        <small><i class="fas fa-check-circle"></i> Masih bisa tambah Rp
                                            {{ number_format($package->max_value - $package->total_value, 0, ',', '.') }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Available Products -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="card-title">Produk Tersedia</div>
                            <div class="card-tools">
                                <input type="text" id="searchAvailable" class="form-control form-control-sm"
                                    placeholder="Cari produk...">
                            </div>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            @forelse($availableProducts as $product)
                                <div class="product-item border rounded p-2 mb-2" data-product-id="{{ $product->id }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <small class="fw-bold product-name">{{ $product->name }}</small>
                                            <br><small class="text-muted">{{ $product->sku }} | Rp
                                                {{ number_format($product->price, 0, ',', '.') }}</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="addProductToPackage({{ $product->id }})" title="Tambah ke paket">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-3">
                                    <small class="text-muted">Semua produk sudah ada dalam paket</small>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Package Products -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="card-title mb-0">Produk dalam Paket</div>
                                <div id="totalValue" class="badge badge-lg badge-primary">
                                    Total: Rp {{ number_format($package->total_value, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($package->packageProducts->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="packageTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40%">Produk</th>
                                                <th width="15%" class="text-center">Jumlah</th>
                                                <th width="18%" class="text-end">Harga Satuan</th>
                                                <th width="18%" class="text-end">Total</th>
                                                <th width="9%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="packageTableBody">
                                            @foreach ($package->packageProducts as $item)
                                                <tr data-product-id="{{ $item->product->id }}"
                                                    data-price="{{ $item->product->price }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm me-3"
                                                                style="background-color: #f1f2f6;">
                                                                <i class="fas fa-box text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <strong>{{ $item->product->name }}</strong><br>
                                                                <small class="text-muted">SKU: {{ $item->product->sku }} |
                                                                    PV: {{ $item->product->pv }}</small><br>
                                                                <small class="text-success">Stok:
                                                                    {{ $item->product->stock }}</small>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" name="products[{{ $loop->index }}][id]"
                                                            value="{{ $item->product->id }}">
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="input-group input-group-sm"
                                                            style="max-width: 120px; margin: 0 auto;">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                onclick="changeQuantity(this, -1)">-</button>
                                                            <input type="number"
                                                                name="products[{{ $loop->index }}][quantity]"
                                                                class="form-control text-center quantity-input"
                                                                value="{{ $item->quantity }}" min="1"
                                                                onchange="updateTotal()">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                onclick="changeQuantity(this, 1)">+</button>
                                                        </div>
                                                    </td>
                                                    <td class="text-end product-price">
                                                        Rp {{ number_format($item->product->price, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-end product-total fw-bold">
                                                        Rp
                                                        {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="removeProduct(this)" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">TOTAL NILAI PAKET:</td>
                                                <td class="text-end fw-bold" id="grandTotal">
                                                    <span class="text-success fs-5">Rp
                                                        {{ number_format($package->total_value, 0, ',', '.') }}</span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Total produk: <span
                                                id="totalItems">{{ $package->packageProducts->sum('quantity') }}</span>
                                            item |
                                            Variasi: <span
                                                id="totalVariations">{{ $package->packageProducts->count() }}</span>
                                            jenis
                                        </small>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-secondary me-2" onclick="resetPackage()">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save"></i> Update Paket
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div id="emptyState" class="text-center py-5">
                                    <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                                    <h4 class="text-muted">Paket Kosong</h4>
                                    <p class="text-muted mb-4">Belum ada produk dalam paket. Tambahkan produk dari sidebar!
                                    </p>
                                </div>

                                <!-- Hidden table for when products are added -->
                                <div id="packageTable" style="display: none;">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="40%">Produk</th>
                                                    <th width="15%" class="text-center">Jumlah</th>
                                                    <th width="18%" class="text-end">Harga Satuan</th>
                                                    <th width="18%" class="text-end">Total</th>
                                                    <th width="9%" class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="packageTableBody">
                                                <!-- Products will be added here -->
                                            </tbody>
                                            <tfoot class="table-secondary">
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold">TOTAL NILAI PAKET:</td>
                                                    <td class="text-end fw-bold" id="grandTotal">
                                                        <span class="text-success fs-5">Rp 0</span>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                Total produk: <span id="totalItems">0</span> item |
                                                Variasi: <span id="totalVariations">0</span> jenis
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-secondary me-2"
                                                onclick="resetPackage()">
                                                <i class="fas fa-undo"></i> Reset
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save"></i> Update Paket
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        let productIndex = {{ $package->packageProducts->count() }};
        const availableProductsData = @json($products->keyBy('id'));

        // Update max value display
        document.getElementById('max_value').addEventListener('input', function() {
            const maxValue = parseFloat(this.value) || 0;
            document.getElementById('statsMaxValue').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(
                maxValue);
            updateStatistics();
        });

        // Search functionality for available products
        document.getElementById('searchAvailable').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        function addProductToPackage(productId) {
            // Check if product already in package
            const existingRow = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (existingRow) {
                const quantityInput = existingRow.querySelector('.quantity-input');
                quantityInput.value = parseInt(quantityInput.value) + 1;
                updateTotal();

                existingRow.classList.add('table-warning');
                setTimeout(() => existingRow.classList.remove('table-warning'), 1000);
                return;
            }

            const product = availableProductsData[productId];
            if (!product) {
                alert('Produk tidak ditemukan!');
                return;
            }

            // Show package table if hidden
            const emptyState = document.getElementById('emptyState');
            const packageTable = document.getElementById('packageTable');
            if (emptyState && emptyState.style.display !== 'none') {
                emptyState.style.display = 'none';
                packageTable.style.display = 'block';
            }

            // Create new table row
            const tableBody = document.getElementById('packageTableBody');
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-product-id', productId);
            newRow.setAttribute('data-price', product.price);

            newRow.innerHTML = `
        <td>
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3" style="background-color: #f1f2f6;">
                    <i class="fas fa-box text-primary"></i>
                </div>
                <div>
                    <strong>${product.name}</strong><br>
                    <small class="text-muted">SKU: ${product.sku} | PV: ${product.pv}</small><br>
                    <small class="text-success">Stok: ${product.stock}</small>
                </div>
            </div>
            <input type="hidden" name="products[${productIndex}][id]" value="${productId}">
        </td>
        <td class="text-center">
            <div class="input-group input-group-sm" style="max-width: 120px; margin: 0 auto;">
                <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(this, -1)">-</button>
                <input type="number" name="products[${productIndex}][quantity]" 
                       class="form-control text-center quantity-input" 
                       value="1" min="1" onchange="updateTotal()">
                <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(this, 1)">+</button>
            </div>
        </td>
        <td class="text-end product-price">
            Rp ${new Intl.NumberFormat('id-ID').format(product.price)}
        </td>
        <td class="text-end product-total fw-bold">
            Rp ${new Intl.NumberFormat('id-ID').format(product.price)}
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeProduct(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

            tableBody.appendChild(newRow);
            productIndex++;

            updateTotal();

            // Animate new row
            newRow.classList.add('table-success');
            setTimeout(() => newRow.classList.remove('table-success'), 1500);

            // Hide from available products
            const availableProductItem = document.querySelector(`.product-item[data-product-id="${productId}"]`);
            if (availableProductItem) {
                availableProductItem.style.display = 'none';
            }
        }

        function changeQuantity(button, change) {
            const input = button.parentElement.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const newValue = Math.max(1, currentValue + change);
            input.value = newValue;
            updateTotal();
        }

        function removeProduct(button) {
            if (confirm('Hapus produk dari paket?')) {
                const row = button.closest('tr');
                const productId = row.getAttribute('data-product-id');

                row.remove();
                updateTotal();

                // Show product back in available list
                const availableProductItem = document.querySelector(`.product-item[data-product-id="${productId}"]`);
                if (availableProductItem) {
                    availableProductItem.style.display = 'block';
                }

                // Show empty state if no products left
                const rows = document.querySelectorAll('#packageTableBody tr');
                if (rows.length === 0) {
                    const emptyState = document.getElementById('emptyState');
                    const packageTable = document.getElementById('packageTable');
                    if (emptyState) {
                        emptyState.style.display = 'block';
                        packageTable.style.display = 'none';
                    }
                }
            }
        }

        function resetPackage() {
            if (confirm('Reset semua produk dalam paket? Data akan hilang!')) {
                location.reload();
            }
        }

        function updateTotal() {
            const rows = document.querySelectorAll('#packageTableBody tr');
            let totalValue = 0;
            let totalItems = 0;

            rows.forEach(row => {
                const price = parseFloat(row.getAttribute('data-price'));
                const quantityInput = row.querySelector('.quantity-input');
                const quantity = parseInt(quantityInput.value) || 1;

                const productTotal = price * quantity;
                totalValue += productTotal;
                totalItems += quantity;

                // Update row total
                const totalCell = row.querySelector('.product-total');
                totalCell.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(productTotal);
            });

            // Update grand total
            const grandTotalElement = document.getElementById('grandTotal');
            if (grandTotalElement) {
                grandTotalElement.innerHTML =
                    `<span class="text-success fs-5">Rp ${new Intl.NumberFormat('id-ID').format(totalValue)}</span>`;
            }

            // Update header total
            const totalValueElement = document.getElementById('totalValue');
            if (totalValueElement) {
                totalValueElement.textContent = `Total: Rp ${new Intl.NumberFormat('id-ID').format(totalValue)}`;
            }

            // Update counters
            const totalItemsElement = document.getElementById('totalItems');
            const totalVariationsElement = document.getElementById('totalVariations');
            if (totalItemsElement) totalItemsElement.textContent = totalItems;
            if (totalVariationsElement) totalVariationsElement.textContent = rows.length;

            updateStatistics(totalValue);
        }

        function updateStatistics(totalValue = 0) {
            const maxValue = parseFloat(document.getElementById('max_value').value) || 750000;
            const remaining = maxValue - totalValue;
            const percentage = (totalValue / maxValue) * 100;

            // Update statistics
            document.getElementById('statsTotal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalValue);
            document.getElementById('statsRemaining').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(
                0, Math.abs(remaining)));

            // Update progress bar
            const progressBar = document.getElementById('valueProgressBar');
            const progressText = document.getElementById('progressText');

            progressBar.style.width = Math.min(percentage, 100) + '%';
            progressText.textContent = percentage.toFixed(1) + '% dari batas maksimal';

            // Update progress bar color and remaining text color
            const remainingElement = document.getElementById('statsRemaining');
            if (percentage > 100) {
                progressBar.className = 'progress-bar bg-danger';
                remainingElement.className = 'text-danger';
                remainingElement.textContent = 'Rp -' + new Intl.NumberFormat('id-ID').format(Math.abs(remaining));
            } else if (percentage > 90) {
                progressBar.className = 'progress-bar bg-warning';
                remainingElement.className = 'text-success';
            } else {
                progressBar.className = 'progress-bar bg-success';
                remainingElement.className = 'text-success';
            }

            // Update alert
            const packageAlert = document.getElementById('packageAlert');
            let alertHTML = '';

            if (totalValue > maxValue) {
                alertHTML = `<div class="alert alert-danger alert-sm">
            <small><i class="fas fa-exclamation-triangle"></i> Nilai paket melebihi batas maksimal!</small>
        </div>`;
            } else if (totalValue === 0) {
                alertHTML = `<div class="alert alert-info alert-sm">
            <small><i class="fas fa-info-circle"></i> Paket kosong</small>
        </div>`;
            } else if (remaining < maxValue * 0.1) {
                alertHTML = `<div class="alert alert-warning alert-sm">
            <small><i class="fas fa-exclamation-circle"></i> Mendekati batas maksimal</small>
        </div>`;
            } else {
                alertHTML = `<div class="alert alert-success alert-sm">
            <small><i class="fas fa-check-circle"></i> Masih bisa tambah Rp ${new Intl.NumberFormat('id-ID').format(remaining)}</small>
        </div>`;
            }

            packageAlert.innerHTML = alertHTML;
        }

        // Form validation
        document.getElementById('packageForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#packageTableBody tr');

            if (rows.length === 0) {
                e.preventDefault();
                alert('Paket tidak boleh kosong! Tambahkan minimal 1 produk.');
                return;
            }

            const maxValue = parseFloat(document.getElementById('max_value').value) || 0;
            const totalValue = Array.from(rows).reduce((sum, row) => {
                const price = parseFloat(row.getAttribute('data-price'));
                const quantity = parseInt(row.querySelector('.quantity-input').value) || 1;
                return sum + (price * quantity);
            }, 0);

            if (totalValue > maxValue) {
                e.preventDefault();
                alert(
                    'Total nilai paket melebihi batas maksimal! Kurangi jumlah produk atau naikkan batas maksimal.');
                return;
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateTotal();
        });
    </script>

    <style>
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-lg {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
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

        #packageTableBody tr.table-warning {
            animation: highlight 1s ease-in-out;
        }

        #packageTableBody tr.table-success {
            animation: highlightSuccess 1.5s ease-in-out;
        }

        @keyframes highlight {
            0% {
                background-color: #fff3cd;
            }

            100% {
                background-color: transparent;
            }
        }

        @keyframes highlightSuccess {
            0% {
                background-color: #d1e7dd;
            }

            100% {
                background-color: transparent;
            }
        }

        .card-tools input {
            width: 200px;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.75rem;
            }

            .input-group {
                max-width: 90px !important;
            }

            .avatar {
                width: 30px;
                height: 30px;
            }

            .card-tools input {
                width: 100%;
            }
        }
    </style>
@endsection
