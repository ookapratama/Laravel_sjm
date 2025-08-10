<div class="space-y-2">
    <p><strong>Nama:</strong> {{ $user->name }}</p>
    <p><strong>Username:</strong> {{ $user->username }}</p>
    <p><strong>Status:</strong> {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}</p>
    <p><strong>Position:</strong> {{ ucfirst($user->position) }}</p>
    <p><strong>Level:</strong> {{ $user->level }}</p>
    <p><strong>Downline Kiri:</strong> {{ $user->kiri_count }}</p>
    <p><strong>Downline Kanan:</strong> {{ $user->kanan_count }}</p>
</div>
