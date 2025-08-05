@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konfirmasi Data Surat (Admin)</h2>
    
    @if($ocr_error)
        <div class="alert alert-warning">
            <strong>Peringatan Ekstraksi:</strong> {{ $ocr_error }}
        </div>
    @endif

    <form action="{{ route('admin.surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <input type="hidden" name="nomor_surat" value="">
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Form Data Surat</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nomor Surat (Preview)</label>
                    <div id="nomorSuratPreview" class="form-control bg-light" style="font-weight:bold;">
                        .../.../.../INTENS/.../...
                    </div>
                </div>
                <input type="hidden" name="nomor_urut" id="nomor_urut_hidden" value="">
                <input type="hidden" name="nomor_surat" id="nomor_surat_hidden" value=".../.../.../INTENS/.../...">
                
                <div class="mb-3">
                    <label for="divisi_id" class="form-label">Divisi</label>
                    <select class="form-select" id="divisi_id" name="divisi_id" required>
                        <option value="">Pilih Divisi</option>
                        @foreach($divisions as $divisi)
                            <option value="{{ $divisi->id }}" {{ ($input['divisi_id'] ?? '') == $divisi->id ? 'selected' : '' }} data-kode="{{ $divisi->kode_divisi }}">
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
                    <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" value="{{ $input['tanggal_surat'] ?? date('Y-m-d') }}" required>
                </div>
                
                <div class="mb-3">
                    <label for="tanggal_diterima" class="form-label">Tanggal Diterima</label>
                    <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" value="{{ $input['tanggal_diterima'] ?? date('Y-m-d') }}" required>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_private" name="is_private" {{ ($input['is_private'] ?? false) ? 'checked' : '' }} onchange="toggleUserSelection()">
                        <label class="form-check-label" for="is_private">
                            Surat Private (Akses Terbatas)
                        </label>
                    </div>
                </div>
                
                <div id="userSelectionContainer" class="d-none">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Pilih User yang Dapat Mengakses</h6>
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
                                    Admin dan pengupload tidak perlu dipilih karena sudah otomatis memiliki akses.
                                    Jika tidak ada user yang dipilih, hanya pengupload dan Admin yang dapat mengakses.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3 d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-secondary" onclick="previewSurat()">
                    <i class="fas fa-eye"></i> Preview File
                </button>
                <button type="button" class="btn btn-warning" id="btnCancelLock">
                    <i class="fas fa-times"></i> Batal & Hapus Lock
                </button>
            </div>
            <button type="submit" class="btn btn-primary">Konfirmasi & Simpan</button>
        </div>
    </form>
    
    <form id="previewForm" action="{{ route('surat.preview') }}" method="POST" target="_blank" style="display:none;">
        @csrf
        <input type="hidden" name="file_path" id="preview_file_path" value="{{ $file_path }}">
        <input type="hidden" name="nomor_urut" id="preview_nomor_urut" value="">
        <input type="hidden" name="divisi_id" id="preview_divisi_id" value="">
        <input type="hidden" name="jenis_surat_id" id="preview_jenis_surat_id" value="">
        <input type="hidden" name="tanggal_surat" id="preview_tanggal_surat" value="">
    </form>
</div>

<script>
// Real-time nomor surat preview update
function updateNomorSuratPreview() {
    var divisiSelect = document.getElementById('divisi_id');
    var jenisSelect = document.getElementById('jenis_surat_id');
    var tanggalSurat = document.getElementById('tanggal_surat').value;
    var divisiId = divisiSelect.value;
    var kodeDivisi = divisiSelect.selectedOptions[0] ? divisiSelect.selectedOptions[0].getAttribute('data-kode') : '...';
    var jenisSuratId = jenisSelect.value;
    var kodeJenis = jenisSelect.selectedOptions[0] ? jenisSelect.selectedOptions[0].getAttribute('data-kode') : '...';
    var tgl = tanggalSurat ? new Date(tanggalSurat) : null;
    var bulan = tgl && !isNaN(tgl.getMonth()) ? (tgl.getMonth()+1).toString().padStart(2, '0') : '...';
    var tahun = tgl && !isNaN(tgl.getFullYear()) ? tgl.getFullYear() : '...';
    
    // Get next nomor urut if both divisi and jenis surat are selected
    if (divisiId && jenisSuratId) {
        fetch(`/api/next-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}`)
            .then(response => response.json())
            .then(data => {
                var nomorUrut = data.next_nomor_urut || '...';
                var nomorSurat = `${nomorUrut.toString().padStart(3, '0')}/${kodeDivisi}/${kodeJenis}/INTENS/${bulan}/${tahun}`;
                
                document.getElementById('nomorSuratPreview').textContent = nomorSurat;
                document.getElementById('nomor_urut_hidden').value = nomorUrut;
                document.getElementById('nomor_surat_hidden').value = nomorSurat;
                
                // Update preview form values
                document.getElementById('preview_nomor_urut').value = nomorUrut;
                document.getElementById('preview_divisi_id').value = divisiId;
                document.getElementById('preview_jenis_surat_id').value = jenisSuratId;
                document.getElementById('preview_tanggal_surat').value = tanggalSurat;
            })
            .catch(error => {
                console.error('Error fetching nomor urut:', error);
            });
    } else {
        var nomorSurat = `.../.../.../INTENS/.../...`;
        document.getElementById('nomorSuratPreview').textContent = nomorSurat;
        document.getElementById('nomor_urut_hidden').value = '';
        document.getElementById('nomor_surat_hidden').value = nomorSurat;
    }
}

