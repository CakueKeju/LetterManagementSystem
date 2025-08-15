@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Preview Surat</h2>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Preview:</strong> File ini sudah berisi nomor surat yang valid. Berikut adalah preview surat yang akan disimpan.
        <br><small class="text-muted">Jika file tidak berisi placeholder text, preview akan menampilkan file asli.</small>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Informasi Surat</h6>
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
                            <td>{{ \App\Models\Division::find($divisi_id)->nama_divisi }}</td>
                        </tr>
                        <tr>
                            <td><strong>Jenis Surat:</strong></td>
                            <td>{{ \App\Models\JenisSurat::find($jenis_surat_id)->nama_jenis }}</td>
                        </tr>
                        <tr>
                            <td><strong>Perihal:</strong></td>
                            <td>{{ $perihal ?? 'Tidak terdeteksi' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Tanggal Surat:</strong></td>
                            <td>{{ $tanggal_surat ? date('d/m/Y', strtotime($tanggal_surat)) : 'Tidak terdeteksi' }}</td>
                        </tr>
                        <tr>
                            <td><strong>File:</strong></td>
                            <td>{{ basename($file_path) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @if($is_private ?? false)
                                    <span class="badge bg-warning">Private</span>
                                @else
                                    <span class="badge bg-success">Public</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" onclick="previewFile()">
            <i class="fas fa-eye"></i> Preview File (New Tab)
        </button>
        
        <div>
            <a href="{{ route('surat.upload') }}" class="btn btn-warning">
                <i class="fas fa-upload"></i> Upload Ulang
            </a>
            <form action="{{ route('surat.store-from-preview') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="file_path" value="{{ $file_path }}">
                <input type="hidden" name="file_size" value="{{ $file_size }}">
                <input type="hidden" name="mime_type" value="{{ $mime_type }}">
                <input type="hidden" name="nomor_urut" value="{{ $nomor_urut }}">
                <input type="hidden" name="divisi_id" value="{{ $divisi_id }}">
                <input type="hidden" name="jenis_surat_id" value="{{ $jenis_surat_id }}">
                <input type="hidden" name="perihal" value="{{ $perihal ?? '' }}">
                <input type="hidden" name="tanggal_surat" value="{{ $tanggal_surat ?? date('Y-m-d') }}">
                <input type="hidden" name="tanggal_diterima" value="{{ date('Y-m-d') }}">
                <input type="hidden" name="is_private" value="{{ $is_private ? '1' : '0' }}">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Surat
                </button>
            </form>
        </div>
    </div>
</div>

<form id="previewForm" action="{{ route('surat.preview') }}" method="POST" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="file_path" id="preview_file_path" value="{{ $file_path }}">
    <input type="hidden" name="nomor_urut" id="preview_nomor_urut" value="{{ $nomor_urut }}">
    <input type="hidden" name="divisi_id" id="preview_divisi_id" value="{{ $divisi_id }}">
    <input type="hidden" name="jenis_surat_id" id="preview_jenis_surat_id" value="{{ $jenis_surat_id }}">
    <input type="hidden" name="tanggal_surat" id="preview_tanggal_surat" value="{{ $tanggal_surat ?? date('Y-m-d') }}">
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