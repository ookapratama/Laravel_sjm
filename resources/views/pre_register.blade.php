@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4>Aktivasi Member Baru</h4>

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
                @forelse($pending as $pre)
                    <tr data-id="{{ $pre->id }}">
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
                        </td>
                        <td class="status-cell">
                            <span class="badge bg-{{ $pre->status === 'payment_verified' ? 'warning' : 'success' }}">
                                {{ ucfirst($pre->status) }}
                            </span>
                        </td>
                        <td class="action-cell">
                            @if($pre->status === 'payment_verified')
                                <button onclick="approveMember({{ $pre->id }})" class="btn btn-sm btn-primary">Aktivasi</button>
                            @else
                                <span class="text-muted">Sudah Diaktivasi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Fullscreen Image Modal --}}
<div id="imagePreview" class="full-preview-overlay" onclick="this.classList.remove('show')">
    <img src="" id="fullPreviewImage" alt="Preview">
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

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

<script>
    function showPreview(src) {
        document.getElementById('fullPreviewImage').src = src;
        document.getElementById('imagePreview').classList.add('show');
    }
</script>

<script>
    
function approveMember(id) {
    Swal.fire({
        title: 'Aktivasi Akun?',
        text: 'Yakin ingin mengaktifkan akun member ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Aktivasi',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/approve-member/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.querySelector('.status-cell').innerHTML = `<span class="badge bg-success">Approved</span>`;
                        row.querySelector('.action-cell').innerHTML = `<span class="text-muted">Sudah Diaktivasi</span>`;
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
