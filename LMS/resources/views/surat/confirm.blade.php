@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konfirmasi Data Surat</h2>
    <form action="{{ route('surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <input type="hidden" name="kode_surat" value="{{ $kode_surat }}">
        
        <div class="mb-3">
            <label for="kode_surat_display" class="form-label">Kode Surat (Otomatis)</label>
            <input type="text" class="form-control" id="kode_surat_display" value="{{ $kode_surat }}" readonly>
        </div>
        <div class="mb-3">
            <label for="nomor_urut" class="form-label">Nomor Urut</label>
            <input type="number" class="form-control" id="nomor_urut" name="nomor_urut" value="{{ $input['nomor_urut'] ?? '' }}" required>
        </div>
        <div class="mb-3">
            <label for="divisi_id" class="form-label">Divisi</label>
            <select class="form-select" id="divisi_id" name="divisi_id" required>
                <option value="">Pilih Divisi</option>
                @foreach($divisions as $divisi)
                    <option value="{{ $divisi->id }}" {{ ($input['divisi_id'] ?? '') == $divisi->id ? 'selected' : '' }}>
                        {{ $divisi->nama_divisi }} ({{ $divisi->kode_divisi }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
            <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required>
                <option value="">Pilih Jenis Surat</option>
                @foreach($jenisSurat as $jenis)
                    <option value="{{ $jenis->id }}" {{ ($input['jenis_surat_id'] ?? '') == $jenis->id ? 'selected' : '' }}>
                        {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <input type="text" class="form-control" id="deskripsi" name="deskripsi" value="{{ $input['deskripsi'] ?? '' }}" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
            <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" value="{{ $input['tanggal_surat'] ?? '' }}" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_diterima" class="form-label">Tanggal Diterima/Scan</label>
            <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" value="{{ $input['tanggal_diterima'] ?? '' }}" required>
        </div>
        <div class="mb-3">
            <label for="is_private" class="form-label">Status</label>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" {{ ($input['is_private'] ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_private">Surat Private</label>
            </div>
        </div>
        @if($extracted_text)
        <div class="mb-3">
            <label for="extracted_text" class="form-label">Hasil OCR (Preview)</label>
            <textarea class="form-control" id="extracted_text" rows="5" readonly>{{ $extracted_text }}</textarea>
        </div>
        @endif
        <button type="submit" class="btn btn-success">Simpan Surat</button>
    </form>
</div>
@endsection 