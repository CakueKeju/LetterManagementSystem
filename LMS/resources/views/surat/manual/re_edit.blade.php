@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Edit Form & Upload Ulang</h2>
            
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle"></i> File Upload Sebelumnya Gagal Verifikasi</h6>
                <p class="mb-2"><strong>Error:</strong> {{ $failedData['error_message'] }}</p>
                <p class="mb-0">Silakan edit form di bawah untuk generate nomor surat yang baru, atau pastikan file yang di-upload sudah sesuai dengan nomor surat yang diharapkan.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <!-- Form Input -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Form Data Surat</h5>
                        </div>
                        <div class="card-body">
                            <form id="reEditForm" action="{{ route('surat.manual.reUpload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-3">
                                    <label class="form-label">Divisi</label>
                                    <div class="form-control bg-light">
                                        {{ Auth::user()->division->nama_divisi }} ({{ Auth::user()->division->kode_divisi }})
                                    </div>
                                    <input type="hidden" name="divisi_id" value="{{ Auth::user()->divisi_id }}">
                                </div>

                                <div class="mb-3">
                                    <label for="jenis_surat_id" class="form-label">Jenis Surat <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required>
                                        <option value="">Pilih Jenis Surat</option>
                                        @foreach($jenisSurat as $jenis)
                                            <option value="{{ $jenis->id }}" 
                                                    data-kode="{{ $jenis->kode_jenis }}"
                                                    {{ $jenis->id == $formData['jenis_surat_id'] ? 'selected' : '' }}>
                                                {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="perihal" class="form-label">Perihal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="perihal" name="perihal" 
                                           value="{{ old('perihal', $formData['perihal']) }}" required maxlength="255">
                                </div>

                                <div class="mb-3">
                                    <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" 
                                           value="{{ old('tanggal_surat', $formData['tanggal_surat']) }}" required>
                                </div>

                                <div class="mb-3" style="display: none;">
                                    <label for="tanggal_diterima" class="form-label">Tanggal Diterima</label>
                                    <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" 
                                           value="{{ old('tanggal_diterima', $formData['tanggal_diterima']) }}">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1"
                                               {{ old('is_private', $formData['is_private']) ? 'checked' : '' }}
                                               onchange="toggleUserSelection()">
                                        <label class="form-check-label" for="is_private">
                                            Surat Private
                                        </label>
                                    </div>
                                </div>

                                <!-- User Selection for Private Access -->
                                <div id="userSelectionSection" class="mb-3" style="display: {{ old('is_private', $formData['is_private']) ? 'block' : 'none' }};">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Pilih User yang Dapat Mengakses Surat Private</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="userSearch" class="form-label">Cari User:</label>
                                                <input type="text" class="form-control" id="userSearch" placeholder="Ketik nama, username, atau email..." onkeyup="searchUsers()">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="form-label">Daftar User:</label>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllUsers()">Pilih Semua</button>
                                                </div>
                                                <div id="userList" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                                    <div class="text-center text-muted">
                                                        <i class="fas fa-spinner fa-spin"></i> Loading users...
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <small>
                                                    <i class="fas fa-info-circle"></i>
                                                    <strong>Info:</strong> User yang dipilih akan dapat mengakses surat private ini. 
                                                    Admin dan Anda (pengupload) tidak perlu dipilih karena sudah otomatis memiliki akses.
                                                    Jika tidak ada user yang dipilih, hanya Anda dan Admin yang dapat mengakses.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="file" class="form-label">File Surat Baru <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="file" name="file" required 
                                           accept=".pdf,.doc,.docx">
                                    <div class="form-text">
                                        Format: PDF, DOC, DOCX (Max 10MB)
                                    </div>
                                    <div id="fileError" class="alert alert-danger mt-2" style="display: none;"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview & Info -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi & Preview</h5>
                        </div>
                        <div class="card-body">
                            <!-- Nomor Surat Preview -->
                            <div class="mb-4">
                                <label class="form-label">Preview Nomor Surat Baru:</label>
                                <div id="nomorSuratPreview" class="p-3 border border-info rounded bg-light text-center">
                                    <span class="text-muted">Lengkapi form untuk preview nomor surat</span>
                                </div>
                            </div>

                            <!-- Comparison -->
                            <div class="mb-4">
                                <h6><i class="fas fa-compare text-warning me-2"></i>Perbandingan</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <p><strong>Nomor Surat Sebelumnya:</strong></p>
                                        <div class="alert alert-danger">
                                            <code>{{ $failedData['expected_nomor_surat'] }}</code>
                                        </div>
                                        
                                        <p><strong>Yang Ditemukan di File:</strong></p>
                                        <div class="alert alert-warning">
                                            @if($failedData['found_nomor_surat'])
                                                <code>{{ $failedData['found_nomor_surat'] }}</code>
                                            @else
                                                <em class="text-muted">Tidak ditemukan</em>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- File Info Sebelumnya -->
                            <div class="mb-4">
                                <h6><i class="fas fa-file text-secondary me-2"></i>File Sebelumnya</h6>
                                <div class="alert alert-light">
                                    <p class="mb-1"><strong>Nama:</strong> {{ $failedData['original_filename'] }}</p>
                                    @if($failedData['ocr_error'])
                                        <p class="mb-0 text-warning"><strong>Error OCR:</strong> {{ $failedData['ocr_error'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" form="reEditForm" class="btn btn-success btn-lg" id="submitBtn">
                                    <i class="fas fa-upload me-2"></i>Upload File Baru
                                </button>
                                <a href="{{ route('surat.manual.result', ['status' => 'failed']) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Verification
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('surat.mode.selection') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-home me-2"></i>Kembali ke Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// =============================================================================================
// PREVIEW NOMOR SURAT

// konversi bulan ke romawi
function monthToRoman(month) {
    const romanNumerals = {
        1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI',
        7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII'
    };
    return romanNumerals[month] || month;
}

document.addEventListener('DOMContentLoaded', function() {
    const jenisSuratSelect = document.getElementById('jenis_surat_id');
    const tanggalSuratInput = document.getElementById('tanggal_surat');
    const perihalInput = document.getElementById('perihal');
    const nomorSuratPreview = document.getElementById('nomorSuratPreview');
    
    const divisiKode = '{{ Auth::user()->division->kode_divisi }}';
    
    // update preview nomor surat
    function updatePreview() {
        const jenisSuratId = jenisSuratSelect.value;
        const tanggalSurat = tanggalSuratInput.value;
        const perihal = perihalInput.value.trim();
        
        if (!jenisSuratId || !tanggalSurat || !perihal) {
            nomorSuratPreview.innerHTML = '<span class="text-muted">Lengkapi form untuk preview nomor surat</span>';
            return;
        }
        
        const selectedOption = jenisSuratSelect.options[jenisSuratSelect.selectedIndex];
        const jenisKode = selectedOption.getAttribute('data-kode');
        
        const date = new Date(tanggalSurat);
        const month = monthToRoman(date.getMonth() + 1);
        const year = String(date.getFullYear());
        
        // loading state
        nomorSuratPreview.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Ngambil nomor surat...</span>';
        
        // ambil nomor urut berikutnya
        fetch('/api/next-nomor-urut', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                divisi_id: {{ Auth::user()->divisi_id }},
                jenis_surat_id: jenisSuratId,
                tanggal_surat: tanggalSurat
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.next_nomor_urut) {
                const nomorUrut = String(data.next_nomor_urut).padStart(3, '0');
                const nomorSurat = `${nomorUrut}/${divisiKode}/${jenisKode}/INTENS/${month}/${year}`;
                
                nomorSuratPreview.innerHTML = `<h5 class="text-success fw-bold mb-0">${nomorSurat}</h5>`;
            } else {
                nomorSuratPreview.innerHTML = '<span class="text-danger">Error: Ga bisa generate nomor surat</span>';
            }
        })
        .catch(error => {
            console.error('Error ambil nomor surat:', error);
            nomorSuratPreview.innerHTML = '<span class="text-danger">Error saat ngambil nomor surat</span>';
        });
    }
    
    // event listeners
    jenisSuratSelect.addEventListener('change', updatePreview);
    tanggalSuratInput.addEventListener('change', updatePreview);
    perihalInput.addEventListener('input', function() {
        // debounce untuk input text
        clearTimeout(this.previewTimeout);
        this.previewTimeout = setTimeout(updatePreview, 500);
    });
    
    // initial preview
    updatePreview();
    
    // handle form submission dengan loading state
    const form = document.getElementById('reEditForm');
    const submitBtn = document.getElementById('submitBtn');
    const fileInput = document.getElementById('file');
    const fileError = document.getElementById('fileError');
    
    // validasi file
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        fileError.style.display = 'none';
        
        if (file) {
            if (file.size > maxSize) {
                fileError.innerHTML = 'File terlalu besar! Maksimal 10MB.';
                fileError.style.display = 'block';
                this.value = '';
                submitBtn.disabled = true;
                return;
            }
            
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!allowedTypes.includes(file.type)) {
                fileError.innerHTML = 'Format file tidak didukung! Gunakan PDF, DOC, atau DOCX.';
                fileError.style.display = 'block';
                this.value = '';
                submitBtn.disabled = true;
                return;
            }
            
            submitBtn.disabled = false;
        }
    });
    
    form.addEventListener('submit', function(e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            fileError.innerHTML = 'Pilih file dulu!';
            fileError.style.display = 'block';
            return false;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
    });

    // Load users if private is already checked
    if (document.getElementById('is_private').checked) {
        loadUsers();
    }
});

