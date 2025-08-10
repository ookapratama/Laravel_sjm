@extends('layouts.app')

@section('content')
<div class="page-inner">
  <h4>Manajemen Hak Akses Role</h4>

  {{-- FORM SELECT ROLE: AJAX --}}
  <div class="form-group mb-3">
    <label for="role">Pilih Role:</label>
    <select id="roleSelect" class="form-control w-25 d-inline-block">
      <option value="">-- Pilih Role --</option>
      @foreach($roles as $role)
        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
          {{ ucfirst($role->name) }}
        </option>
      @endforeach
    </select>
  </div>

  {{-- CONTAINER UNTUK FORM PERMISSION --}}
  <div id="permissionFormContainer">
    @if(request('role'))
      @include('management.access._form', [
          'roleName' => request('role'),
          'permissions' => $permissions,
          'roleObj' => $roles->firstWhere('name', request('role'))
      ])
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
  // Handle perubahan select role (GET via AJAX)
  $('#roleSelect').on('change', function () {
    const selectedRole = $(this).val();
    if (!selectedRole) {
      $('#permissionFormContainer').html('');
      return;
    }

    $.get('/management?role=' + selectedRole, function (data) {
      const html = $(data).find('#permissionFormContainer').html();
      $('#permissionFormContainer').html(html);
    });
  });

  // Delegate submit form permission (POST via AJAX)
  $(document).on('submit', '#permissionForm', function (e) {
    e.preventDefault();
    const form = $(this);
    const formData = form.serialize();

    $.post('/management/update', formData, function (res) {
      toastr.success('Hak akses berhasil disimpan.');
    }).fail(function () {
      toastr.error('Gagal menyimpan hak akses.');
    });
  });
});
</script>
@endpush
