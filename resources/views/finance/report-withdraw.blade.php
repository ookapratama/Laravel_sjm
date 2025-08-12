@extends('layouts.app')

@section('content')
    <div class="page-inner">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4>History Withdrawal Member</h4>
                <p class="text-muted">Riwayat transaksi withdrawal untuk analisis dan verifikasi</p>
            </div>
            <div>
                {{-- <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchUserModal">
                    <i class="fas fa-search"></i> Cari Member
                </button> --}}
            </div>
        </div>

        <!-- User Info Card (akan tampil setelah user dipilih) -->
        <div id="userInfoCard" class="card mb-4" style="display: none;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user"></i> Informasi Member
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="userDetails">
                            <!-- User details will be loaded here -->
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <button class="btn btn-info btn-sm" onclick="refreshHistory()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="hideUserInfo()">
                                <i class="fas fa-times"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats Card -->
        <div id="summaryCard" class="card mb-4" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> Ringkasan Withdrawal
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="border rounded p-3 bg-success text-white">
                            <h4 id="approvedCount">0</h4>
                            <small>Approved</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border rounded p-3 bg-danger text-white">
                            <h4 id="rejectedCount">0</h4>
                            <small>Rejected</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border rounded p-3 bg-warning text-white">
                            <h4 id="pendingCount">0</h4>
                            <small>Pending</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-primary text-white">
                            <h5 id="totalApprovedAmount">Rp 0</h5>
                            <small>Total Approved</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-secondary text-white">
                            <h5 id="totalRejectedAmount">Rp 0</h5>
                            <small>Total Rejected</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div id="historyCard" class="card" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> Riwayat Withdrawal (20 Terakhir)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Tanggal Proses</th>
                                <th>Referensi Transfer</th>
                                <th>Catatan Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- History data will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <div id="noHistoryMessage" class="text-center text-muted py-5" style="display: none;">
                    <i class="fas fa-inbox fa-3x mb-3"></i><br>
                    Member ini belum pernah melakukan withdrawal
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Search User -->
    <div class="modal fade" id="searchUserModal" tabindex="-1" aria-labelledby="searchUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchUserModalLabel">Cari Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="searchInput" class="form-label">Cari berdasarkan nama, email, atau member ID:</label>
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="Ketik untuk mencari...">
                            <button class="btn btn-primary" onclick="searchUsers()">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </div>

                    <div id="searchResults">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-search fa-2x mb-2"></i><br>
                            Masukkan kata kunci untuk mencari member
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('scripts')
    <script>
        let currentUserId = null;
        let currentMode = 'all'; // 'all' or 'user'

        document.addEventListener('DOMContentLoaded', () => {
            // Enter key search
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchUsers();
                }
            });

            // Load data awal saat halaman dimuat
            loadAllWithdrawals();
            // loadTodayStats();
        });

        // Function to load all withdrawals (default view)
        function loadAllWithdrawals() {
            currentMode = 'all';
            currentUserId = null;
            console.log('load tabel')

            // Hide user-specific cards
            document.getElementById('userInfoCard').style.display = 'none';
            document.getElementById('summaryCard').style.display = 'none';

            // Show main table
            document.getElementById('historyCard').style.display = 'block';
            // document.getElementById('historyTitle').textContent = 'Riwayat Withdrawal Terbaru';

            // Show loading
            document.getElementById('historyTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading semua withdrawal...
                    </td>
                </tr>
            `;

            // const status = document.getElementById('statusFilter').value;
            // const limit = document.getElementById('limitFilter').value;
            // const date = document.getElementById('dateFilter').value;

            let url = `/finance/all-withdrawals?limit=${20}`;
            // if (status) url += `&status=${status}`;
            // if (date) url += `&date=${date}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAllWithdrawals(data.withdrawals);
                        // document.getElementById('totalRecords').textContent = data.withdrawals.length;
                    } else {
                        showAlert('danger', 'Gagal memuat data withdrawal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Terjadi kesalahan saat memuat data');
                });
        }

        // Function to display all withdrawals in table
        function displayAllWithdrawals(withdrawals) {
            const tbody = document.getElementById('historyTableBody');

            if (withdrawals.length > 0) {
                let html = '';
                console.log(withdrawals)
                withdrawals.forEach(item => {
                    const statusBadge = getStatusBadge(item.status);
                    const processedDate = item.processed_at ? new Date(item.processed_at).toLocaleString('id-ID') :
                        '-';

                    html += `
                        <tr>
                            <td><strong>#${item.withdrawal_id}</strong></td>
                            <td>
                                <strong>${item.user_name}</strong><br>
                                <small class="text-muted">${item.user_email}</small><br>
                                <small class="text-info">ID: ${item.member_id || item.user_id}</small>
                                <br>
                                <strong>${formatRupiah(item.amount)}</strong>
                            </td>
                            <td>${statusBadge}</td>
                            <td>${new Date(item.withdrawal_date).toLocaleString('id-ID')}</td>
                            <td>${item.processed_at ? new Date(item.processed_at).toLocaleString('id-ID') : '-'}</td>
                            <td>
                                <code>${item.transfer_reference || '-'}</code>
                            </td>
                            <td>
                                ${item.admin_notes || '-'}
                            </td>
                            <td>
                                <div class="btn-group-vertical">
                                    <button class="btn btn-info btn-sm" onclick="showWithdrawalDetail(${item.withdrawal_id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    </div>
                                    </td>
                                    </tr>
                                    `;
                });
                // <button class="btn btn-primary btn-sm" onclick="viewUserHistory(${item.user_id}, '${item.user_name}', '${item.user_email}', '${item.member_id || ''}')">
                //     <i class="fas fa-user"></i>
                // </button>
                tbody.innerHTML = html;
                document.getElementById('noHistoryMessage').style.display = 'none';
            } else {
                tbody.innerHTML = '';
                document.getElementById('noHistoryMessage').style.display = 'block';
                document.getElementById('noDataText').textContent = 'Tidak ada data withdrawal sesuai filter';
            }
        }

        // Function to load today's statistics
        // function loadTodayStats() {
        //     fetch('/finance/today-stats')
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data.success) {
        //                 document.getElementById('todayPending').textContent = data.stats.pending || 0;
        //                 document.getElementById('todayApproved').textContent = data.stats.approved || 0;
        //                 document.getElementById('todayRejected').textContent = data.stats.rejected || 0;
        //             }
        //         })
        //         .catch(error => {
        //             console.error('Error loading today stats:', error);
        //         });
        // }

        // Function to apply filters
        function applyFilters() {
            if (currentMode === 'all') {
                loadAllWithdrawals();
            } else if (currentUserId) {
                loadWithdrawalHistory(currentUserId);
            }
        }

        // Function to view specific user history (dari button di table)
        function viewUserHistory(userId, userName, userEmail, memberId) {
            selectUser(userId, userName, userEmail, memberId);
        }

        function searchUsers() {
            const searchTerm = document.getElementById('searchInput').value.trim();

            if (searchTerm.length < 2) {
                showAlert('warning', 'Masukkan minimal 2 karakter untuk pencarian');
                return;
            }

            const resultsDiv = document.getElementById('searchResults');
            resultsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Mencari...</div>';

            fetch(`/finance/users/search?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        let html = '<div class="list-group">';

                        data.users.forEach(user => {
                            html += `
                                <a href="#" class="list-group-item list-group-item-action" onclick="selectUser(${user.id}, '${user.name}', '${user.email}', '${user.member_id || ''}')">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${user.name}</h6>
                                        <small class="text-muted">ID: ${user.member_id || user.id}</small>
                                    </div>
                                    <p class="mb-1">${user.email}</p>
                                    <small>Total Withdrawals: ${user.withdrawal_count || 0}</small>
                                </a>
                            `;
                        });

                        html += '</div>';
                        resultsDiv.innerHTML = html;
                    } else {
                        resultsDiv.innerHTML = `
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-user-slash fa-2x mb-2"></i><br>
                                Tidak ditemukan member dengan kata kunci: <strong>${searchTerm}</strong>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Terjadi kesalahan saat mencari member
                        </div>
                    `;
                });
        }

        // Function to select user and load history
        function selectUser(userId, userName, userEmail, memberId) {
            currentUserId = userId;

            // Close search modal
            // const searchModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('searchUserModal'));
            // searchModal.hide();
            console.log(searchModal)

            // Show user info
            document.getElementById('userDetails').innerHTML = `
                <h5>${userName}</h5>
                <p class="mb-1"><strong>Email:</strong> ${userEmail}</p>
                <p class="mb-0"><strong>Member ID:</strong> ${memberId || 'Tidak ada'}</p>
            `;

            document.getElementById('userInfoCard').style.display = 'block';

            // Load withdrawal history
            loadWithdrawalHistory(userId);
        }

        // Function to load withdrawal history
        function loadWithdrawalHistory(userId) {
            // Show loading
            document.getElementById('summaryCard').style.display = 'block';
            document.getElementById('historyCard').style.display = 'block';
            document.getElementById('historyTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading history...
                    </td>
                </tr>
            `;

            fetch(`/finance/users/${userId}/withdrawal-history`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update summary
                        const summary = data.summary;
                        document.getElementById('approvedCount').textContent = summary.approved_count || 0;
                        document.getElementById('rejectedCount').textContent = summary.rejected_count || 0;
                        document.getElementById('pendingCount').textContent = summary.pending_count || 0;
                        document.getElementById('totalApprovedAmount').textContent = formatRupiah(summary
                            .total_approved_amount || 0);
                        document.getElementById('totalRejectedAmount').textContent = formatRupiah(summary
                            .total_rejected_amount || 0);

                        // Update history table
                        const history = data.history;
                        const tbody = document.getElementById('historyTableBody');

                        if (history.length > 0) {
                            let html = '';
                            history.forEach(item => {
                                const statusBadge = getStatusBadge(item.status);
                                const processedDate = item.processed_at ? new Date(item.processed_at)
                                    .toLocaleString('id-ID') : '-';

                                html += `
                                    <tr>
                                        <td><strong>#${item.id}</strong></td>
                                        <td><strong>${formatRupiah(item.amount)}</strong></td>
                                        <td>${statusBadge}</td>
                                        <td>${new Date(item.created_at).toLocaleString('id-ID')}</td>
                                        <td>${processedDate}</td>
                                        <td>${item.processed_by_name || '-'}</td>
                                        <td>
                                            <code>${item.transfer_reference || '-'}</code>
                                        </td>
                                        <td>
                                            <small>${item.admin_notes || '-'}</small>
                                        </td>
                                        <td>
                                            <button class="btn btn-info btn-sm" onclick="showWithdrawalDetail(${item.id})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            tbody.innerHTML = html;
                            document.getElementById('noHistoryMessage').style.display = 'none';
                        } else {
                            tbody.innerHTML = '';
                            document.getElementById('noHistoryMessage').style.display = 'block';
                        }
                    } else {
                        showAlert('danger', 'Gagal memuat history withdrawal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Terjadi kesalahan saat memuat data');
                });
        }

        // Function to show withdrawal detail
        function showWithdrawalDetail(withdrawalId) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalBody = document.getElementById('detailModalBody');

            modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            modal.show();

            fetch(`/finance/withdraws/${withdrawalId}/detail`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const withdrawal = data.data;
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informasi Withdrawal</h6>
                                    <table class="table table-sm">
                                        <tr><td>ID:</td><td><strong>#${withdrawal.id}</strong></td></tr>
                                        <tr><td>Jumlah:</td><td><strong>${formatRupiah(withdrawal.amount)}</strong></td></tr>
                                        <tr><td>Pajak:</td><td>${formatRupiah(withdrawal.tax || 0)}</td></tr>
                                        <tr><td>Net Amount:</td><td><strong>${formatRupiah(withdrawal.net_amount || withdrawal.amount)}</strong></td></tr>
                                        <tr><td>Status:</td><td>${getStatusBadge(withdrawal.status)}</td></tr>
                                        <tr><td>Tanggal Pengajuan:</td><td>${new Date(withdrawal.created_at).toLocaleString('id-ID')}</td></tr>
                                        ${withdrawal.processed_at ? `<tr><td>Tanggal Proses:</td><td>${new Date(withdrawal.processed_at).toLocaleString('id-ID')}</td></tr>` : ''}
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Informasi Rekening</h6>
                                    <table class="table table-sm">
                                        <tr><td>Bank:</td><td>${withdrawal.nama_bank || '-'}</td></tr>
                                        <tr><td>No. Rekening:</td><td><code>${withdrawal.nomor_rekening || '-'}</code></td></tr>
                                        <tr><td>Nama Rekening:</td><td>${withdrawal.nama_rekening || '-'}</td></tr>
                                        <tr><td>Referensi Transfer:</td><td><code>${withdrawal.transfer_reference || '-'}</code></td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            ${withdrawal.admin_notes ? `
                                                <div class="mt-3">
                                                    <h6>Catatan Admin</h6>
                                                    <div class="alert alert-light">${withdrawal.admin_notes}</div>
                                                </div>
                                                ` : ''}
                        `;
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat detail withdrawal</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data</div>';
                });
        }

        // Function to refresh history
        function refreshUserHistory() {
            if (currentUserId) {
                loadWithdrawalHistory(currentUserId);
                showAlert('success', 'History berhasil diperbarui');
            }
        }

        // Function to hide user info
        function hideUserInfo() {
            document.getElementById('userInfoCard').style.display = 'none';
            document.getElementById('summaryCard').style.display = 'none';
            currentUserId = null;
            currentMode = 'all';

            // Kembali ke view semua withdrawal
            loadAllWithdrawals();
        }

        function History() {
            if (currentUserId) {
                loadWithdrawalHistory(currentUserId);
                showAlert('success', 'History berhasil diperbarui');
            }
        }

        // Function to hide user info
        function hideUserInfo() {
            document.getElementById('userInfoCard').style.display = 'none';
            document.getElementById('summaryCard').style.display = 'none';
            document.getElementById('historyCard').style.display = 'none';
            currentUserId = null;
        }

        // Utility functions
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                'approved': '<span class="badge bg-success">Approved</span>',
                'rejected': '<span class="badge bg-danger">Rejected</span>',
                'processed': '<span class="badge bg-primary">Processed</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
        }

        function showAlert(type, message) {
            // Simple alert using SweetAlert2 if available, or browser alert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'danger' ? 'error' : type,
                    title: message,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                alert(message);
            }
        }
    </script>

    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }

        .table td {
            vertical-align: middle;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .border.rounded {
            border-radius: 0.375rem !important;
        }

        .bg-success,
        .bg-danger,
        .bg-warning,
        .bg-primary,
        .bg-secondary {
            border-radius: 0.375rem;
        }

        .modal-lg {
            max-width: 900px;
        }

        code {
            background-color: #f8f9fa;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
        }
    </style>
@endsection
