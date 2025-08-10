@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Target vs Realisasi Pendaftaran Member</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Bulan</th>
                    <th>Target</th>
                    <th>Realisasi</th>
                    <th>% Tercapai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendaftaran as $row)
                <tr>
                    <td>{{ $row->bulan }}</td>
                    <td>{{ $row->target }}</td>
                    <td>{{ $row->total }}</td>
                    <td class="{{ $row->percent >= 100 ? 'text-success' : 'text-warning' }}">
                        {{ $row->percent }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <h4 class="page-title">Target vs Realisasi Pendaftaran</h4>
    
    <canvas id="targetChart" height="80"></canvas>
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('targetChart').getContext('2d');

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [
                {
                    label: 'Realisasi',
                    data: {!! json_encode($values) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Target',
                    data: {!! json_encode($targets) !!},
                    borderColor: '#dc3545',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endpush