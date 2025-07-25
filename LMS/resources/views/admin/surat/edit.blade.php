@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        <i class="fas fa-edit me-2"></i>
                        Edit Surat: {{ $surat->kode_surat }}
                    </h4>
                    <a href="{{ route('admin.surat.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.surat.update', $surat->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nomor_urut" class="form-label">Nomor Urut *</label>
                                    <input type="number" class="form-control @error('nomor_urut') is-invalid @enderror" 
                                           id="nomor_urut" name="nomor_urut" value="{{ old('nomor_urut', $surat->nomor_urut) }}" required>
                                    @error('nomor_urut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nomor_surat" class="form-label">Nomor Surat (Otomatis)</label>
                                    <input type="text" class="form-control" id="nomor_surat" value="{{ $surat->nomor_surat }}" readonly>
                                    <small class="text-muted">Nomor surat akan di-generate otomatis berdasarkan nomor urut, divisi, dan jenis surat</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="divisi_id" class="form-label">Divisi *</label>
                                    <select class="form-control @error('divisi_id') is-invalid @enderror" id="divisi_id" name="divisi_id" required>
                                        <option value="">Pilih Divisi</option>
                                        @foreach($divisions as $division)
                                            <option value="{{ $division->id }}" {{ old('divisi_id', $surat->divisi_id) == $division->id ? 'selected' : '' }}>
                                                {{ $division->nama_divisi }} ({{ $division->kode_divisi }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('divisi_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jenis_surat_id" class="form-label">Jenis Surat *</label>
                                    <select class="form-control @error('jenis_surat_id') is-invalid @enderror" id="jenis_surat_id" name="jenis_surat_id" required>
                                        <option value="">Pilih Jenis Surat</option>
                                        @foreach($jenisSurat as $jenis)
                                            <option value="{{ $jenis->id }}" {{ old('jenis_surat_id', $surat->jenis_surat_id) == $jenis->id ? 'selected' : '' }}>
                                                {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('jenis_surat_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="perihal" class="form-label">Perihal *</label>
                            <textarea class="form-control @error('perihal') is-invalid @enderror" id="perihal" name="perihal" rows="3" required>{{ old('perihal', $surat->perihal) }}</textarea>
                            @error('perihal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_surat" class="form-label">Tanggal Surat *</label>
                                    <input type="date" class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                           id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', $surat->tanggal_surat ? $surat->tanggal_surat->format('Y-m-d') : '') }}" required>
                                    @error('tanggal_surat')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_diterima" class="form-label">Tanggal Diterima *</label>
                                    <input type="date" class="form-control @error('tanggal_diterima') is-invalid @enderror" 
                                           id="tanggal_diterima" name="tanggal_diterima" value="{{ old('tanggal_diterima', $surat->tanggal_diterima ? $surat->tanggal_diterima->format('Y-m-d') : '') }}" required>
                                    @error('tanggal_diterima')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_private" name="is_private" value="1" {{ old('is_private', $surat->is_private) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_private">
                                    Surat Private (Hanya dapat diakses oleh user yang dipilih)
                                </label>
                            </div>
                        </div>

                        <!-- Private Access Selection -->
                        <div id="private-access-section" class="mb-3" style="display: {{ $surat->is_private ? 'block' : 'none' }};">
                            <label class="form-label">Pilih User yang Dapat Mengakses Surat Ini:</label>
                            <div class="row">
                                @foreach($users as $user)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="selected_users[]" 
                                                   value="{{ $user->id }}" id="user_{{ $user->id }}"
                                                   {{ $surat->accesses->contains('user_id', $user->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="user_{{ $user->id }}">
                                                <strong>{{ $user->full_name }}</strong><br>
                                                <small class="text-muted">{{ $user->username }} - {{ $user->division->nama_divisi ?? 'N/A' }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- File Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informasi File</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>File Path:</strong> {{ $surat->file_path }}</p>
                                        <p><strong>File Size:</strong> {{ number_format($surat->file_size / 1024, 2) }} KB</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>MIME Type:</strong> {{ $surat->mime_type }}</p>
                                        <p><strong>Uploaded by:</strong> {{ $surat->uploader->full_name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <a href="{{ Storage::url($surat->file_path) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Surat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isPrivateCheckbox = document.getElementById('is_private');
    const privateAccessSection = document.getElementById('private-access-section');

    isPrivateCheckbox.addEventListener('change', function() {
        if (this.checked) {
            privateAccessSection.style.display = 'block';
        } else {
            privateAccessSection.style.display = 'none';
        }
    });
});
</script>
@endsection 