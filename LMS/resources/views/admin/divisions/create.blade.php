@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        Tambah Divisi Baru
                    </h4>
                    <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.divisions.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kode_divisi" class="form-label">Kode Divisi *</label>
                                    <input type="text" class="form-control @error('kode_divisi') is-invalid @enderror" 
                                           id="kode_divisi" name="kode_divisi" value="{{ old('kode_divisi') }}" 
                                           maxlength="10" required>
                                    <small class="form-text text-muted">Maksimal 10 karakter</small>
                                    @error('kode_divisi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_divisi" class="form-label">Nama Divisi *</label>
                                    <input type="text" class="form-control @error('nama_divisi') is-invalid @enderror" 
                                           id="nama_divisi" name="nama_divisi" value="{{ old('nama_divisi') }}" 
                                           maxlength="100" required>
                                    @error('nama_divisi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                      id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi') }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Simpan Divisi
                            </button>
                            <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 