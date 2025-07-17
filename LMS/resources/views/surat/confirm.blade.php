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

    <form action="{{ route('surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <input type="hidden" name="kode_surat" value="{{ $kode_surat }}">
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Form Data Surat</h6>
            </div>
            <div class="card-body">
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
                    <label for="tanggal_diterima" class="form-label">Tanggal Upload</label>
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
                                    <span class="text-muted">Daftar User (Maksimal 30 user)</span>
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
                                    Jika tidak ada user yang dipilih, hanya Anda yang dapat mengakses.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-success mt-3">Simpan Surat</button>
    </form>
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