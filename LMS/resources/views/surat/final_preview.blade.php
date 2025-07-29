@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Preview Final Surat</h2>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Preview:</strong> Berikut adalah preview surat yang akan disimpan. Pastikan semua data sudah benar sebelum melanjutkan.
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
            <a href="{{ route('surat.confirm') }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Data
            </a>
            <form action="{{ route('surat.final-store') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="file_path" value="{{ $file_path }}">
                <input type="hidden" name="file_size" value="{{ $file_size }}">
                <input type="hidden" name="mime_type" value="{{ $mime_type }}">
                <input type="hidden" name="nomor_urut" value="{{ $input['nomor_urut'] }}">
                <input type="hidden" name="divisi_id" value="{{ $input['divisi_id'] }}">
                <input type="hidden" name="jenis_surat_id" value="{{ $input['jenis_surat_id'] }}">
                <input type="hidden" name="perihal" value="{{ $input['perihal'] }}">
                <input type="hidden" name="tanggal_surat" value="{{ $input['tanggal_surat'] }}">
                <input type="hidden" name="tanggal_diterima" value="{{ $input['tanggal_diterima'] }}">
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

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading preview...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="downloadFile()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

<form id="previewForm" action="{{ route('surat.preview') }}" method="POST" target="_blank" style="display:none;">
    @csrf
    <input type="hidden" name="file_path" id="preview_file_path" value="{{ $file_path }}">
    <input type="hidden" name="nomor_urut" id="preview_nomor_urut" value="{{ $input['nomor_urut'] }}">
    <input type="hidden" name="divisi_id" id="preview_divisi_id" value="{{ $input['divisi_id'] }}">
    <input type="hidden" name="jenis_surat_id" id="preview_jenis_surat_id" value="{{ $input['jenis_surat_id'] }}">
    <input type="hidden" name="tanggal_surat" id="preview_tanggal_surat" value="{{ $input['tanggal_surat'] }}">
</form>

<script>
// Simple preview function
function previewFile() {
    console.log('Preview button clicked');
    
    // Simple approach - just submit the form to open in new tab
    var form = document.getElementById('previewForm');
    if (form) {
        form.target = '_blank';
        form.method = 'POST'; // Ensure it's POST
        form.submit();
    } else {
        console.error('Preview form not found');
    }
}

// Alternative modal approach if Bootstrap is available
function previewFileModal() {
    console.log('Preview modal button clicked');
    
    // Check if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var modalElement = document.getElementById('previewModal');
        if (modalElement) {
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Load content using POST
            var form = document.getElementById('previewForm');
            var formData = new FormData(form);
            var previewUrl = '{{ route("surat.preview") }}';
            
            var previewContent = document.getElementById('previewContent');
            var fileExtension = '{{ pathinfo($file_path, PATHINFO_EXTENSION) }}';
            
            if (fileExtension.toLowerCase() === 'pdf') {
                // For PDF, create a temporary form to POST
                var tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = previewUrl;
                tempForm.target = 'preview_iframe';
                tempForm.style.display = 'none';
                
                // Add CSRF token
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                tempForm.appendChild(csrfToken);
                
                // Add form data
                for (var pair of formData.entries()) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = pair[0];
                    input.value = pair[1];
                    tempForm.appendChild(input);
                }
                
                document.body.appendChild(tempForm);
                
                previewContent.innerHTML = `
                    <iframe name="preview_iframe" 
                            width="100%" 
                            height="600px" 
                            style="border: none;">
                    </iframe>
                `;
                
                tempForm.submit();
                document.body.removeChild(tempForm);
            } else {
                // For other files, show download link
                previewContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>File Preview:</strong> File ini tidak dapat ditampilkan dalam browser.
                        <br><br>
                        <button onclick="downloadFile()" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download File
                        </button>
                    </div>
                `;
            }
        } else {
            console.error('Modal element not found');
            // Fallback to simple preview
            previewFile();
        }
    } else {
        console.log('Bootstrap not available, using simple preview');
        // Fallback to simple preview
        previewFile();
    }
}

function downloadFile() {
    console.log('Download button clicked');
    var form = document.getElementById('previewForm');
    if (form) {
        form.method = 'POST'; // Ensure it's POST
        form.submit();
    }
}

// Test if everything is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('Modal element exists:', document.getElementById('previewModal') !== null);
    console.log('Preview form exists:', document.getElementById('previewForm') !== null);
    console.log('Preview form method:', document.getElementById('previewForm') ? document.getElementById('previewForm').method : 'N/A');
    console.log('Preview form action:', document.getElementById('previewForm') ? document.getElementById('previewForm').action : 'N/A');
});
</script>
@endsection 