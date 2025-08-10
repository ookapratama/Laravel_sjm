@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Profil Akun</h5>
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-3">
                        @csrf
                        <div class="text-end me-3">
                            @if ($user->avatar)
                                <img src="{{ asset('storage/avatars/'.$user->avatar) }}" class="rounded-circle shadow-sm" width="60" height="60" alt="avatar">
                            @else
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="rounded-circle shadow-sm" width="60" height="60" alt="avatar">
                            @endif
                        </div>
                        <input type="file" name="avatar" accept="image/*" class="form-control form-control-sm" onchange="validateImage(this)" title="Max 100 KB">
                    </form>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No HP</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $user->address) }}</textarea>
                            </div>
                            <hr class="mt-4">
                            <div class="col-md-6">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $user->bank_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No Rekening</label>
                                <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $user->bank_account) }}">
                            </div>
                            <hr class="mt-4">
                            <div class="col-md-6">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>
                            <div class="col-md-12 mt-3">
                                <button class="btn btn-primary w-100 shadow-sm">💾 Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateImage(input) {
    const file = input.files[0];
    if (file && file.size > 102400) { // 100 KB = 102400 bytes
        alert("Ukuran maksimal avatar adalah 100 KB");
        input.value = "";
    }
}
</script>
@endsection
