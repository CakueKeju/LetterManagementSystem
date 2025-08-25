@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload Surat</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('surat.handleUpload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
            <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required>
                <option value="">Pilih Jenis Surat</option>
                @foreach($jenisSurat as $jenis)
                    <option value="{{ $jenis->id }}" data-kode="{{ $jenis->kode_jenis }}">
                        {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                    </option>
                @endforeach
            </select>
            <div class="form-text">Pilih jenis surat untuk menentukan format nomor surat</div>
        </div>
        <div class="mb-3">
            <label for="file" class="form-label">Surat File (DOC, DOCX)</label>
            <input type="file" class="form-control" id="file" name="file" required accept=".doc,.docx">
            <div class="form-text">
                <strong>Format yang didukung:</strong><br>
                • <strong>Word (DOC/DOCX):</strong> Isi nomor surat otomatis pada placeholder<br>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload me-2"></i>Upload
        </button>
    </form>
</div>
@endsection 