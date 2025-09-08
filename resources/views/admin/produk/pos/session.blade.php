@extends('layouts.app')

@section('title', 'POS Session - ' . $session->activationPin->code)

@section('content')
    <div class="page-inner">
        <!-- Header -->
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">POS Session</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.pos.dashboard') }}">Dashboard POS</a></li>
                        <li class="breadcrumb-item active">Session {{ $session->activationPin->code }}</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <button class="btn btn-warning me-2" onclick="cancelSession()">
                    <i class="fas fa-times"></i> Batalkan
                </button>
                <button class="btn btn-success" onclick="completeSession()" id="completeBtn"
                    {{ $session->items->count() == 0 ? 'disabled' : '' }}>
                    <i class="fas fa-check"></i> Selesaikan
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Product Selection (Left Side) -->
            <div class="col-md-8">
                <!-- Session Info Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>{{ $session->member->name ?? 'N/A' }}</h5>
                                <p class="text-muted mb-1">{{ $session->member->email ?? '' }}</p>
                                <p class="mb-0">
                                    PIN: <code>{{ $session->activationPin->code }}</code>
                                    {!! $session->activationPin->bagan_badge !!}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6 class="mb-1">Budget</h6>
                                        <p class="mb-0 text-primary fw-bold">
                                            Rp {{ number_format($session->total_budget, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="mb-1">Terpakai</h6>
                                        <p class="mb-0 text-warning fw-bold">
                                            Rp {{ number_format($session->used_budget, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="mb-1">Sisa</h6>
                                        <p class="mb-0 text-success fw-bold" id="remainingBudget">
                                            Rp {{ number_format($session->remaining_budget, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Progress Bar -->
                                <div class="progress mt-2" style="height: 8px;">
                                    <div class="progress-bar bg-primary" id="budgetProgress"
                                        style="width: {{ $session->total_budget > 0 ? ($session->used_budget / $session->total_budget) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Pilih Produk</div>
                        <div class="card-tools">
                            <div class="input-group input-group-sm">
                                <input type="text" id="searchProduct" class="form-control" placeholder="Cari produk...">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <div class="row" id="productGrid">
                            @foreach ($products as $product)
                                <div class="col-md-6 col-lg-4 mb-3 product-item"
                                    data-name="{{ strtolower($product->name) }}"
                                    data-sku="{{ strtolower($product->sku) }}">
                                    <div class="card product-card h-100"
                                        onclick="selectProduct({{ $product->id }}, '{{ $product->name }}', {{ $product->price }}, {{ $product->pv }}, {{ $product->stock }})">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0" style="font-size: 0.9rem;">{{ $product->name }}
                                                </h6>
                                                <span class="badge badge-info badge-sm">{{ $product->pv }} PV</span>
                                            </div>
                                            <p class="text-muted mb-2" style="font-size: 0.8rem;">
                                                <code>{{ $product->sku }}</code>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-primary">Rp
                                                    {{ number_format($product->price, 0, ',', '.') }}</strong>
                                                <small class="text-muted">Stok: {{ $product->stock }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shopping Cart (Right Side) -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Keranjang</div>
                        <div class="card-tools">
                            <span class="badge badge-primary" id="itemCount">{{ $session->items->count() }} item</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="cartItems" style="max-height: 400px; overflow-y: auto;">
                            @forelse($session->items as $item)
                                <div class="cart-item border-bottom p-3" data-item-id="{{ $item->id }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div style="flex: 1;">
                                            <h6 class="mb-1" style="font-size: 0.9rem;">{{ $item->product->name }}</h6>
                                            <small class="text-muted">{{ $item->quantity }}x @ Rp
                                                {{ number_format($item->unit_price, 0, ',', '.') }}</small>
                                            <br><small class="text-info">{{ $item->total_pv }} PV</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-primary">
                                                Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger mt-1"
                                                onclick="removeItem({{ $item->id }})">
                                                <i class="fas fa-trash" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4" id="emptyCart">
                                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Keranjang kosong</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if ($session->items->count() > 0)
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Total:</small>
                                    <div class="fw-bold" id="cartTotal">
                                        Rp {{ number_format($session->used_budget, 0, ',', '.') }}
                                    </div>
                                    <small class="text-info" id="cartPv">{{ $session->total_pv }} PV</small>
                                </div>
                                @if ($session->max_products)
                                    <div class="text-end">
                                        <small class="text-muted">Produk:</small>
                                        <div class="fw-bold" id="productCount">
                                            {{ $session->products_count }}/{{ $session->max_products }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Product Selection Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <input type="text" class="form-control" id="selectedProductName" readonly>
                        <input type="hidden" id="selectedProductId">
                        <input type="hidden" id="selectedProductPrice">
                        <input type="hidden" id="selectedProductPv">
                        <input type="hidden" id="selectedProductStock">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="displayPrice" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">PV</label>
                            <input type="text" class="form-control" id="displayPv" readonly>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Quantity</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center" id="quantity" value="1"
                                min="1">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="changeQuantity(1)">+</button>
                        </div>
                        <small class="text-muted">Stok tersedia: <span id="availableStock"></span></small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Total: Rp <span id="modalTotal">0</span></strong>
                        <br><small>PV: <span id="modalTotalPv">0</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="addToCart()">Tambah ke Keranjang</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSession = @json($session);

        // Search products
        document.getElementById('searchProduct').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach(item => {
                const name = item.dataset.name;
                const sku = item.dataset.sku;
                const visible = name.includes(searchTerm) || sku.includes(searchTerm);
                item.style.display = visible ? '' : 'none';
            });
        });

        // Select product
        function selectProduct(id, name, price, pv, stock) {
            document.getElementById('selectedProductId').value = id;
            document.getElementById('selectedProductName').value = name;
            document.getElementById('selectedProductPrice').value = price;
            document.getElementById('selectedProductPv').value = pv;
            document.getElementById('selectedProductStock').value = stock;
            document.getElementById('displayPrice').value = number_format(price, 0, ',', '.');
            document.getElementById('displayPv').value = pv;
            document.getElementById('availableStock').textContent = stock;
            document.getElementById('quantity').value = 1;

            updateModalTotal();
            new bootstrap.Modal(document.getElementById('productModal')).show();
        }

        // Change quantity
        function changeQuantity(delta) {
            const quantityInput = document.getElementById('quantity');
            const currentQty = parseInt(quantityInput.value);
            const stock = parseInt(document.getElementById('selectedProductStock').value);
            const newQty = Math.max(1, Math.min(stock, currentQty + delta));

            quantityInput.value = newQty;
            updateModalTotal();
        }

        // Update modal total
        function updateModalTotal() {
            const price = parseFloat(document.getElementById('selectedProductPrice').value);
            const pv = parseFloat(document.getElementById('selectedProductPv').value);
            const quantity = parseInt(document.getElementById('quantity').value);

            const total = price * quantity;
            const totalPv = pv * quantity;

            document.getElementById('modalTotal').textContent = number_format(total, 0, ',', '.');
            document.getElementById('modalTotalPv').textContent = totalPv;
        }

        // Add to cart
        function addToCart() {
            const productId = document.getElementById('selectedProductId').value;
            const quantity = parseInt(document.getElementById('quantity').value);

            fetch(`{{ route('admin.pos.add-product', $session->id) }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update session data
                        currentSession = data.session;

                        // Add item to cart UI
                        addItemToCartUI(data.item);

                        // Update summary
                        updateCartSummary();

                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();

                        // Show success message
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

        // Add item to cart UI
        function addItemToCartUI(item) {
            const cartItems = document.getElementById('cartItems');
            const emptyCart = document.getElementById('emptyCart');

            if (emptyCart) {
                emptyCart.remove();
            }

            const itemHtml = `
                <div class="cart-item border-bottom p-3" data-item-id="${item.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1" style="font-size: 0.9rem;">${item.product.name}</h6>
                            <small class="text-muted">${item.quantity}x @ Rp ${number_format(item.unit_price, 0, ',', '.')}</small>
                            <br><small class="text-info">${item.total_pv} PV</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary">
                                Rp ${number_format(item.total_price, 0, ',', '.')}
                            </div>
                            <button class="btn btn-sm btn-outline-danger mt-1" onclick="removeItem(${item.id})">
                                <i class="fas fa-trash" style="font-size: 0.7rem;"></i>
                            </button>
                        </div>
                    </div>
                </div>`;

            cartItems.insertAdjacentHTML('beforeend', itemHtml);
        }

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
                                window.location.href = data.redirect_url;

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

        // Remove item
        function removeItem(itemId) {
            Swal.fire({
                title: 'Hapus item?',
                text: 'Item akan dihapus dari keranjang',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('admin.pos.remove-item', [$session->id, '']) }}/${itemId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update session data
                                currentSession = data.session;

                                // Remove item from UI
                                document.querySelector(`[data-item-id="${itemId}"]`).remove();

                                // Update summary
                                updateCartSummary();

                                // Check if cart is empty
                                if (currentSession.products_count == 0) {
                                    document.getElementById('cartItems').innerHTML = `
                                    <div class="text-center py-4" id="emptyCart">
                                        <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">Keranjang kosong</p>
                                    </div>`;
                                }

                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    window.location.href = data.redirect_url;
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

        // Cancel session
        function cancelSession() {
            Swal.fire({
                title: 'Batalkan Session?',
                text: 'Session akan dibatalkan dan semua item dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tidak',
                input: 'textarea',
                inputPlaceholder: 'Alasan pembatalan...'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('admin.pos.cancel-session', $session->id) }}`, {
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
                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    window.location.href = data.redirect_url;
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

        // Utility function for number formatting
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

        // Auto-save session every 30 seconds (optional)
        // setInterval(() => {
        //     console.log('Auto-saving session...');
        // }, 30000);
    </script>

    <style>
        .product-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #007bff;
        }

        .cart-item {
            transition: background-color 0.2s;
        }

        .cart-item:hover {
            background-color: #f8f9fa;
        }

        .progress {
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .badge-sm {
            font-size: 0.7rem;
        }

        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {

            .col-md-8,
            .col-md-4 {
                margin-bottom: 1rem;
            }

            .product-card .card-body {
                padding: 0.75rem;
            }

            .cart-item {
                padding: 0.75rem;
            }
        }
    </style>
@endsection