// When division changes, load jenis surat for that division
function updateJenisSuratOptions() {
    var divisiSelect = document.getElementById('divisi_id');
    var jenisSuratSelect = document.getElementById('jenis_surat_id');
    var divisiId = divisiSelect.value;
    
    if (!divisiId) {
        jenisSuratSelect.innerHTML = '<option value="">Pilih divisi terlebih dahulu</option>';
        updateNomorSuratPreview();
        return;
    }
    
    // Show loading
    jenisSuratSelect.innerHTML = '<option value="">Loading...</option>';
    
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
            updateNomorSuratPreview();
        })
        .catch(error => {
            console.error('Fetch error:', error);
            jenisSuratSelect.innerHTML = '<option value="">Error loading data</option>';
        });
}

// Preview surat
function previewSurat() {
    var nomorUrut = document.getElementById('nomor_urut_hidden').value;
    var divisiId = document.getElementById('divisi_id').value;
    var jenisSuratId = document.getElementById('jenis_surat_id').value;
    var tanggalSurat = document.getElementById('tanggal_surat').value;
    var filePath = document.querySelector('input[name="file_path"]').value;
    
    // Validasi
    if (!nomorUrut || !divisiId || !jenisSuratId || !tanggalSurat || !filePath) {
        alert('Data belum lengkap untuk preview. Pastikan semua field terisi.');
        return;
    }
    
    // Isi form preview
    document.getElementById('preview_nomor_urut').value = nomorUrut;
    document.getElementById('preview_divisi_id').value = divisiId;
    document.getElementById('preview_jenis_surat_id').value = jenisSuratId;
    document.getElementById('preview_tanggal_surat').value = tanggalSurat;
    document.getElementById('preview_file_path').value = filePath;
    
    // Submit form
    document.getElementById('previewForm').submit();
}

// User selection functions
let allUsers = [];
let filteredUsers = [];

function toggleUserSelection() {
    const isPrivate = document.getElementById('is_private').checked;
    const container = document.getElementById('userSelectionContainer');
    
    if (isPrivate) {
        container.classList.remove('d-none');
        loadUsers();
    } else {
        container.classList.add('d-none');
    }
}

function loadUsers() {
    fetch('/api/users')
        .then(response => response.json())
        .then(data => {
            allUsers = data;
            filteredUsers = data;
            renderUserList();
        })
        .catch(error => {
            console.error('Error loading users:', error);
            document.getElementById('userList').innerHTML = '<div class="text-center text-danger">Error loading users</div>';
        });
}

function searchUsers() {
    const query = document.getElementById('userSearch').value.toLowerCase();
    filteredUsers = allUsers.filter(user => 
        user.full_name.toLowerCase().includes(query) ||
        user.username.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query)
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

// Lock management variables
let lockExtensionInterval;
let inactivityTimeout;
let lastActivityTime;

// Lock nomor urut and update preview
function lockNomorUrutAndUpdate() {
    var divisiId = document.getElementById('divisi_id').value;
    var jenisSuratId = document.getElementById('jenis_surat_id').value;
    
    if (!divisiId || !jenisSuratId) {
        // Update preview dengan data yang ada meskipun belum ada nomor urut
        updateNomorSuratPreview();
        return;
    }
    
    // Update preview dulu dengan placeholder untuk responsivitas
    updateNomorSuratPreview();
    
    fetch(`/api/lock-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}`)
        .then(response => response.json())
        .then(data => {
            if (data.nomor_urut) {
                document.getElementById('nomor_urut_hidden').value = data.nomor_urut;
                // Update lagi setelah dapat nomor urut yang benar
                updateNomorSuratPreview();
                
                // Start lock management
                startLockManagement();
            }
        })
        .catch(error => {
            console.error('Error locking nomor urut:', error);
            // Tetap update preview meskipun ada error
            updateNomorSuratPreview();
        });
}

// Start lock management system
function startLockManagement() {
    // Setup activity tracking
    trackActivity();
    
    // Setup automatic lock extension every 25 minutes
    if (lockExtensionInterval) {
        clearInterval(lockExtensionInterval);
    }
    lockExtensionInterval = setInterval(extendLock, 25 * 60 * 1000); // 25 minutes
    
    // Setup heartbeat every 5 minutes to keep locks alive and cleanup expired ones
    if (window.heartbeatInterval) {
        clearInterval(window.heartbeatInterval);
    }
    window.heartbeatInterval = setInterval(function() {
        fetch('/api/heartbeat-nomor-urut-lock', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Heartbeat successful, locks extended');
            }
        })
        .catch(error => {
            console.warn('Heartbeat failed:', error);
        });
    }, 5 * 60 * 1000); // 5 minutes
    
    // Add activity event listeners
    document.addEventListener('mousemove', trackActivity);
    document.addEventListener('keypress', trackActivity);
    document.addEventListener('click', trackActivity);
    document.addEventListener('scroll', trackActivity);
}

