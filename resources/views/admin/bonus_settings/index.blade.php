@extends('layouts.app')

@section('content')
<div class="page-inner">
    <div class="card-body">
        <div class="table-responsive">
            <table id="multi-filter-select"
            class="display table table-striped table-hover">
            <table class="table" id="bonusTable">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>



        </table>
    </div>
</div>

<button class="btn btn-success mt-3" onclick="openBonusModal()">+ Tambah Bonus</button>
<!-- Modal -->
<div class="modal fade" id="bonusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="bonusForm">
            @csrf
            <input type="hidden" name="id" id="bonusId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bonus Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Type</label>
                    <input type="text" name="type" class="form-control" required>
                    <label>Key</label>
                    <input type="text" name="key" class="form-control" required>
                    <label>Value</label>
                    <input type="text" name="value" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
<script>
    
$(document).ready(function () {
    loadBonusSettings();

$('#bonusForm').submit(function(e) {
    e.preventDefault();

    const id = $('#bonusId').val();
    const url = id ? `/bonus-settings/${id}` : '/bonus-settings';
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: 'POST',
        data: $(this).serialize() + (id ? '&_method=PUT' : ''),
        success: function(res) {
            $('#bonusModal').modal('hide');
            loadBonusSettings();

            Swal.fire({
                icon: 'success',
                title: id ? 'Data berhasil diupdate!' : 'Data berhasil ditambahkan!',
                timer: 1500,
                showConfirmButton: false
            });
        },
        error: function() {
            Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
        }
    });
});

});

function loadBonusSettings() {
    $.get('/bonus-settings/json', function (data) {
        let rows = '';
        data.forEach(row => {
            rows += `
                <tr>
                    <td>${row.type}</td>
                    <td>${row.key}</td>
                    <td>${row.value}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editBonus(${row.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBonus(${row.id})">Hapus</button>
                    </td>
                </tr>`;
        });
        $('#bonusTable tbody').html(rows);
    });
}


function openBonusModal() {
    $('#bonusForm')[0].reset();
    $('#bonusId').val('');
    $('#bonusModal').modal('show');
}

function editBonus(id) {
    $.get(`/bonus-settings/${id}`, function (data) {
        $('#bonusId').val(data.id);
        $('[name=type]').val(data.type);
        $('[name=key]').val(data.key);
        $('[name=value]').val(data.value);
        $('#bonusModal').modal('show');
    });
}

function deleteBonus(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Data tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/bonus-settings/${id}`,
                method: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                success: function() {
                    loadBonusSettings();
                    Swal.fire(
                        'Terhapus!',
                        'Data berhasil dihapus.',
                        'success'
                    );
                },
                error: function() {
                    Swal.fire('Gagal!', 'Tidak dapat menghapus data.', 'error');
                }
            });
        }
    });
}

</script>

@endsection
