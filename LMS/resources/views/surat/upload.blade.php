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
            <label for="file" class="form-label">Surat File (PDF, DOC, DOCX, JPG, PNG)</label>
            <input type="file" class="form-control" id="file" name="file" required>
            <div class="form-text">
                <strong>Format yang didukung:</strong><br>
                • <strong>PDF:</strong> Ekstraksi teks langsung dari PDF<br>
                • <strong>Word (DOC/DOCX):</strong> Ekstraksi teks dari dokumen Word<br>
                • <strong>Gambar (JPG/PNG):</strong> OCR untuk mengekstrak teks dari gambar<br><br>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Lanjutkan</button>
    </form>
</div>
@endsection 