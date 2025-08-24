@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:860px">
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h4 class="mb-3">{{ $invitation->exists ? 'Edit Undangan' : 'Buat Undangan' }}</h4>
      <form method="POST" enctype="multipart/form-data"
            action="{{ $invitation->exists ? route('inv.update',$invitation) : route('inv.store') }}">
        @csrf @if($invitation->exists) @method('PUT') @endif

        <div class="mb-3">
          <label class="form-label">Judul *</label>
          <input class="form-control" name="title" value="{{ old('title',$invitation->title) }}" required>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Waktu</label>
            <input type="datetime-local" class="form-control" name="event_datetime"
                   value="{{ old('event_datetime', optional($invitation->event_datetime)->format('Y-m-d\TH:i')) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Kota</label>
            <input class="form-control" name="city" value="{{ old('city',$invitation->city) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Tempat</label>
            <input class="form-control" name="venue_name" value="{{ old('venue_name',$invitation->venue_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Alamat Tempat</label>
            <input class="form-control" name="venue_address" value="{{ old('venue_address',$invitation->venue_address) }}">
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Tema</label>
            <select class="form-select" name="theme">
             @foreach(['luxury'=>'Gold-Black','royal_marble'=>'Royal Marble','baroque'=>'Baroque Gold Side'] as $k=>$v)
                <option value="{{ $k }}" @selected(old('theme',$invitation->theme)===$k)>{{ $v }}</option>
                @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Warna Utama</label>
            <input class="form-control" name="primary_color" value="{{ old('primary_color',$invitation->primary_color) }}" placeholder="#C9A95D">
          </div>
          <div class="col-md-4">
            <label class="form-label">Warna Sekunder</label>
            <input class="form-control" name="secondary_color" value="{{ old('secondary_color',$invitation->secondary_color) }}" placeholder="#1A1A1A">
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-control" rows="4" name="description">{{ old('description',$invitation->description) }}</textarea>
        </div>

        <div class="mt-3">
          <label class="form-label">Background (opsional)</label>
          <input type="file" class="form-control" name="background">
        </div>

        @if($invitation->exists)
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active" @checked(old('is_active',$invitation->is_active))>
            <label for="active" class="form-check-label">Aktifkan undangan</label>
          </div>
        @endif

        <div class="d-flex justify-content-end mt-4">
          <button class="btn btn-dark">{{ $invitation->exists ? 'Simpan Perubahan' : 'Buat Undangan' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
