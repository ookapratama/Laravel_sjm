@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4>Verifikasi Pembayaran Member Baru</h4>

    <div class="table-responsive">
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Metode Pembayaran</th>
                    <th>Bukti Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($preRegistrations as $pre)
                    <tr data-id="{{ $pre->id }}">
                        @php $loop @endphp
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $pre->name }}</td>
                        <td>{{ $pre->email }}</td>
                        <td>{{ $pre->phone }}</td>
                        <td>{{ $pre->payment_method }}</td>
                        <td>
                        @if($pre->payment_proof)
                                <img src="{{ asset('storage/' . $pre->payment_proof) }}"
                                     alt="Bukti"
                                     width="140"
                                     class="img-thumbnail"
                                     onclick="showPreview('{{ asset('storage/' . $pre->payment_proof) }}')"
                                     style="cursor:pointer">
                            @else
                                <span class="text-muted">Tidak tersedia</span>
                            @endif
                        <td class="status-cell">
                            <span class="badge bg-{{ $pre->status === 'pending' ? 'warning' : 'success' }}">
                                {{ ucfirst($pre->status) }}
                            </span>
                        </td>
                        <td class="action-cell">
                            @if($pre->status === 'pending')
                                <button onclick="verifyPayment({{ $pre->id }})" class="btn btn-sm btn-success">Verifikasi</button>
                            @else
                                <span class="text-muted">Terverifikasi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div id="imagePreview" class="full-preview-overlay" onclick="this.classList.remove('show')">
    <img src="" id="fullPreviewImage" alt="Preview">
</div>
<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Style --}}
<style>
    .badge {
        font-size: 0.8rem;
        padding: 0.45em 0.75em;
        border-radius: 0.4rem;
    }
    .full-preview-overlay {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(0, 0, 0, 0.85);
        justify-content: center;
        align-items: center;
    }
    .full-preview-overlay img {
        max-width: 90%;
        max-height: 90%;
        border: 4px solid #f5c542;
        border-radius: 12px;
    }
    .full-preview-overlay.show {
        display: flex;
    }
</style>

{{-- Script --}}
<script>
    function showPreview(src) {
        const preview = document.getElementById('imagePreview');
        const img = document.getElementById('fullPreviewImage');
        img.src = src;
        preview.classList.add('show');
    }
</script>
<script>
function verifyPayment(id) {
    Swal.fire({
        title: 'Verifikasi Pembayaran?',
        text: 'Yakin ingin memverifikasi pembayaran ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Verifikasi',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/finance/verify-payment/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // ✅ Update baris tabel secara dinamis
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.querySelector('.status-cell').innerHTML = `<span class="badge bg-success">Verified</span>`;
                        row.querySelector('.action-cell').innerHTML = `<span class="text-muted">Terverifikasi</span>`;
                    }

                      toastr.success(data.message);
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Terjadi kesalahan saat memproses.', 'error');
                console.error(error);
            });
        }
    });
}

</script>
@endsection
