@extends('layouts.app')

@section('title', 'Persetujuan Withdraw')

@section('content')
<div class="page-inner">
  <h3 class="mb-4">Permintaan Withdraw Bonus</h3>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th>Tanggal</th>
          <th>Nama Member</th>
          <th>Jumlah</th>
          <th>Pajak</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($withdraws as $w)
        <tr class="text-center" id="row-{{ $w->id }}">
          <td>{{ $w->created_at->format('d M Y H:i') }}</td>
          <td>{{ $w->user->name }}</td>
          <td>Rp{{ number_format($w->amount, 0, ',', '.') }}</td>
          <td>Rp{{ number_format($w->tax, 0, ',', '.') }}</td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td>
<button class="btn btn-success btn-sm approve-btn" data-id="{{ $w->id }}">✅ Setujui</button>
<button class="btn btn-danger btn-sm reject-btn" data-id="{{ $w->id }}">❌ Tolak</button>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">Belum ada permintaan withdraw.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>


  @stack('script')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const handleAction = (id, action) => {
    const isApprove = action === 'approve';

    Swal.fire({
      title: isApprove ? 'Setujui Withdraw?' : 'Tolak Withdraw?',
      input: 'text',
      inputLabel: 'Catatan Admin',
      inputPlaceholder: 'Contoh: Ditransfer besok / Ditolak karena...',
      inputValue: isApprove ? 'Disetujui oleh admin' : 'Ditolak oleh admin',
      showCancelButton: true,
      confirmButtonText: isApprove ? 'Setujui' : 'Tolak',
      cancelButtonText: 'Batal',
      inputValidator: (value) => {
        if (!value) return 'Catatan wajib diisi!';
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const note = result.value;

        // ✅ Gunakan URL dinamis berdasarkan action (approve / reject)
        fetch(`admin/withdraws/${action}/${id}`, {
          method: 'PUT',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ admin_notes: note })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Berhasil', data.message, 'success');
            document.getElementById(`row-${id}`).remove();
          } else {
            Swal.fire('Gagal', 'Tindakan gagal diproses.', 'error');
          }
        })
        .catch(err => {
          console.error(err);
          Swal.fire('Error', 'Terjadi kesalahan.', 'error');
        });
      }
    });
  };

  // Tombol Approve
  document.querySelectorAll('.approve-btn').forEach(btn => {
    btn.addEventListener('click', () => handleAction(btn.dataset.id, 'approve'));
  });

  // Tombol Reject
  document.querySelectorAll('.reject-btn').forEach(btn => {
    btn.addEventListener('click', () => handleAction(btn.dataset.id, 'reject'));
  });
});

</script>
@endsection
