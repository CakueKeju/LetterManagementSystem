@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload Surat (Admin)</h2>
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
    <form action="{{ route('admin.surat.handleUpload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Surat File (PDF, DOC, DOCX)</label>
            <input type="file" class="form-control" id="file" name="file" required accept=".pdf,.doc,.docx">
            <div class="form-text">
                <strong>Format yang didukung:</strong><br>
                • <strong>PDF:</strong> Isi nomor surat otomatis pada placeholder<br>
                • <strong>Word (DOC/DOCX):</strong> Isi nomor surat otomatis pada placeholder<br>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Lanjutkan</button>
    </form>
</div>
@endsection 