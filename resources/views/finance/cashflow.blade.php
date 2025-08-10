@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Ringkasan Arus Kas Bulanan</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Bulan</th>
                    <th>Total Pemasukan</th>
                    <th>Total Pengeluaran</th>
                    <th>Saldo Kas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cashflow as $row)
                <tr>
                    <td>{{ $row->bulan }}</td>
                    <td>Rp {{ number_format($row->total_masuk, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->total_keluar, 0, ',', '.') }}</td>
                    <td class="{{ $row->saldo >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($row->saldo, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
