@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Rekap Bonus Pairing vs RO</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Bulan</th>
                    <th>Total Bonus Pairing (1–6)</th>
                    <th>Total Bonus RO (7+)</th>
                    <th>Total Keseluruhan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rekap as $row)
                <tr>
                    <td>{{ $row->bulan }}</td>
                    <td>Rp {{ number_format($row->pairing, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->ro, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($row->pairing + $row->ro, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
