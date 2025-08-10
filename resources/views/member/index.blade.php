@extends('layouts.app')

@section('title', 'Penarikan Bonus')

@section('content')
<div class="page-inner">
  <h3 class="mb-4">Penarikan Bonus</h3>

  {{-- Ringkasan Bonus --}}
  <div class="row">
              <div class="col-sm-6 col-md-4">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                          <i class="fas fa-wallet"></i>
                        </div>
                      </div>
                    <div class="col col-stats">
                      <div class="numbers">
                        <p class="card-category">Bonus Tersedia</p>
                        <h4 class="card-title">Rp <span id="bonus-available">0</span></h4>
                      </div>
                    </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-md-4">
                <div class="card card-stats card-round" role="button" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-secondary bubble-shadow-small">
                          <i class="fas fa-paper-plane"
                               data-bs-toggle="tooltip"
                               title="Ajukan pencairan bonus"
                               data-bs-placement="top"
                               data-bs-target="#withdrawModal"
                               style="cursor: pointer;"
                               data-bs-toggle="modal"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <h4 class="card-title">Ajukan Pencairan</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
  {{-- Form Pengajuan Withdraw --}}
<!-- Modal Penarikan Bonus -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="withdraw-form" autocomplete="off">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="withdrawModalLabel">Ajukan Penarikan Bonus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="amount" class="form-label">Jumlah Penarikan <span class="text-danger">*</span></label>
            <input type="number" name="amount" id="amount" class="form-control" placeholder="Contoh: 50000" min="50000" required>
            <div class="form-text">Minimal penarikan Rp50.000</div>
          </div>

          <div class="mb-3">
            <label for="payment_channel" class="form-label">Metode Penarikan</label>
            <input type="text" name="payment_channel" id="payment_channel" class="form-control" required value="{{ $mitraProfile->nama_bank ?? '' }}" readonly>
          </div>

          <div class="mb-3">
            <label for="payment_details" class="form-label">No Rekening </label>
            <input type="text" name="payment_details" id="payment_details" class="form-control" required value="{{ $mitraProfile->nomor_rekening ?? '' }}" readonly>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Catatan Tambahan (Opsional)</label>
            <textarea name="notes" id="notes" rows="2" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button id="submitBtn" type="submit" class="btn btn-success">
            <i class="fas fa-paper-plane"></i> Ajukan Penarikan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


  {{-- Histori Withdraw --}}
  <div class="card">
    <div class="card-header">
      <h4>Riwayat Penarikan</h4>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr class="table-dark text-center">
            <th>Tanggal</th>
            <th>Jumlah</th>
            <th>Biaya Admin</th>
            <th>Total</th>
            <th>Status</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody id="withdraw-history-body">
          @forelse ($withdrawals as $w)
          <tr class="text-center">
            <td>{{ $w->created_at->format('d M Y H:i') }}</td>
            <td>Rp. {{ number_format($w->amount, 0, ',', '.') }}</td>
            <td>Rp. {{ number_format($w->tax, 0, ',', '.') }}</td>
            <td><strong>Rp. {{ number_format(($w->amount - $w->tax), 0, ',', '.') }}</strong></td>
            
            <td>
              @if($w->status == 'approved')
                  <span class="badge bg-success">Selesai, sudah ditransfer</span>
              @elseif($w->status == 'menunggu')
                  <span class="badge bg-primary">Menunggu pencairan</span>
              @elseif($w->status == 'rejected')
                  <span class="badge bg-danger">Ditolak admin</span>
              @else
                  <span class="badge bg-warning text-dark">Menunggu persetujuan</span>
              @endif
            </td>
            <td>{{ $w->admin_notes ?? '-' }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted">Belum ada riwayat penarikan.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@stack('script')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('withdraw-form');
  const btn = document.getElementById('submitBtn');
  const amountInput = document.getElementById('amount');
  let availableBonus = 0;

  // Ambil bonus saat pertama kali
  fetchBonusAvailable();

  form.addEventListener('submit', function(e) {
    e.preventDefault(); // 🚫 cegah reload

    const amount = parseFloat(amountInput.value);

    // ✅ Validasi frontend
    if (isNaN(amount) || amount < 50000) {
      Swal.fire('Oops!', 'Jumlah minimal penarikan adalah Rp50.000', 'warning');
      return;
    }

    if (amount > availableBonus) {
     
      Swal.fire('Oops!', 'Jumlah penarikan melebihi saldo bonus Anda.', 'warning');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

    const formData = new FormData(form);

    fetch("{{ route('member.withdraw.store') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        Swal.fire('Berhasil!', data.message, 'success');
        fetchBonusAvailable(); // Refresh bonus

        const now = new Date();
        const tanggal = now.toLocaleDateString('id-ID', {
          day: '2-digit', month: 'short', year: 'numeric',
        }) + ' ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        const tax = Math.round(amount * 0.05);
        const net = amount - tax;

        const row = `
        <tr class="text-center">
          <td>${tanggal}</td>
          <td>Rp${amount.toLocaleString('id-ID')}</td>
          <td>Rp${tax.toLocaleString('id-ID')}</td>
          <td><strong>Rp${net.toLocaleString('id-ID')}</strong></td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td>-</td>
        </tr>
        `;
        document.getElementById('withdraw-history-body').insertAdjacentHTML('afterbegin', row);
        form.reset();
        $('#withdrawModal').modal('hide');
      } else {
        Swal.fire('Gagal', data.message ?? 'Terjadi kesalahan', 'warning');
      }
    })
    .catch(error => {
      console.error(error);
      Swal.fire('Error', 'Gagal mengirim data.', 'error');
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Ajukan Penarikan';
    });
  });

  function fetchBonusAvailable() {
    fetch('{{ route('member.withdraw.bonus') }}')
      .then(res => res.json())
      .then(data => {
        const el = document.getElementById('bonus-available');
        if (el) el.innerText = data.bonus;
        availableBonus = parseFloat(data.bonus_raw ?? 0); // backend wajib kirim bonus_raw (angka)

      })
      .catch(err => console.error('Gagal ambil bonus:', err));
  }
});
</script>



@endsection
