@extends('layouts.app')

@section('content')

<div class="page-inner">
    <h4>Daftar Withdraw Menunggu Pencairan</h4>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>User</th>
                <th>No. Rekening</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Detail Pembayaran</th>
                <th>Catatan Admin</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        @foreach($withdraws as $w)
            <tr id="row-{{ $w->id }}">
                <td>{{ $w->user->name }}</td>
                <td>{{ $w->user->bank_account }}</td> {{-- 💡 Tambahan no rekening --}}
                <td>Rp {{ number_format($w->amount) }}</td>
                <td>{{ $w->payment_channel }}</td>
                <td>{{ $w->payment_details }}</td>
                <td>{{ $w->admin_notes }}</td>
                <td>
                    <button class="btn btn-success process-btn" data-id="{{ $w->id }}">Proses</button>
                </td>   
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

  <meta name="csrf-token" content="{{ csrf_token() }}">
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.process-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;

            Swal.fire({
                title: 'Konfirmasi Transfer',
                input: 'text',
                inputLabel: 'Nomor Referensi Transfer',
                inputPlaceholder: 'Misal: TRF12345678',
                showCancelButton: true,
                confirmButtonText: 'Proses Transfer',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) return 'Nomor referensi wajib diisi!';
                }
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(`/finance/withdraws/${id}/process`, { // ✅ GANTI URL DI SINI
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            transfer_reference: result.value
                        })
                    })
                    .then(async res => {
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const data = isJson ? await res.json() : await res.text();

                        if (!res.ok) {
                            throw new Error(isJson ? (data.message ?? 'Gagal memproses') : 'Respon bukan JSON:\n' + data);
                        }

                        Swal.fire('Sukses', data.message, 'success');
                        document.getElementById(`row-${id}`).remove();
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', err.message || 'Terjadi kesalahan', 'error');
                    });
                }
            });
        });
    });
});

</script>

@endsection
