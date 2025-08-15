@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Preview Final Surat - Mode Otomatis (Admin)</h2>
        <a href="{{ route('admin.surat.mode.selection') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Pilih Mode Lain
        </a>
    </div>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Preview:</strong> Berikut adalah preview surat yang akan disimpan. Pastikan semua data sudah benar sebelum melanjutkan.
        <br><small class="text-muted">Jika file tidak berisi placeholder text, preview akan menampilkan file asli.</small>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="fas fa-magic"></i> Informasi Surat - Mode Otomatis</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nomor Surat:</strong></td>
                            <td>{{ $nomor_surat ?? '.../.../.../INTENS/.../...' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Divisi:</strong></td>
                            <td>{{ \App\Models\Division::find($input['divisi_id'])->nama_divisi }}</td>
                        </tr>
                        <tr>
                            <td><strong>Jenis Surat:</strong></td>
                            <td>{{ \App\Models\JenisSurat::find($input['jenis_surat_id'])->nama_jenis }}</td>
                        </tr>
                        <tr>
                            <td><strong>Perihal:</strong></td>
                            <td>{{ $input['perihal'] }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Tanggal Surat:</strong></td>
                            <td>{{ date('d/m/Y', strtotime($input['tanggal_surat'])) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Upload:</strong></td>
                            <td>{{ date('d/m/Y', strtotime($input['tanggal_diterima'])) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @if($input['is_private'] ?? false)
                                    <span class="badge bg-warning">Private</span>
                                @else
                                    <span class="badge bg-success">Public</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>File:</strong></td>
                            <td>{{ basename($file_path) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" onclick="previewFile()">
            <i class="fas fa-eye"></i> Preview File
        </button>
        
        <div>
            <a href="{{ route('admin.surat.automatic.upload') }}" class="btn btn-warning" onclick="return confirm('Yakin ingin kembali ke halaman edit? Data yang sudah diisi akan hilang.')">
                <i class="fas fa-edit"></i> Edit Data
            </a>
            <form action="{{ route('admin.surat.automatic.store') }}" method="POST" style="display: inline;" id="finalStoreForm">
                @csrf
                <input type="hidden" name="file_path" value="{{ $file_path }}">
                <input type="hidden" name="file_size" value="{{ $file_size }}">
                <input type="hidden" name="mime_type" value="{{ $mime_type }}">
                <input type="hidden" name="nomor_urut" value="{{ $input['nomor_urut'] ?? '' }}">
                <input type="hidden" name="divisi_id" value="{{ $input['divisi_id'] ?? '' }}">
                <input type="hidden" name="jenis_surat_id" value="{{ $input['jenis_surat_id'] ?? '' }}">
                <input type="hidden" name="perihal" value="{{ $input['perihal'] ?? '' }}">
                <input type="hidden" name="tanggal_surat" value="{{ $input['tanggal_surat'] ?? '' }}">
                <input type="hidden" name="tanggal_diterima" value="{{ $input['tanggal_diterima'] ?? '' }}">
                @if($input['is_private'] ?? false)
                    <input type="hidden" name="is_private" value="1">
                    @if(isset($input['selected_users']))
                        @foreach($input['selected_users'] as $userId)
                            <input type="hidden" name="selected_users[]" value="{{ $userId }}">
                        @endforeach
                    @endif
                @endif
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Surat
                </button>
            </form>
        </div>
    </div>
</div>

<form id="previewForm" action="{{ route('surat.preview') }}" method="POST" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="file_path" id="preview_file_path" value="{{ $file_path ?? '' }}">
    <input type="hidden" name="nomor_urut" id="preview_nomor_urut" value="{{ $input['nomor_urut'] ?? '' }}">
    <input type="hidden" name="divisi_id" id="preview_divisi_id" value="{{ $input['divisi_id'] ?? '' }}">
    <input type="hidden" name="jenis_surat_id" id="preview_jenis_surat_id" value="{{ $input['jenis_surat_id'] ?? '' }}">
    <input type="hidden" name="tanggal_surat" id="preview_tanggal_surat" value="{{ $input['tanggal_surat'] ?? '' }}">
</form>

<script>
function previewFile() {
    var form = document.getElementById('previewForm');
    if (form) {
        form.target = '_blank';
        form.method = 'POST';
        form.submit();
    } else {
        alert('Error: Preview form not found. Please refresh the page and try again.');
    }
}
</script>
@endsection 
