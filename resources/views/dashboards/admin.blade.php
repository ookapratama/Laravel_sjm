@extends('layouts.app')

@section('content')
@php
    $baganNames = [
        1 => 'Starter',
        2 => 'Booster',
        3 => 'Growth',
        4 => 'Champion',
        5 => 'Legacy',
    ];
    $biaya = [
        1 => 1500000,
        2 => 3000000,
        3 => 6000000,
        4 => 12000000,
        5 => 24000000,
    ];
      $userBaganAktif = collect($userBagans)
        ->filter(fn($b) => $b->is_active)
        ->pluck('bagan')
        ->toArray();
       
        @endphp

<div class="page-inner">
          
        <div class="row">
              <div class="col-md-4">
                <div class="card card-secondary">
                  <div class="card-body skew-shadow">
                    <h1>  Rp. {{ number_format($totalBonusnett, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Bonus Net</h5>
                    <div class="pull-right">
                      <h3 class="fw-bold op-8"></h3>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card card-secondary bg-secondary-gradient">
                  <div class="card-body bubble-shadow">
                    <h1>Rp. {{ number_format($totalBonus, 0, ',', '.') }}</h1>
                    <h5 class="op-8">Total Bonus </h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card card-secondary bg-secondary-gradient">
                  <div class="card-body curves-shadow">
                    <h1>{{$leftDownline+$rightDownline}}</h1>
                    <h5 class="op-8">Jumlah Downline</h5>

                  </div>
                </div>
              </div>
            </div>
            
<div class="row">
  <div class="col-md-6">
    <div class="card card-black">
      <div class="card-body">
        <h4 class="text-center">Downline Kiri <strong>{{ $leftDownline }}</strong></h4>
        <p class="text-center">
          {{ optional($user->getLeftChild())->name ?? 'Belum Ada' }}
        </p>
        <p class="text-center">
          {{ optional($user->getLeftChild())->username ?? 'Belum Ada' }}
        </p>
        
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card card-black">
      <div class="card-body">
        <h4 class="text-center">Downline Kanan <strong>{{ $rightDownline }}</strong></h4>
      </h3><p class="text-center">
          {{ optional($user->getRightChild())->name ?? 'Belum Ada' }}
        </p>
        <p class="text-center">
          {{ optional($user->getRightChild())->username ?? 'Belum Ada' }}
        </p>
        
      </div>
    </div>
  </div>
</div>

<div class="card card-black">
    <div class="card-header text-white">
        <h4 class="mb-0">🔰 Status Bagan & Upgrade</h4>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach ($baganNames as $bagan => $label)
             @php $allocated = optional(collect($userBagans)->firstWhere('bagan', $bagan))->allocated_from_bonus ?? 0;@endphp
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Bagan {{ $bagan }}: {{ $label }}</strong>
                        <br>
                        <small>Biaya: Rp{{ number_format($biaya[$bagan], 0, ',', '.') }}</small>
                    </div>

                    @if(in_array($bagan, $userBaganAktif))
          <span class="badge badge-success">✅ Aktif</span>
        @else
          <button type="button" class="btn btn-warning btn-sm mt-2"
                    data-bagan="{{ $bagan }}"
                    data-nama="{{ $label }}"
                    data-biaya="{{ $biaya[$bagan] }}"
                    data-allocated="{{ $allocated }}"
                    onclick="openUpgradeModal(this)">
                Upgrade ke Bagan {{ $bagan }}
            </button>
        @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formUpgradeBagan">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-black">
          <h5 class="modal-title">Upgrade Bagan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
          <div class="modal-body">
            <input type="hidden" id="baganId" name="bagan">
            <p>Yakin ingin upgrade ke <strong id="namaBagan">Bagan X</strong>?</p>
            <p>Biaya: <strong id="biayaBagan">Rp</strong></p>
            
            <div id="bonusInfo" class="mt-3">
              <p>Bonus tertahan: <strong id="allocatedBonusText"></strong></p>
              <p>Sisa yang harus dibayar: <strong id="sisaPembayaranText"></strong></p>
              <p>Saldo bonus tersedia: <strong>Rp{{ number_format($saldoBonusTersedia, 0, ',', '.') }}</strong></p>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="metode_pembayaran" value="bonus" id="pakaiBonus" checked>
              <label class="form-check-label" for="pakaiBonus">Gunakan saldo bonus</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="metode_pembayaran" value="transfer" id="pakaiTransfer">
              <label class="form-check-label" for="pakaiTransfer">Transfer manual</label>
            </div>

            <div id="manualTransferSection" style="display: none;">
            <hr>
            <p class="text-danger">Saldo tidak mencukupi. Silakan transfer manual ke rekening berikut:</p>
            <p><strong>Bank Mandiri</strong> - 1740011176609 a.n. PT Sair Jaya Mandiri</p>

            <div class="form-group">
                <label for="buktiTransfer">Upload Bukti Transfer</label>
                <input type="file" name="bukti_transfer" id="buktiTransfer" class="form-control" accept="image/*">
            </div>
        </div>
          </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Upgrade Sekarang</button>
        </div>
      </div>
    </form>
  </div>
</div>
</div>
@stack('script')
<script>
function openUpgradeModal(button) {
   const bagan = button.getAttribute('data-bagan');
    const nama = button.getAttribute('data-nama');
    const biaya = parseInt(button.getAttribute('data-biaya'));
    const allocated = parseInt(button.getAttribute('data-allocated') || 0);
    const sisa = biaya - allocated;

    document.getElementById('baganId').value = bagan;
    document.getElementById('namaBagan').innerText = 'Bagan ' + bagan + ' - ' + nama;
    document.getElementById('biayaBagan').innerText = 'Rp' + biaya.toLocaleString();

    document.getElementById('allocatedBonusText').innerText = 'Rp' + allocated.toLocaleString();
    document.getElementById('sisaPembayaranText').innerText = 'Rp' + sisa.toLocaleString();

    new bootstrap.Modal(document.getElementById('upgradeModal')).show();
}
document.querySelectorAll('input[name="metode_pembayaran"]').forEach(el => {
    el.addEventListener('change', function () {
        toggleTransferSection();
    });
});

function toggleTransferSection(forceTransfer = false) {
    const selected = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
    const section = document.getElementById('manualTransferSection');

    if (selected === 'transfer' || forceTransfer) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';

        // ⛔ Hapus file jika sebelumnya pernah diisi
        const fileInput = document.getElementById('buktiTransfer');
        if (fileInput) fileInput.value = ''; // Reset file input
    }
}


document.getElementById('formUpgradeBagan').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = this;
    const bagan = document.getElementById('baganId').value;

    // Buat FormData manual
    const formData = new FormData();
    formData.append('_token', form.querySelector('[name="_token"]').value);
    formData.append('bagan', bagan);

    const metode = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
    formData.append('metode_pembayaran', metode);

    if (metode === 'transfer') {
        const fileInput = document.getElementById('buktiTransfer');
        if (fileInput?.files[0]) {
            formData.append('bukti_transfer', fileInput.files[0]);
        }
    }


    if (metode === 'bonus') {
        fetch(`/member/bagan/cek-saldo/${bagan}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
.then(data => {
    console.log('💬 Response dari cek-saldo:', data); // <== Tambahkan ini
    if (data.success) {
        console.log('🔁 Memanggil kirimUpgrade...');
        kirimUpgrade(formData, bagan);
    } else {
        toastr.warning(data.message);
        toggleTransferSection(true);
        document.getElementById('pakaiTransfer').checked = true;
    }
})
        .catch(() => toastr.error('Terjadi kesalahan validasi saldo.'));
    } else {
      console.log('🔁 Memanggil kirimUpgrade...');
        kirimUpgrade(formData, bagan);
    }
});


function kirimUpgrade(formData, bagan) {
    console.log('🚀 [kirimUpgrade] Mulai kirim ke /member/bagan/upgrade/' + bagan);

    fetch(`/member/bagan/upgrade/${bagan}`, {
        method: 'POST',
        body: formData,
    })
    .then(async res => {
        console.log('📥 [kirimUpgrade] Respon diterima', res);

        if (!res.ok) {
            const data = await res.json();
            console.error('❌ [kirimUpgrade] Gagal:', data);
            toastr.error(Object.values(data.errors || {})[0][0] || 'Upgrade gagal.');
            return;
        }

        return res.json();
    })
    .then(data => {
        console.log('✅ [kirimUpgrade] Respon JSON:', data);
        if (data?.success) {
            toastr.success('Upgrade berhasil!');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(data?.message || 'Upgrade gagal.');
        }
    })
    .catch(err => {
        console.error('⚠️ [kirimUpgrade] Error:', err);
        toastr.error('Terjadi kesalahan saat upgrade.');
    });
}

</script>
@endsection