// =============================================================================================
// USER SELECTION FUNCTIONS 

let allUsers = [];
let filteredUsers = [];

function toggleUserSelection() {
    const isPrivate = document.getElementById('is_private').checked;
    const userSection = document.getElementById('userSelectionSection');
    
    if (isPrivate) {
        userSection.style.display = 'block';
        loadUsers();
    } else {
        userSection.style.display = 'none';
    }
}

function loadUsers() {
    fetch('{{ route("surat.getUsersForAccess") }}')
        .then(response => response.json())
        .then(users => {
            allUsers = users;
            filteredUsers = users;
            renderUserList();
        })
        .catch(error => {
            console.error('Error loading users:', error);
            document.getElementById('userList').innerHTML = '<div class="text-center text-danger">Error loading users</div>';
        });
}

function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value.toLowerCase();
    filteredUsers = allUsers.filter(user => 
        user.full_name.toLowerCase().includes(searchTerm) ||
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );
    renderUserList();
}

function renderUserList() {
    const userList = document.getElementById('userList');
    
    if (filteredUsers.length === 0) {
        userList.innerHTML = '<div class="text-center text-muted">Tidak ada user ditemukan</div>';
        return;
    }
    
    let html = '';
    filteredUsers.forEach(user => {
        html += `
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="selected_users[]" value="${user.id}" id="user_${user.id}">
                <label class="form-check-label" for="user_${user.id}">
                    <strong>${user.full_name}</strong><br>
                    <small class="text-muted">${user.username} • ${user.email}</small>
                </label>
            </div>
        `;
    });
    
    userList.innerHTML = html;
}

function selectAllUsers() {
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

</script>
@endsection
