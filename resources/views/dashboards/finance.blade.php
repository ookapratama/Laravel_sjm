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


<