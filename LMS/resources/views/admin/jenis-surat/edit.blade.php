@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        <i class="fas fa-edit me-2"></i>
                        Edit Jenis Surat: {{ $jenisSurat->nama_jenis }}
                    </h4>
                    <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.jenis-surat.update', $jenisSurat->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kode_jenis" class="form-label">Kode Jenis *</label>
                                    <input type="text" class="form-control @error('kode_jenis') is-invalid @enderror" 
                                           id="kode_jenis" name="kode_jenis" value="{{ old('kode_jenis', $jenisSurat->kode_jenis) }}" 
                                           maxlength="10" required>
                                    <small class="form-text text-muted">Maksimal 10 karakter</small>
                                    @error('kode_jenis')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_jenis" class="form-label">Nama Jenis *</label>
                                    <input type="text" class="form-control @error('nama_jenis') is-invalid @enderror" 
                                           id="nama_jenis" name="nama_jenis" value="{{ old('nama_jenis', $jenisSurat->nama_jenis) }}" 
                                           maxlength="100" required>
                                    @error('nama_jenis')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                      id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $jenisSurat->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Jenis Surat Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informasi Jenis Surat</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tanggal Dibuat:</strong> {{ $jenisSurat->created_at->format('d/m/Y H:i') }}</p>
                                        <p><strong>Terakhir Update:</strong> {{ $jenisSurat->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> 
                                            @if($jenisSurat->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Tidak Aktif</span>
                                            @endif
                                        </p>
                                        <p><strong>Jumlah Surat:</strong> <span class="badge bg-primary">{{ $jenisSurat->surat_count }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Jenis Surat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 