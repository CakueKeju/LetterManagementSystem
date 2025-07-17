@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit Divisi: {{ $division->nama_divisi }}
                    </h4>
                    <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.divisions.update', $division->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kode_divisi" class="form-label">Kode Divisi *</label>
                                    <input type="text" class="form-control @error('kode_divisi') is-invalid @enderror" 
                                           id="kode_divisi" name="kode_divisi" value="{{ old('kode_divisi', $division->kode_divisi) }}" 
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
                                           id="nama_divisi" name="nama_divisi" value="{{ old('nama_divisi', $division->nama_divisi) }}" 
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
                                      id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $division->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Division Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informasi Divisi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tanggal Dibuat:</strong> 
                                            @if($division->created_at)
                                                {{ $division->created_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                        <p><strong>Terakhir Update:</strong> 
                                            @if($division->updated_at)
                                                {{ $division->updated_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Jumlah Users:</strong> <span class="badge bg-info">{{ $division->users_count }}</span></p>
                                        <p><strong>Jumlah Surat:</strong> <span class="badge bg-primary">{{ $division->surat_count ?? 0 }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Divisi
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