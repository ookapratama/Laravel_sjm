@extends('layouts.app')

@section('title', 'Assign Paket ke Activation Pins')

@section('content')
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Assign Paket ke Activation Pins</h3>
                <p class="mb-2">Tentukan paket untuk activation pins yang sudah digunakan user</p>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-box"></i> Kelola Paket
                </a>
                <button class="btn btn-success" onclick="bulkAssignModal()">
                    <i class="fas fa-layer-group"></i> Bulk Assign
                </button>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $unassignedCount ?? 0 }}</h4>
                                <p class="mb-0">Belum Mendapatkan Paket</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $assignedCount ?? 0 }}</h4>
                                <p class="mb-0">Sudah Mendapatkan Paket</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $totalUsedPins ?? 0 }}</h4>
                                <p class="mb-0">Total Pin Terpakai (Used)</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>{{ $availablePackages ?? 0 }}</h4>
                                <p class="mb-0">Paket Tersedia</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-gift fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="card-title mb-0">
                                Activation Pins Belum Assign Paket ({{ $unassignedPins->count() ?? 0 }} pins)
                            </div>
                            <div class="card-tools">
                                <input type="text" id="searchPins" class="form-control"
                                    placeholder="Cari user atau pin..." style="width: 250px;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($unassignedPins->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover" id="pinsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th width="15%">Pin Code</th>
                                            <th width="20%">User</th>
                                            <th width="15%">Tanggal Used</th>
                                            <th width="25%">Paket</th>
                                            <th width="20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unassignedPins as $pin)
                                            <tr class="pin-row"
                                                data-user="{{ strtolower($pin->user_name . ' ' . $pin->user_username) }}"
                                                data-pin="{{ strtolower($pin->code) }}">
                                                <td>
                                                    <input type="checkbox" class="pin-checkbox form-check-input"
                                                        value="{{ $pin->id }}">
                                                </td>
                                                <td>
                                                    <code
                                                        class="bg-warning text-dark px-2 py-1 rounded">{{ $pin->code }}</code>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                                            style="width: 35px; height: 35px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong>{{ $pin->user_name }}</strong><br>
                                                            <small class="text-muted">{{ $pin->user_username }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($pin->used_at)->format('d/m/Y H:i') }}
                                                    </small>
                                                    <br>
                                                    <small class="badge bg-info">
                                                        {{ \Carbon\Carbon::parse($pin->used_at)->diffForHumans() }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm package-select"
                                                        data-pin-id="{{ $pin->id }}" onchange="quickAssign(this)">
                                                        <option value="">Pilih Paket...</option>
                                                        @foreach ($packages as $package)
                                                            <option value="{{ $package->id }}"
                                                                data-name="{{ $package->name }}"
                                                                data-value="{{ $package->total_value }}">
                                                                {{ $package->name }}
                                                                (Rp
                                                                {{ number_format($package->total_value, 0, ',', '.') }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-success"
                                                            onclick="assignPackageModal({{ $pin->id }}, '{{ $pin->code }}', '{{ $pin->user_name }}')"
                                                            title="Assign Package">
                                                            <i class="fas fa-gift"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info"
                                                            onclick="viewUserHistory({{ $pin->used_by }})"
                                                            title="History User">
                                                            <i class="fas fa-history"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div id="noResultsFound" class="text-center py-5" style="display: none;">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada data yang ditemukan</h5>
                                <p class="text-muted">Coba ubah kata kunci pencarian</p>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                                <h4 class="text-success">Semua Pin Sudah Diassign!</h4>
                                <p class="text-muted">Tidak ada activation pin yang perlu diassign paket</p>
                                {{-- <a href="{{ route('admin.product.transaction.assigned') }}"
                                    class="btn btn-success">
                                    <i class="fas fa-list"></i> Lihat Yang Sudah Assign
                                </a> --}}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Assign Package -->
    <div class="modal fade" id="assignPackageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="assignPackageForm">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-gift me-2"></i>Assign Paket ke Pin
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="assignPinId" name="pin_id">

                        <!-- Pin Info -->
                        <div class="alert alert-info">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="fas fa-key fa-2x"></i>
                                </div>
                                <div class="col">
                                    <h6 class="alert-heading mb-1">Pin Information</h6>
                                    <div><strong>Code:</strong> <span id="assignPinCode">-</span></div>
                                    <div><strong>User:</strong> <span id="assignUserName">-</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="mb-3">
                            <label class="form-label">Pilih Paket</label>
                            <select name="package_id" id="packageSelection" class="form-select" required>
                                <option value="">Pilih Paket...</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" data-name="{{ $package->name }}"
                                        data-description="{{ $package->description }}"
                                        data-value="{{ $package->total_value }}"
                                        data-items="{{ $package->total_items }}">
                                        {{ $package->name }} - Rp {{ number_format($package->total_value, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Package Preview -->
                        <div id="packagePreview" class="card border-success d-none">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Package Preview</h6>
                            </div>
                            <div class="card-body">
                                <div id="packageDetails">
                                    <!-- Package details will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Assign Paket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Assign -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="bulkAssignForm">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Bulk Assign Paket</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Paket untuk Multiple Pins</label>
                            <select name="package_id" class="form-select" required>
                                <option value="">Pilih Paket...</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}">
                                        {{ $package->name }} - Rp {{ number_format($package->total_value, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <small><i class="fas fa-exclamation-triangle"></i>
                                Paket akan diassign ke semua pin yang dicentang</small>
                        </div>
                        <div id="selectedPinsCount" class="text-muted">
                            Tidak ada pin yang dipilih
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Bulk Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchPins').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.pin-row');
            const table = document.getElementById('pinsTable');
            const noResults = document.getElementById('noResultsFound');
            let visibleCount = 0;

            rows.forEach(row => {
                const userData = row.getAttribute('data-user');
                const pinData = row.getAttribute('data-pin');

                if (userData.includes(searchTerm) || pinData.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && searchTerm !== '') {
                table.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                table.style.display = '';
                noResults.style.display = 'none';
            }
        });

        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.pin-checkbox');
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('tr').style.display !== 'none') {
                    checkbox.checked = this.checked;
                }
            });
            updateSelectedCount();
        });

        // Update selected count for bulk assign
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('pin-checkbox')) {
                updateSelectedCount();
            }
        });

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.pin-checkbox:checked');
            const countDiv = document.getElementById('selectedPinsCount');
            countDiv.textContent = `${selected.length} pin dipilih`;
        }

        // Quick assign from dropdown - URL UPDATED
        function quickAssign(selectElement) {
            const pinId = selectElement.getAttribute('data-pin-id');
            const packageId = selectElement.value;

            if (!packageId) return;

            Swal.fire({
                title: 'Assign Paket?',
                text: 'Yakin ingin assign paket ini ke pin?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Assign!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/products/${pinId}/transaction-packages`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify({
                                package_id: packageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message || 'Paket berhasil diassign!');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                toastr.error(data.message || 'Gagal assign paket');
                                selectElement.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Terjadi kesalahan saat assign paket');
                            selectElement.value = '';
                        });
                } else {
                    selectElement.value = '';
                }
            });
        }

        // Show assign package modal
        function assignPackageModal(pinId, pinCode, userName) {
            document.getElementById('assignPinId').value = pinId;
            document.getElementById('assignPinCode').textContent = pinCode;
            document.getElementById('assignUserName').textContent = userName;

            // Reset form
            document.getElementById('packageSelection').value = '';
            document.getElementById('packagePreview').classList.add('d-none');

            new bootstrap.Modal(document.getElementById('assignPackageModal')).show();
        }

        // Package selection change
        document.getElementById('packageSelection').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const preview = document.getElementById('packagePreview');

            if (this.value) {
                const packageDetails = document.getElementById('packageDetails');
                packageDetails.innerHTML = `
                    <h6>${selectedOption.dataset.name}</h6>
                    <p class="text-muted">${selectedOption.dataset.description || 'Tidak ada deskripsi'}</p>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Total Items:</small><br>
                            <strong>${selectedOption.dataset.items}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Total Value:</small><br>
                            <strong class="text-success">Rp ${new Intl.NumberFormat('id-ID').format(selectedOption.dataset.value)}</strong>
                        </div>
                    </div>
                `;
                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }
        });

        // Assign package form submit - URL UPDATED
        document.getElementById('assignPackageForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const pinId = formData.get('pin_id');

            // Loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;

            fetch(`/admin/products/${pinId}/transaction-packages`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message || 'Paket berhasil diassign!');
                        bootstrap.Modal.getInstance(document.getElementById('assignPackageModal')).hide();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        toastr.error(data.message || 'Gagal assign paket');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Terjadi kesalahan saat assign paket');
                })
                .finally(() => {
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Bulk assign modal
        function bulkAssignModal() {
            const selected = document.querySelectorAll('.pin-checkbox:checked');
            if (selected.length === 0) {
                Swal.fire({
                    title: 'Tidak Ada Pin Terpilih',
                    text: 'Pilih minimal 1 pin untuk bulk assign',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            updateSelectedCount();
            new bootstrap.Modal(document.getElementById('bulkAssignModal')).show();
        }

        // Bulk assign form submit - URL UPDATED
        document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const selected = Array.from(document.querySelectorAll('.pin-checkbox:checked')).map(cb => cb.value);
            const packageId = this.querySelector('select[name="package_id"]').value;

            if (selected.length === 0) {
                toastr.warning('Tidak ada pin yang dipilih');
                return;
            }

            // Confirmation dengan SweetAlert
            Swal.fire({
                title: 'Bulk Assign Paket?',
                html: `Assign paket ke <strong>${selected.length} pin</strong>?<br><small class="text-muted">Aksi ini tidak dapat dibatalkan</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Assign Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    submitBtn.disabled = true;

                    fetch('/admin/products/bulk-assign-package', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                pin_ids: selected,
                                package_id: packageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message ||
                                    `${selected.length} pin berhasil diassign paket!`);
                                bootstrap.Modal.getInstance(document.getElementById('bulkAssignModal'))
                                    .hide();
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                toastr.error(data.message || 'Gagal bulk assign paket');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Terjadi kesalahan saat bulk assign');
                        })
                        .finally(() => {
                            // Restore button
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        });
                }
            });
        });

        function viewUserHistory(userId) {
            Swal.fire({
                title: 'User PIN History',
                html: `
            <div class="text-start">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">PIN Activated</h6>
                            <p class="timeline-info">PIN berhasil diaktifkan oleh user</p>
                            <small class="text-muted">3 hari lalu</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Package Assigned</h6>
                            <p class="timeline-info">Admin assign paket ke user</p>
                            <small class="text-muted">2 hari lalu</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Products Delivered</h6>
                            <p class="timeline-info">Produk berhasil diberikan ke user</p>
                            <small class="text-muted">1 hari lalu</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        History lengkap dalam pengembangan...
                    </small>
                </div>
            </div>
        `,
                width: '600px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#6c757d',
                customClass: {
                    htmlContainer: 'text-start'
                }
            });
        }
    </script>

    <style>
        .table th {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .pin-row:hover {
            background-color: #f8f9fa;
        }

        .package-select {
            min-width: 200px;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }

        .modal-header {
            border-bottom: none;
        }

        .alert-info {
            border-left: 4px solid #0dcaf0;
        }

        #packagePreview {
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .card-tools input {
                width: 100% !important;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .package-select {
                min-width: 150px;
                font-size: 0.875rem;
            }
        }
    </style>
@endsection
