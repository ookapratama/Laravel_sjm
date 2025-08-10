@extends('layouts.app')
@section('content')
<div class="page-inner">
    <div class="card-header">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">Data Member</h4>
                        <button
                        class="btn btn-primary btn-round ms-auto"
                        data-bs-toggle="modal"
                        data-bs-target="#userModal"
                        >
                        <i class="fa fa-plus"></i>
                        Member Baru
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="multi-filter-select"
                    class="display table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Sponsor</th>
                            <th>Upline</th>
                            <th>Posisi</th>
                            <th>Level</th>
                            <th>Tax ID</th>
                            <th>rekening Bank</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr data-id="{{ $user->id }}">
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->sponsor->username ?? '-' }}</td>
                            <td>{{ $user->upline->username ?? '-' }}</td>
                            <td>{{ ucfirst($user->position) }}</td>
                            <td>{{ $user->level }}</td>
                            <td>{{ $user->tax_id }}</td>
                            <td>{{ $user->bank_account}}</td>
                            <td>{{ $user->address }}</td>
                            <td>
                                <button class="btn btn-sm btn-info editUser" onClick="editUser({{ $user->id }}) "><i class="fas fa-user-edit"></i></button>
                                <button class="btn btn-sm btn-danger deleteUser" onClick="destroy({{ $user->id }}) "><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</div>

{{-- Modal --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">

        <form id="userForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Registrasi Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <input type="hidden" id="userId" name="id">

                                    <div class="mb-2">
                                        <label>Nama</label>
                                        <input type="text" name="name" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Username</label>
                                        <input type="text" name="username" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Password</label>
                                        <input type="password" name="password" class="form-control form-control-sm">
                                    </div>
                                    <div class="mb-2">
                                        <label>Konfirmasi Password</label>
                                        <input type="password" name="password_confirmation"class="form-control form-control-sm">

                                    </div>
                                    <div class="mb-2">
                                        <label>Sponsor</label>
                                        <select name="sponsor_id" class="form-control form-control-sm">
                                            <option value="1">-</option>
                                            @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->username }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Upline</label>
                                        <select name="upline_id" class="form-control form-control-sm">
                                            <option value="">-</option>
                                            @foreach($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->username }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <div class="mb-2">
                                        <label>Posisi</label>
                                        <select name="position" class="form-control form-control-sm">
                                            <option value="">Pilih (otomatis)</option>
                                            <option value="left">Left</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Level</label>
                                        <input type="number" name="level" class="form-control form-control-sm " value="1">
                                    </div>
                                    <div class="mb-2">
                                        <label>Tanggal Bergabung</label>
                                        <input type="datetime-local" name="joined_at" class="form-control form-control-sm">
                                    </div>
                                    <div class="mb-2">
                                        <label>NPWP/NIK</label>
                                        <input type="text" name="tax_id" class="form-control form-control-sm">
                                    </div>
                                    <div class="mb-2">
                                        <label>Alamat</label>
                                        <textarea name="address" class="form-control form-control-sm"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label>Rekening Bank (JSON)</label>
                                        <textarea name="bank_account" class="form-control form-control-sm" placeholder='{"bank":"BCA","no":"12345678"}'></textarea>
                                    </div>
                                </div>
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

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script>
    function editUser(id){
   

   $.get(`/data-member/${id}/edit`, function (data) {
// Set nilai ke form
$('#userForm')[0].reset(); // clear sebelumnya
$('#userId').val(data.id);
$('input[name="name"]').val(data.name);
$('input[name="username"]').val(data.username);
$('input[name="email"]').val(data.email);
$('select[name="sponsor_id"]').val(data.sponsor_id);
$('select[name="upline_id"]').val(data.upline_id);
$('select[name="position"]').val(data.position);
$('input[name="level"]').val(data.level);
$('input[name="joined_at"]').val(data.joined_at?.slice(0, 16));
$('input[name="tax_id"]').val(data.tax_id);
$('textarea[name="address"]').val(data.address);
$('textarea[name="bank_account"]').val(data.bank_account);

$('#userModal').modal('show');
}).fail(function () {
    Swal.fire('Gagal', 'Data tidak ditemukan!', 'error');
});

}

    function destroy(id){
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/data-member/${id}`,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Data berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    },
                    error: function (err) {
                        console.error(err);
                        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus.', 'error');
                    }
                });
            }
        });
    }
    $(document).ready(function () {

        $("#multi-filter-select").DataTable({
            pageLength: 10,
            initComplete: function () {
                this.api()
                .columns()
                .every(function () {
                    var column = this;
                    var select = $(
                        '<select class="form-select"><option value=""></option></select>'
                        )
                    .appendTo($(column.footer()).empty())
                    .on("change", function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());

                        column
                        .search(val ? "^" + val + "$" : "", true, false)
                        .draw();
                    });

                    column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                        select.append(
                            '<option value="' + d + '">' + d + "</option>"
                            );
                    });
                });
            },
        });
        $('#addUser').click(function () {
            $('#userForm')[0].reset();
            $('#userModal').modal('show');
        });

        //add member
        $('#userForm').submit(function (e) {
            e.preventDefault();
            let id = $('#userId').val();
            let url = id ? `/data-member/${id}` : '/data-member';

            let formData = $(this).serializeArray();
            if (id) {
formData.push({ name: '_method', value: 'PUT' }); // spoofing PUT
}

// Clear previous error styles
$('#userForm input, #userForm select, #userForm textarea').removeClass('is-invalid');

$.ajax({
    url: url,
    method: 'POST',
    data: formData,
    success: function () {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data user berhasil disimpan.',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            location.reload();
        });
    },
    error: function (xhr) {
        console.log("Status:", xhr.status);
        console.log("ResponseText:", xhr.responseText);
        console.log("ResponseJSON:", xhr.responseJSON);

        if (xhr.status === 422) {
            let errors = xhr.responseJSON?.errors || {};

// Log error field
console.log("Error Fields:", Object.keys(errors));

// Hapus error sebelumnya
$('#userForm .is-invalid').removeClass('is-invalid');
$('#userForm .invalid-feedback').remove();

let firstField = null;

for (const field in errors) {
    const $field = $(`[name="${field}"]`);

    if (!$field.length) {
        console.warn(`Field "${field}" tidak ditemukan di form!`);
        continue;
    }
    $field.addClass('is-invalid');
// Tambahkan pesan error hanya kalau belum ada
if ($field.next('.invalid-feedback').length === 0) {
    $field.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
}

if (!firstField) firstField = $field;
}

// Scroll & fokus
if (firstField) {
    $('html, body').animate({
        scrollTop: firstField.offset().top - 100
    }, 300);
    firstField.focus();
}

Swal.fire({
    icon: 'error',
    title: 'Validasi Gagal',
    text: 'Periksa data yang ditandai.',
});
} else {
    console.error("Other error:", xhr);
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Kesalahan tidak terduga.',
    });
}
}
});
});



    });
</script>
@endsection
@push('scripts')
@endpush
