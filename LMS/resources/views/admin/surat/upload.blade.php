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
            <label for="divisi_id" class="form-label">Pilih Divisi</label>
            <select class="form-select" id="divisi_id" name="divisi_id" required>
                <option value="">Pilih Divisi</option>
                @foreach($divisions as $divisi)
                    <option value="{{ $divisi->id }}" data-kode="{{ $divisi->kode_divisi }}">
                        {{ $divisi->nama_divisi }} ({{ $divisi->kode_divisi }})
                    </option>
                @endforeach
            </select>
            <div class="form-text">Pilih divisi untuk menentukan jenis surat yang tersedia</div>
        </div>
        
        <div class="mb-3">
            <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
            <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required disabled>
                <option value="">Pilih divisi terlebih dahulu</option>
            </select>
            <div class="form-text">Jenis surat akan muncul setelah memilih divisi</div>
        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const divisiSelect = document.getElementById('divisi_id');
    const jenisSuratSelect = document.getElementById('jenis_surat_id');
    
    // When division changes, load jenis surat for that division
    divisiSelect.addEventListener('change', function() {
        const divisiId = this.value;
        
        if (!divisiId) {
            jenisSuratSelect.innerHTML = '<option value="">Pilih divisi terlebih dahulu</option>';
            jenisSuratSelect.disabled = true;
            return;
        }
        
        // Show loading
        jenisSuratSelect.innerHTML = '<option value="">Loading...</option>';
        jenisSuratSelect.disabled = true;
        
        // Fetch jenis surat for selected division
        fetch(`/api/jenis-surat-by-division?divisi_id=${divisiId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    jenisSuratSelect.innerHTML = '<option value="">Error loading data</option>';
                    return;
                }
                
                // Populate jenis surat dropdown
                let options = '<option value="">Pilih Jenis Surat</option>';
                data.jenis_surat.forEach(jenis => {
                    options += `<option value="${jenis.id}" data-kode="${jenis.kode_jenis}">
                        ${jenis.nama_jenis} (${jenis.kode_jenis})
                    </option>`;
                });
                
                jenisSuratSelect.innerHTML = options;
                jenisSuratSelect.disabled = false;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                jenisSuratSelect.innerHTML = '<option value="">Error loading data</option>';
            });
    });
});
</script>
@endsection 