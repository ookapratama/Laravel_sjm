@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Top 10 Member Dengan Bonus Terbesar</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="bg-success text-white">
                <tr>
                    <th>Rank</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Total Bonus</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->user->name ?? '-' }}</td>
                    <td>{{ $row->user->username ?? '-' }}</td>
                    <td>Rp {{ number_format($row->total_bonus, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
