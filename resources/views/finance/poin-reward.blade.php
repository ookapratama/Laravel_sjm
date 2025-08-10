@extends('layouts.app')

@section('content')
<div class="page-inner">
    <h4 class="page-title">Poin & Status Reward Member</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Total Poin</th>
                    <th>Reward</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->username }}</td>
                    <td>{{ $row->pairing_point }}</td>
                    <td>{{ $row->reward }}</td>
                    <td class="{{ $row->reward !== '-' ? 'text-success' : 'text-danger' }}">
                        {{ $row->status }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
