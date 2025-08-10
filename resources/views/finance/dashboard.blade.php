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

// LINE CHART
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: daily.map(d => d.date),
        datasets: [
            {
                label: 'Pendaftaran Member',
                data: daily.map(d => d.pendaftaran_member),
                borderColor: '#4CAF50',
                fill: false
            },
            {
                label: 'Produk',
                data: daily.map(d => d.produk),
                borderColor: '#2196F3',
                fill: false
            },
            {
                label: 'Manajemen',
                data: daily.map(d => d.manajemen),
                borderColor: '#FFC107',
                fill: false
            },
            {
                label: 'Pairing Bonus',
                data: daily.map(d => d.pairing_bonus),
                borderColor: '#F44336',
                fill: false
            },
            {
                label: 'RO Bonus',
                data: daily.map(d => d.ro_bonus),
                borderColor: '#9C27B0',
                fill: false
            },
            {
                label: 'Reward Poin',
                data: daily.map(d => d.reward_poin),
                borderColor: '#3F51B5',
                fill: false
            },
            {
                label: 'Withdraw',
                data: daily.map(d => d.withdraw),
                borderColor: '#795548',
                fill: false
            },
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// PIE CHART PEMASUKAN
new Chart(document.getElementById('incomePieChart'), {
    type: 'pie',
    data: {
        labels: ['Pendaftaran', 'Produk', 'Manajemen'],
        datasets: [{
            data: [incomePie.pendaftaran_member, incomePie.produk, incomePie.manajemen],
            backgroundColor: ['#4CAF50', '#2196F3', '#FFC107']
        }]
    }
});

// PIE CHART PENGELUARAN
new Chart(document.getElementById('expensePieChart'), {
    type: 'pie',
    data: {
        labels: ['Pairing Bonus', 'RO Bonus', 'Reward Poin', 'Withdraw'],
        datasets: [{
            data: [expensePie.pairing_bonus, expensePie.ro_bonus, expensePie.reward_poin, expensePie.withdraw],
            backgroundColor: ['#F44336', '#9C27B0', '#3F51B5', '#795548']
        }]
    }
});
</script>
@endpush
