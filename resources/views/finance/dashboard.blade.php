@extends('layouts.app')
@section('content')
<div class="page-inner">
    <h4 class="page-title">Statistik Keuangan</h4>
    <div class="row">
        <!-- Grafik Line Harian -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Pemasukan & Pengeluaran Harian</div>
                <div class="card-body">
                    <canvas id="lineChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Pie Chart Pemasukan -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Pemasukan Bulan Ini</div>
                <div class="card-body">
                    <canvas id="incomePieChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Pie Chart Pengeluaran -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Pengeluaran Bulan Ini</div>
                <div class="card-body">
                    <canvas id="expensePieChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
<script>
const daily = @json($daily);
const incomePie = @json($incomePie);
const expensePie = @json($expensePie);

const rupiah = (v) => new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0
}).format(Number(v) || 0);

// LINE CHART
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: daily.map(d => d.date),
    datasets: [
      { label: 'Penjualan Pin', data: daily.map(d => d.penjualan_pin), borderColor: '#4CAF50', fill: false },
      { label: 'Produk',        data: daily.map(d => d.produk),        borderColor: '#2196F3', fill: false },
      { label: 'Manajemen',     data: daily.map(d => d.manajemen),     borderColor: '#FFC107', fill: false },
      { label: 'Pairing Bonus', data: daily.map(d => d.pairing_bonus), borderColor: '#F44336', fill: false },
      { label: 'Withdraw',      data: daily.map(d => d.withdraw),      borderColor: '#795548', fill: false },
    ]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value) => rupiah(value) // ✅ Axis Y ke Rp
        }
      }
    },
    plugins: {
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const label = ctx.dataset.label || '';
            const val = (typeof ctx.parsed?.y === 'number') ? ctx.parsed.y :
                        (typeof ctx.raw === 'number') ? ctx.raw : 0;
            return `${label}: ${rupiah(val)}`; // ✅ Tooltip ke Rp
          }
        }
      }
    }
  }
});

// PIE CHART PEMASUKAN
new Chart(document.getElementById('incomePieChart'), {
  type: 'pie',
  data: {
    labels: ['Penjualan Pin', 'Produk', 'Manajemen'],
    datasets: [{
      data: [incomePie.penjualan_pin, incomePie.produk, incomePie.manajemen],
      backgroundColor: ['#4CAF50', '#2196F3', '#FFC107']
    }]
  },
  options: {
    plugins: {
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const lbl = ctx.label || '';
            const val = (typeof ctx.parsed === 'number') ? ctx.parsed :
                        (typeof ctx.raw === 'number') ? ctx.raw : 0;
            return `${lbl}: ${rupiah(val)}`; // ✅ Tooltip ke Rp
          }
        }
      }
    }
  }
});

// PIE CHART PENGELUARAN
new Chart(document.getElementById('expensePieChart'), {
  type: 'pie',
  data: {
    labels: ['Pairing Bonus', 'Withdraw'],
    datasets: [{
      data: [expensePie.pairing_bonus, expensePie.withdraw],
      backgroundColor: ['#F44336', '#9C27B0']
    }]
  },
  options: {
    plugins: {
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const lbl = ctx.label || '';
            const val = (typeof ctx.parsed === 'number') ? ctx.parsed :
                        (typeof ctx.raw === 'number') ? ctx.raw : 0;
            return `${lbl}: ${rupiah(val)}`; // ✅ Tooltip ke Rp
          }
        }
      }
    }
  }
});
</script>
@endpush
