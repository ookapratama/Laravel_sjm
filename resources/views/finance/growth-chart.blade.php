@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Grafik Pertumbuhan Member</h4>
    <div class="card">
        <div class="card-body">
            <canvas id="memberGrowthChart" height="100"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
<script>
const ctx = document.getElementById('memberGrowthChart').getContext('2d');
const memberGrowthChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($labels),
        datasets: [{
            label: 'Jumlah Pendaftar',
            data: @json($values),
            borderColor: 'blue',
            backgroundColor: 'rgba(0,123,255,0.1)',
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
