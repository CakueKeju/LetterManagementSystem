@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konfirmasi Data Surat (Admin)</h2>
    <form action="{{ route('admin.surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <div class="mb-3">
            <label for="divisi_id" class="form-label">Divisi</label>
            <select name="divisi_id" id="divisi_id" class="form-select" required>
                <option value="">Pilih Divisi</option>
                @foreach($divisions as $divisi)
                    <option value="{{ $divisi->id }}">{{ $divisi->nama_divisi }} ({{ $divisi->kode_divisi }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
            <select name="jenis_surat_id" id="jenis_surat_id" class="form-select" required>
                <option value="">Pilih Jenis Surat</option>
                @foreach($jenisSurat as $jenis)
                    <option value="{{ $jenis->id }}">{{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="nomor_urut" class="form-label">Nomor Urut</label>
            <input type="number" name="nomor_urut" id="nomor_urut" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="perihal" class="form-label">Perihal</label>
            <input type="text" name="perihal" id="perihal" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
            <input type="date" name="tanggal_surat" id="tanggal_surat" class="form-control" required>
        </div>
        <input type="hidden" name="tanggal_diterima" value="{{ date('Y-m-d') }}">
        <button type="submit" class="btn btn-primary">Konfirmasi & Simpan</button>
    </form>
</div>
@endsection 