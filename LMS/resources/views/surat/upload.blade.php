@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload Surat</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('surat.handleUpload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="nomor_urut" class="form-label">Nomor Urut</label>
            <input type="number" class="form-control" id="nomor_urut" name="nomor_urut" required>
        </div>
        <div class="mb-3">
            <label for="divisi_id" class="form-label">Divisi</label>
            <select class="form-select" id="divisi_id" name="divisi_id" required>
                <option value="">Pilih Divisi</option>
                @foreach($divisions as $divisi)
                    <option value="{{ $divisi->id }}">{{ $divisi->nama_divisi }} ({{ $divisi->kode_divisi }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
            <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required>
                <option value="">Pilih Jenis Surat</option>
                @foreach($jenisSurat as $jenis)
                    <option value="{{ $jenis->id }}">{{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="deskripsi" name="deskripsi" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
            <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_diterima" class="form-label">Tanggal Diterima/Scan</label>
            <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" required>
        </div>
        <div class="mb-3">
            <label for="file" class="form-label">Surat File (PDF, JPG, PNG)</label>
            <input type="file" class="form-control" id="file" name="file" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1">
            <label class="form-check-label" for="is_private">Surat Private</label>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Extract</button>
    </form>
</div>
@endsection 