function trackActivity() {
    lastActivityTime = Date.now();
    resetInactivityTimer();
}

function resetInactivityTimer() {
    if (inactivityTimeout) {
        clearTimeout(inactivityTimeout);
    }
    
    // Set 30-minute inactivity timeout
    inactivityTimeout = setTimeout(function() {
        if (confirm('Anda tidak aktif selama 30 menit. Lanjutkan proses upload?')) {
            // User wants to continue, extend lock and reset timer
            extendLock();
            resetInactivityTimer();
        } else {
            // User doesn't want to continue, cancel lock and redirect
            cancelLock();
        }
    }, 30 * 60 * 1000); // 30 minutes
}

function extendLock() {
    const divisiId = document.getElementById('divisi_id')?.value;
    const jenisSuratId = document.getElementById('jenis_surat_id')?.value;
    
    if (divisiId && jenisSuratId) {
        fetch('/api/extend-nomor-urut-lock', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                divisi_id: divisiId,
                jenis_surat_id: jenisSuratId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Lock extended successfully');
            } else {
                console.warn('Failed to extend lock');
            }
        })
        .catch(error => {
            console.error('Error extending lock:', error);
        });
    }
}

function cancelLock() {
    fetch('/api/cancel-nomor-urut-lock', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(`Lock cancelled. Cleaned up ${data.cancelled_locks} user locks and ${data.cleaned_expired_locks} expired locks.`);
        }
        cleanupLockManagement();
        window.location.href = '{{ route("admin.dashboard") }}';
    })
    .catch(error => {
        console.error('Error canceling lock:', error);
        // Redirect anyway
        cleanupLockManagement();
        window.location.href = '{{ route("admin.dashboard") }}';
    });
}

function cleanupLockManagement() {
    if (lockExtensionInterval) {
        clearInterval(lockExtensionInterval);
    }
    if (window.heartbeatInterval) {
        clearInterval(window.heartbeatInterval);
    }
    if (inactivityTimeout) {
        clearTimeout(inactivityTimeout);
    }
    
    // Remove event listeners
    document.removeEventListener('mousemove', trackActivity);
    document.removeEventListener('keypress', trackActivity);
    document.removeEventListener('click', trackActivity);
    document.removeEventListener('scroll', trackActivity);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update nomor surat saat halaman load
    updateNomorSuratPreview();
    
    // Event listeners untuk perubahan field
    document.getElementById('divisi_id').addEventListener('change', function() {
        updateJenisSuratOptions();
    });
    document.getElementById('jenis_surat_id').addEventListener('change', function() {
        lockNomorUrutAndUpdate(); // Lock ketika jenis surat berubah
    });
    document.getElementById('tanggal_surat').addEventListener('change', updateNomorSuratPreview);
    
    // Initialize user selection if already private
    if (document.getElementById('is_private').checked) {
        toggleUserSelection();
    }
    
    // Add cancel button event listener
    document.getElementById('btnCancelLock').addEventListener('click', cancelLock);
    
    // Add cleanup on page unload
    window.addEventListener('beforeunload', function(e) {
        // Cancel locks when page is being closed/navigated away
        if (navigator.sendBeacon) {
            // Use sendBeacon for reliable cleanup during page unload
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            navigator.sendBeacon('/api/cancel-nomor-urut-lock', formData);
        } else {
            // Fallback for older browsers
            fetch('/api/cancel-nomor-urut-lock', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                keepalive: true
            }).catch(() => {}); // Ignore errors during unload
        }
    });
    
    // Add visibility change detection
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden (user switched tabs, minimized, etc.)
            console.log('Page hidden - user may have navigated away');
            // Start a timer to cleanup locks if user doesn't return
            setTimeout(function() {
                if (document.hidden) {
                    // User still away after 2 minutes, cleanup locks
                    fetch('/api/cancel-nomor-urut-lock', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        }
                    }).catch(() => {});
                }
            }, 2 * 60 * 1000); // 2 minutes
        } else {
            // Page is visible again
            console.log('Page visible - user returned');
            // Extend locks since user is back
            extendLock();
        }
    });
    
    // Add focus/blur detection for additional safety
    window.addEventListener('blur', function() {
        console.log('Window lost focus');
    });
    
    window.addEventListener('focus', function() {
        console.log('Window gained focus');
        // Extend locks when user returns focus to window
        extendLock();
    });
    
    // If both divisi and jenis surat are pre-selected, lock immediately
    const divisiId = document.getElementById('divisi_id').value;
    const jenisSuratId = document.getElementById('jenis_surat_id').value;
    if (divisiId && jenisSuratId) {
        lockNomorUrutAndUpdate();
    }
});
</script>
@endsection 