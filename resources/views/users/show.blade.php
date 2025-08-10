@extends('layouts.app')

@section('content')
<div class="p-4">
    <h2 class="text-xl font-bold mb-4">Detail User</h2>
    <ul class="space-y-2 text-sm">
        <li><strong>Nama:</strong> {{ $user->name }}</li>
        <li><strong>Username:</strong> {{ $user->username }}</li>
        <li><strong>Status:</strong> {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}</li>
        <li><strong>Position:</strong> {{ $user->position }}</li>
    </ul>
</div>
@endsection
