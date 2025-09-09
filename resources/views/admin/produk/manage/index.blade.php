@extends('layouts.app')

@section('title', 'Produk - Barang Keluar')

@section('content')
    <div class="page-inner">
        <!-- Header -->
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Produk - Barang Keluar</h3>
                <p class="mb-2">Pilih produk dan catat jumlah barang yang keluar</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.stock.history') }}" class="btn btn-info me-2">
                    <i class="fas fa-history"></i> History
                </a>
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-box-open"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Item Keluar Hari Ini</p>
                                    <h4 class="card-title">{{ $stats['today_count'] }}</h4>
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
                                <div class="icon-big text-center icon-danger bubble-shadow-small">
                                    <i class="fas fa-cart-arrow-down"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total barang keluar hari ini</p>
                                    <h4 class="card-title">{{ $stats['today_stock'] }}</h4>
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
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Nilai Hari Ini</p>
                                    <h4 class="card-title">Rp {{ number_format($stats['today_value'], 0, ',', '.') }}</h4>
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
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Stok Menipis</p>
                                    <h4 class="card-title">{{ $stats['low_stock_count'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Product List (Left) -->
            <div class="col-md-8">

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Detail Transaksi</div>
                        <div class="card-tools">
                            <span class="badge badge-primary" id="cartCount">0 item</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="cartItems" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center py-4" id="emptyCart">
                                <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Belum ada item</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="card-footer" id="cartSummary" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Total Item:</small>
                                <div class="fw-bold" id="totalItems">0</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Total Nilai:</small>
                                <div class="fw-bold text-primary" id="totalValue">Rp 0</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <small class="text-muted">Total PV:</small>
                                <div class="fw-bold text-info" id="totalPv">0</div>
                            </div>
                        </div>

                        <!-- Reference & Notes -->
                        {{-- <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" id="referenceCode"
                                placeholder="Kode Referensi (PIN, dll)">
                        </div> --}}
                        <div class="mb-3">
                            <textarea class="form-control form-control-md" id="notes" rows="3" placeholder="Catatan (opsional)"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="processTransaction()">
                                <i class="fas fa-check"></i> Proses Transaksi
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash"></i> Kosongkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Cart (Right) -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Daftar Produk</div>
                        <div class="card-tools">
                            <div class="input-group input-group-sm">
                                <input type="text" id="searchProduct" class="form-control"
                                    placeholder="Cari produk...">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover table-striped">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="30%">Produk</th>
                                        {{-- <th width="15%">SKU</th> --}}
                                        <th width="15%">Harga</th>
                                        <th width="10%">Stok</th>
                                        <th width="20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="productTable">
                                    @foreach ($products as $product)
                                        <tr class="product-row" data-name="{{ strtolower($product->name) }}"
                                            data-sku="{{ strtolower($product->sku) }}"
                                            data-product-id="{{ $product->id }}">
                                            <td>
                                                <strong>{{ $product->name }}</strong>
                                            </td>
                                            {{-- <td>
                                                <code>{{ $product->sku }}</code>
                                            </td> --}}
                                            <td>
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ $product->stock <= 10 ? 'badge-danger' : ($product->stock <= 50 ? 'badge-warning' : 'badge-success') }}"
                                                    id="stock-{{ $product->id }}">
                                                    {{ $product->stock }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control"
                                                        id="qty-{{ $product->id }}" min="1"
                                                        max="{{ $product->stock }}" value="1" style="width: 70px;">
                                                    <button class="btn btn-primary btn-sm"
                                                        onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }}, {{ $product->pv }}, {{ $product->stock }})">
                                                        <i class="fas fa-plus"></i> Add
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let products = @json($products);

        // Search products
        document.getElementById('searchProduct').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');

            rows.forEach(row => {
                const name = row.dataset.name;
                const sku = row.dataset.sku;
                const visible = name.includes(searchTerm) || sku.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
            });
        });

        // Add product to cart
        function addToCart(productId, productName, price, pv, stock) {
            const quantityInput = document.getElementById(`qty-${productId}`);
            const quantity = parseInt(quantityInput.value);

            if (quantity < 1 || quantity > stock) {
                Swal.fire('Error!', `Quantity harus antara 1 - ${stock}`, 'error');
                return;
            }

            // Check if product already in cart
            const existingIndex = cart.findIndex(item => item.product_id === productId);

            if (existingIndex !== -1) {
                // Update existing item
                const newQuantity = cart[existingIndex].quantity + quantity;
                if (newQuantity > stock) {
                    Swal.fire('Error!', `Total quantity tidak boleh melebihi stok (${stock})`, 'error');
                    return;
                }
                cart[existingIndex].quantity = newQuantity;
                cart[existingIndex].total_price = cart[existingIndex].quantity * price;
                cart[existingIndex].total_pv = cart[existingIndex].quantity * pv;
            } else {
                // Add new item
                cart.push({
                    product_id: productId,
                    product_name: productName,
                    quantity: quantity,
                    unit_price: price,
                    total_price: quantity * price,
                    unit_pv: pv,
                    total_pv: quantity * pv,
                    max_stock: stock
                });
            }

            // Reset quantity input
            quantityInput.value = 1;

            // Update cart display
            updateCartDisplay();

            // Show success
            Swal.fire({
                icon: 'success',
                title: 'Ditambahkan!',
                text: `${productName} x${quantity}`,
                timer: 1000,
                showConfirmButton: false
            });
        }

        // Remove item from cart
        function removeFromCart(productId) {
            cart = cart.filter(item => item.product_id !== productId);
            updateCartDisplay();
        }

        // Update quantity in cart
        function updateCartQuantity(productId, newQuantity) {
            const item = cart.find(item => item.product_id === productId);
            if (item) {
                if (newQuantity < 1) {
                    removeFromCart(productId);
                    return;
                }

                if (newQuantity > item.max_stock) {
                    Swal.fire('Error!', `Quantity tidak boleh melebihi stok (${item.max_stock})`, 'error');
                    return;
                }

                item.quantity = newQuantity;
                item.total_price = item.quantity * item.unit_price;
                item.total_pv = item.quantity * item.unit_pv;
                updateCartDisplay();
            }
        }

        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');
            const emptyCart = document.getElementById('emptyCart');

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center py-4" id="emptyCart">
                        <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                        <p class="text-muted">Belum ada item</p>
                    </div>`;
                cartSummary.style.display = 'none';
                document.getElementById('cartCount').textContent = '0 item';
                return;
            }

            // Generate cart items HTML
            let itemsHtml = '';
            let totalItems = 0;
            let totalValue = 0;
            let totalPv = 0;

            cart.forEach(item => {
                totalItems += item.quantity;
                totalValue += item.total_price;
                totalPv += item.total_pv;

                itemsHtml += `
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div style="flex: 1;">
                                <h6 class="mb-1" style="font-size: 0.9rem;">${item.product_name}</h6>
                                <small class="text-muted">@ Rp ${number_format(item.unit_price, 0, ',', '.')}</small>
                                <br><small class="text-info">${item.unit_pv} PV per item</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.product_id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <button class="btn btn-outline-secondary" onclick="updateCartQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                                <input type="number" class="form-control text-center" value="${item.quantity}" 
                                       onchange="updateCartQuantity(${item.product_id}, parseInt(this.value))" min="1" max="${item.max_stock}">
                                <button class="btn btn-outline-secondary" onclick="updateCartQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">Rp ${number_format(item.total_price, 0, ',', '.')}</div>
                                <small class="text-info">${item.total_pv} PV</small>
                            </div>
                        </div>
                    </div>`;
            });

            cartItems.innerHTML = itemsHtml;
            cartSummary.style.display = 'block';

            // Update summary
            document.getElementById('cartCount').textContent = `${cart.length} item`;
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('totalValue').textContent = 'Rp ' + number_format(totalValue, 0, ',', '.');
            document.getElementById('totalPv').textContent = totalPv;
        }

        // Clear cart
        function clearCart() {
            if (cart.length === 0) return;

            Swal.fire({
                title: 'Kosongkan keranjang?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    cart = [];
                    updateCartDisplay();
                }
            });
        }

        // Process transaction
        function processTransaction() {
            if (cart.length === 0) {
                Swal.fire('Error!', 'Keranjang masih kosong', 'error');
                return;
            }

            // const referenceCode = document.getElementById('referenceCode').value;
            const notes = document.getElementById('notes').value;

            Swal.fire({
                title: 'Proses transaksi?',
                text: `${cart.length} jenis produk akan dicatat keluar`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Proses',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('admin.stock.process') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                items: cart,
                                // reference_code: referenceCode,
                                notes: notes
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    // Clear cart and refresh
                                    cart = [];
                                    updateCartDisplay();
                                    // document.getElementById('referenceCode').value = '';
                                    document.getElementById('notes').value = '';
                                    refreshData();
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

        // Refresh data (update stock)
        function refreshData() {
            location.reload();
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
    </script>

    <style>
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
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

        .input-group-sm .form-control {
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {

            .col-md-8,
            .col-md-4 {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection
