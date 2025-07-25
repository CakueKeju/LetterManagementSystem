@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konfirmasi Data Surat</h2>
    
    @if($extracted_text)
    <div class="mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Hasil Ekstraksi Teks ({{ $extraction_method ?? 'Unknown' }})</h6>
            </div>
            <div class="card-body">
                <textarea class="form-control" rows="6" readonly>{{ $extracted_text }}</textarea>
                <div class="form-text mt-2">
                    Teks di atas diekstrak menggunakan {{ $extraction_method ?? 'Unknown' }}. 
                    Silakan periksa dan perbaiki data yang diperlukan di bawah ini.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Debug Information -->
    <div class="mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Deteksi Otomatis</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Nomor Urut:</strong><br>
                        <span class="badge {{ $input['nomor_urut'] ? 'bg-success' : 'bg-secondary' }}">
                            {{ $input['nomor_urut'] ?? 'Tidak terdeteksi' }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Divisi:</strong><br>
                        @php
                            $detectedDivision = $divisions->find($input['divisi_id'] ?? null);
                        @endphp
                        <span class="badge {{ $input['divisi_id'] ? 'bg-success' : 'bg-secondary' }}">
                            {{ $detectedDivision ? $detectedDivision->nama_divisi : 'Tidak terdeteksi' }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Jenis Surat:</strong><br>
                        @php
                            $detectedJenis = $jenisSurat->find($input['jenis_surat_id'] ?? null);
                        @endphp
                        <span class="badge {{ $input['jenis_surat_id'] ? 'bg-success' : 'bg-secondary' }}">
                            {{ $detectedJenis ? $detectedJenis->nama_jenis : 'Tidak terdeteksi' }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Tanggal Surat:</strong><br>
                        <span class="badge {{ $input['tanggal_surat'] && $input['tanggal_surat'] !== date('Y-m-d') ? 'bg-success' : 'bg-secondary' }}">
                            {{ $input['tanggal_surat'] ?? 'Hari ini' }}
                        </span>
                    </div>
                </div>
                @if($input['nomor_urut'] && $input['divisi_id'] && $input['jenis_surat_id'])
                <div class="mt-3">
                    <strong>Kode Surat yang akan dibuat:</strong><br>
                    <code class="text-success">
                        {{ $input['nomor_urut'] }}/{{ $detectedDivision->kode_divisi }}/{{ $detectedJenis->kode_jenis }}/INTENS/{{ date('Y') }}
                    </code>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    @if(isset($ocr_error) && $ocr_error)
    <div class="mb-4">
        <div class="alert alert-warning">
            <strong>Peringatan Ekstraksi:</strong> {{ $ocr_error }}
        </div>
    </div>
    @endif
    
    @if(empty($extracted_text) && !isset($ocr_error))
    <div class="mb-4">
        <div class="alert alert-info">
            <strong>Info:</strong> Tidak ada teks yang diekstrak dari file. Ini mungkin karena:
            <ul class="mb-0 mt-2">
                <li>File tidak memiliki teks yang dapat diekstrak</li>
                <li>File terproteksi atau terenkripsi</li>
                <li>Kualitas file terlalu rendah</li>
                <li>Format file tidak didukung</li>
            </ul>
        </div>
    </div>
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

    <form action="{{ route('surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <input type="hidden" name="nomor_surat" value="{{ $nomor_surat }}">
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Form Data Surat</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nomor Surat (Preview)</label>
                    <div id="nomorSuratPreview" class="form-control bg-light" style="font-weight:bold;">
                        {{ $nomor_surat ?: '.../.../.../INTENS/.../...' }}
                    </div>
                </div>
                <input type="hidden" name="nomor_urut" id="nomor_urut_hidden" value="{{ $input['nomor_urut'] ?? '' }}">
                <input type="hidden" name="nomor_surat" id="nomor_surat_hidden" value="{{ $nomor_surat ?: '.../.../.../INTENS/.../...' }}">
                <script>
                function updateNomorSuratPreview() {
                    var divisiInput = document.getElementById('divisi_id');
                    var jenisSelect = document.getElementById('jenis_surat_id');
                    var tanggalSurat = document.getElementById('tanggal_surat').value;
                    var divisiId = divisiInput.value;
                    var kodeDivisi = divisiInput.getAttribute('data-kode') || '...';
                    var jenisSuratId = jenisSelect.value;
                    var kodeJenis = jenisSelect.selectedOptions[0] ? jenisSelect.selectedOptions[0].getAttribute('data-kode') : '...';
                    var tgl = tanggalSurat ? new Date(tanggalSurat) : null;
                    var bulan = tgl && !isNaN(tgl.getMonth()) ? (tgl.getMonth()+1).toString().padStart(2, '0') : '...';
                    var tahun = tgl && !isNaN(tgl.getFullYear()) ? tgl.getFullYear() : '...';
                    if (divisiId && jenisSuratId) {
                        fetch(`/api/next-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}`)
                            .then(response => response.json())
                            .then(data => {
                                var nomorUrut = data.next_nomor_urut || '...';
                                document.getElementById('nomor_urut_hidden').value = nomorUrut;
                                var nomorSurat = `${String(nomorUrut).padStart(3, '0')}/${kodeDivisi}/${kodeJenis}/INTENS/${bulan}/${tahun}`;
                                document.getElementById('nomorSuratPreview').textContent = nomorSurat;
                                document.getElementById('nomor_surat_hidden').value = nomorSurat;
                            });
                    } else {
                        var nomorUrut = '...';
                        document.getElementById('nomor_urut_hidden').value = '';
                        var nomorSurat = `${nomorUrut}/${kodeDivisi}/${kodeJenis}/INTENS/${bulan}/${tahun}`;
                        document.getElementById('nomorSuratPreview').textContent = nomorSurat;
                        document.getElementById('nomor_surat_hidden').value = nomorSurat;
                    }
                }
                function lockNomorUrutAjax() {
                    var divisiId = document.getElementById('divisi_id').value;
                    var jenisSuratId = document.getElementById('jenis_surat_id').value;
                    var tanggalSurat = document.getElementById('tanggal_surat').value;
                    if (!divisiId || !jenisSuratId) return;
                    fetch(`/api/lock-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.nomor_urut) {
                                document.getElementById('nomor_urut_hidden').value = data.nomor_urut;
                                updateNomorSuratPreview();
                            }
                        });
                }
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('jenis_surat_id').addEventListener('change', updateNomorSuratPreview);
                    document.getElementById('tanggal_surat').addEventListener('change', updateNomorSuratPreview);
                    updateNomorSuratPreview();
                    document.getElementById('jenis_surat_id').addEventListener('change', lockNomorUrutAjax);
                    if (document.getElementById('divisi_id')) {
                        document.getElementById('divisi_id').addEventListener('change', lockNomorUrutAjax);
                    }
                });
                </script>
                <!-- Removed nomor urut input and script, nomor urut is now prefilled and hidden -->
                <div class="mb-3">
                    <label class="form-label">Divisi</label>
                    <div class="form-control bg-light" readonly>{{ Auth::user()->division->nama_divisi }} ({{ Auth::user()->division->kode_divisi }})</div>
                    <input type="hidden" id="divisi_id" name="divisi_id" value="{{ Auth::user()->divisi_id }}" data-kode="{{ Auth::user()->division->kode_divisi }}">
                </div>
                <div class="mb-3">
                    <label for="jenis_surat_id" class="form-label">Jenis Surat</label>
                    <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required>
                        <option value="">Pilih Jenis Surat</option>
                        @foreach($jenisSurat as $jenis)
                            <option value="{{ $jenis->id }}" {{ ($input['jenis_surat_id'] ?? '') == $jenis->id ? 'selected' : '' }} data-kode="{{ $jenis->kode_jenis }}">
                                {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal</label>
                    <input type="text" class="form-control" id="perihal" name="perihal" value="{{ $input['perihal'] ?? '' }}" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
                    <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" value="{{ $input['tanggal_surat'] ?? '' }}" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_diterima" class="form-label">Tanggal Upload (Otomatis)</label>
                    <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" value="{{ $input['tanggal_diterima'] ?? '' }}" required>
                </div>
                <div class="mb-3">
                    <label for="is_private" class="form-label">Status</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" {{ ($input['is_private'] ?? false) ? 'checked' : '' }} onchange="toggleUserSelection()">
                        <label class="form-check-label" for="is_private">Surat Private</label>
                    </div>
                </div>

                <!-- User Selection for Private Access -->
                <div id="userSelectionSection" class="mb-3" style="display: none;">
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
                                    Jika tidak ada user yang dipilih, hanya Anda dan Admin yang dapat mengakses.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Konfirmasi</button>
        <button type="button" class="btn btn-secondary" onclick="previewSurat()">Preview PDF</button>
    </form>
    <form id="previewForm" action="{{ route('surat.preview') }}" method="POST" target="_blank" style="display:none;">
        @csrf
        <input type="hidden" name="file_path" id="preview_file_path" value="{{ $file_path }}">
        <input type="hidden" name="nomor_urut" id="preview_nomor_urut" value="">
        <input type="hidden" name="divisi_id" id="preview_divisi_id" value="">
        <input type="hidden" name="jenis_surat_id" id="preview_jenis_surat_id" value="">
        <input type="hidden" name="tanggal_surat" id="preview_tanggal_surat" value="">
    </form>
    <script>
    function previewSurat() {
        // Ambil value dari input utama (hidden atau visible)
        var nomorUrut = document.getElementById('nomor_urut_hidden') ? document.getElementById('nomor_urut_hidden').value : '';
        var divisiId = document.getElementById('divisi_id') ? document.getElementById('divisi_id').value : '';
        var jenisSuratId = document.getElementById('jenis_surat_id') ? document.getElementById('jenis_surat_id').value : '';
        var tanggalSurat = document.getElementById('tanggal_surat') ? document.getElementById('tanggal_surat').value : '';
        var filePath = document.querySelector('input[name="file_path"]').value;
        // Isi input hidden di form preview
        document.getElementById('preview_nomor_urut').value = nomorUrut;
        document.getElementById('preview_divisi_id').value = divisiId;
        document.getElementById('preview_jenis_surat_id').value = jenisSuratId;
        document.getElementById('preview_tanggal_surat').value = tanggalSurat;
        document.getElementById('preview_file_path').value = filePath;
        // Validasi minimal
        if (!nomorUrut || !divisiId || !jenisSuratId || !tanggalSurat || !filePath) {
            alert('Data belum lengkap untuk preview. Pastikan semua field terisi.');
            return;
        }
        document.getElementById('previewForm').submit();
    }
    </script>
</div>

<script>
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleUserSelection();
});
</script>
@endsection 