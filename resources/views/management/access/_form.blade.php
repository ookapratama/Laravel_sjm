<form id="permissionForm" method="POST">
  @csrf
  <input type="hidden" name="role" value="{{ $roleName }}">

  <div class="form-group mt-3">
    <label>Checklist Hak Akses:</label><br>
    <div class="row">
      @foreach($permissions as $permission)
        <div class="col-md-4">
          <label>
            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
              {{ $roleObj->hasPermissionTo($permission->name) ? 'checked' : '' }}>
            {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
          </label>
        </div>
      @endforeach
    </div>
  </div>
  <button class="btn btn-primary mt-3">Simpan Perubahan</button>
</form>
