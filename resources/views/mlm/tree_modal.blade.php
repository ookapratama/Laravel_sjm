{{-- resources/views/mlm/tree_modal.blade.php --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="userForm" method="POST" action="{{ route('users.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Form Registrasi Member</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="upline_id" name="upline_id">
          <input type="hidden" id="position" name="position">
          <input type="hidden" id="sponsor_id" name="sponsor_id" value="{{ Auth::id() }}">

          <div class="row">
            <div class="col-md-6">
              <div class="mb-2">
                <label>Nama</label>
                <input type="text" name="name" class="form-control form-control-sm" required>
              </div>
              <div class="mb-2">
                <label>Username</label>
                <input type="text" name="username" class="form-control form-control-sm" required>
              </div>
              <div class="mb-2">
                <label>Password</label>
                <input type="password" name="password" class="form-control form-control-sm">
              </div>
              <div class="mb-2">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control form-control-sm" autocomplete="new-password">
              </div>
              <div class="mb-2">
                <label>Tanggal Bergabung</label>
                <input type="datetime-local" name="joined_at" class="form-control form-control-sm">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-2">
                <label>NPWP/NIK</label>
                <input type="text" name="tax_id" class="form-control form-control-sm">
              </div>
              <div class="mb-2">
                <label>Alamat</label>
                <textarea name="address" class="form-control form-control-sm" rows="3"></textarea>
              </div>
              <div class="mb-2">
                <label>Rekening Bank (JSON)</label>
                <textarea name="bank_account" class="form-control form-control-sm" placeholder='{"bank":"BCA","no":"12345678"}'></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Simpan</button>
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>
