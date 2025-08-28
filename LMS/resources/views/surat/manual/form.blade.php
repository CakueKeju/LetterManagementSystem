@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Mode Manual - Upload Surat</h2>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Mode Manual</h6>
                <p class="mb-0">Isi form terlebih dahulu untuk melihat nomor surat, terus upload file yang sudah memiliki nomor surat.</p>
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
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Form Data Surat</h5>
                        </div>
                        <div class="card-body">
                            <form id="suratForm">
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
                                            <option value="{{ $jenis->id }}" data-kode="{{ $jenis->kode_jenis }}">
                                                {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="perihal" class="form-label">Perihal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="perihal" name="perihal" required maxlength="255">
                                </div>

                                <div class="mb-3">
                                    <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" 
                                           value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="mb-3" style="display: none;">
                                    <label for="tanggal_diterima" class="form-label">Tanggal Diterima</label>
                                    <input type="date" class="form-control" id="tanggal_diterima" name="tanggal_diterima" 
                                           value="{{ date('Y-m-d') }}">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" onchange="toggleUserSelection()">
                                        <label class="form-check-label" for="is_private">
                                            Surat Private
                                        </label>
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
                                                    Admin dan Anda (pengupload) tidak perlu dipilih karena sudah otomatis memiliki akses.
                                                    Jika tidak ada user yang dipilih, hanya Anda dan Admin yang dapat mengakses.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Nomor Surat & Upload -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-hashtag me-2"></i>Nomor Surat & Upload</h5>
                        </div>
                        <div class="card-body">
                            <!-- Nomor Surat Display -->
                            <div class="mb-4">
                                <label class="form-label">Nomor Surat Tersedia:</label>
                                <div id="nomorSuratDisplay" class="p-3 border border-success rounded bg-light text-center">
                                    <span class="text-muted">Lengkapi form untuk melihat nomor surat</span>
                                </div>
                                <div id="copySection" style="display: none;" class="text-center mt-2">
                                    <button type="button" id="copyNomorBtn" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-copy"></i> Copy Nomor
                                    </button>
                                </div>
                            </div>

                            <!-- Upload Section -->
                            <div id="uploadSection" style="display: none;">
                                <div class="alert alert-warning">
                                    <small><i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Pastikan file sudah berisi nomor surat di atas!</strong></small>
                                </div>

                                <form id="uploadForm" enctype="multipart/form-data">
                                    @csrf
                                    
                                    <div class="mb-3">
                                        <label for="file" class="form-label">File Surat <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="file" name="file" required 
                                               accept=".pdf,.doc,.docx">
                                        <div class="form-text">
                                            Format: PDF, DOC, DOCX
                                        </div>
                                    </div>

                                    <div id="verificationResult" class="alert" style="display: none;"></div>

                                    <div class="d-grid">
                                        <button type="button" id="uploadBtn" class="btn btn-primary" disabled>
                                            <i class="fas fa-upload me-2"></i>Upload
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('surat.mode.selection') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Pilihan Mode
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// =================================
// FUNGSI UTAMA
// =================================

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
    const nomorSuratDisplay = document.getElementById('nomorSuratDisplay');
    const copySection = document.getElementById('copySection');
    const uploadSection = document.getElementById('uploadSection');
    const fileInput = document.getElementById('file');
    const uploadBtn = document.getElementById('uploadBtn');
    const verificationResult = document.getElementById('verificationResult');
    
    const divisiKode = '{{ Auth::user()->division->kode_divisi }}';
    let currentNomorSurat = '';
    let currentLockData = null;
    let lastActivityTime = Date.now();
    let inactivityTimeout = null;
    let debounceTimer = null; // Add debounce timer
    
    // Setup copy button event listener
    const copyNomorBtn = document.getElementById('copyNomorBtn');
    if (copyNomorBtn) {
        copyNomorBtn.addEventListener('click', function() {
            copyNomorSurat();
        });
    }
    
    // ==========================================================================================
    
    // update nomor surat otomatis pas form berubah
    function updateNomorSurat() {
        const jenisSuratId = jenisSuratSelect.value;
        const tanggalSurat = tanggalSuratInput.value;
        const perihal = perihalInput.value.trim();
        
        console.log('Manual mode: updating nomor surat:', { jenisSuratId, tanggalSurat, perihal });
        
        if (!jenisSuratId || !tanggalSurat || !perihal) {
            nomorSuratDisplay.innerHTML = '<span class="text-muted">Lengkapi form dulu buat liat nomor surat</span>';
            copySection.style.display = 'none';
            uploadSection.style.display = 'none';
            return;
        }
        
        const selectedOption = jenisSuratSelect.options[jenisSuratSelect.selectedIndex];
        const jenisKode = selectedOption.getAttribute('data-kode');
        
        const date = new Date(tanggalSurat);
        const month = monthToRoman(date.getMonth() + 1);
        const year = String(date.getFullYear());
        
        console.log('Manual mode: calculated date components:', { month, year, tanggalSurat });
        
        // loading state
        nomorSuratDisplay.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Ngambil nomor surat...</span>';
        
        // ambil nomor urut lewat AJAX
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
            console.log('API response:', data);
            if (data.next_nomor_urut) {
                const nomorUrut = String(data.next_nomor_urut).padStart(3, '0');
                const nomorSurat = `${nomorUrut}/${divisiKode}/${jenisKode}/INTENS/${month}/${year}`;
                
                currentNomorSurat = nomorSurat;
                console.log('currentNomorSurat set to:', currentNomorSurat);
                nomorSuratDisplay.innerHTML = `<h4 class="text-success fw-bold mb-0">${nomorSurat}</h4>`;
                copySection.style.display = 'block';
                console.log('copySection displayed, element:', copySection);
                uploadSection.style.display = 'block';
                console.log('uploadSection displayed');
                
                console.log('Manual mode: generated nomor surat:', nomorSurat);
                
                // lock nomor surat ini (simplified - no complex lock data needed)
                lockNomorSurat(jenisSuratId, nomorUrut);
                
                trackActivity(); // reset timer aktivitas
            } else {
                console.error('Manual mode: ga dapet nomor_urut:', data);
                nomorSuratDisplay.innerHTML = '<span class="text-danger">Error: Ga bisa generate nomor surat</span>';
                copySection.style.display = 'none';
                uploadSection.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Manual mode: Error ambil nomor surat:', error);
            nomorSuratDisplay.innerHTML = '<span class="text-danger">Error saat ngambil nomor surat</span>';
            copySection.style.display = 'none';
            uploadSection.style.display = 'none';
        });
    }
    
    // Debounced version untuk input yang sering berubah
    function updateNomorSuratDebounced() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            updateNomorSurat();
        }, 300); // 300ms debounce
    }
    
    // Instant version untuk perubahan select/date
    function updateNomorSuratInstant() {
        updateNomorSurat();
    }
    
    // ==========================================================================================
    
    // lock nomor surat
    function lockNomorSurat(jenisSuratId, nomorUrut) {
        const formData = new FormData();
        formData.append('divisi_id', {{ Auth::user()->divisi_id }});
        formData.append('jenis_surat_id', jenisSuratId);
        formData.append('perihal', perihalInput.value);
        formData.append('tanggal_surat', tanggalSuratInput.value);
        formData.append('tanggal_diterima', document.getElementById('tanggal_diterima').value);
        formData.append('is_private', document.getElementById('is_private').checked ? '1' : '0');
        
        // Include selected users for private access
        if (document.getElementById('is_private').checked) {
            const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked');
            selectedUsers.forEach(checkbox => {
                formData.append('selected_users[]', checkbox.value);
            });
        }
        
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        fetch('{{ route("surat.manual.generate") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentLockData = data.data;
                setupLockCleanup(); // Setup cleanup when user leaves
            }
        })
        .catch(error => {
            console.error('Lock error:', error);
        });
    }
    
    // Setup lock cleanup system
    function setupLockCleanup() {
        // Keep lock alive every 25 minutes (5 minutes before 30-minute expiry)
        if (window.lockExtensionInterval) {
            clearInterval(window.lockExtensionInterval);
        }
        window.lockExtensionInterval = setInterval(extendLock, 25 * 60 * 1000);
        
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
                    console.log('Manual mode: Heartbeat successful, locks extended');
                }
            })
            .catch(error => {
                console.warn('Manual mode: Heartbeat failed:', error);
            });
        }, 5 * 60 * 1000); //duration
        
        // Track user activity
        document.addEventListener('keypress', trackActivity);
        document.addEventListener('click', trackActivity);
        document.addEventListener('scroll', trackActivity);
        document.addEventListener('mousemove', trackActivity);
        
        // Handle page unload/leave events
        window.addEventListener('beforeunload', function(e) {
            // Cancel lock when user is actually leaving the page
            if (currentLockData) {
                navigator.sendBeacon('/api/cancel-nomor-urut-lock', JSON.stringify({
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }));
                console.log('Manual mode: Lock cleaned up on page leave');
            }
        });
        
        // Detect navigation away from manual page using pagehide event
        window.addEventListener('pagehide', function(e) {
            if (currentLockData) {
                navigator.sendBeacon('/api/cancel-nomor-urut-lock', JSON.stringify({
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }));
                console.log('Manual mode: Lock cleaned up on page hide');
            }
        });
        
        // Handle visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // User returned to tab, extend lock if still reasonable
                const timeSinceLastActivity = Date.now() - lastActivityTime;
                if (timeSinceLastActivity < 30 * 60 * 1000) { // 30 minutes
                    extendLock();
                }
            }
        });
        
        // Start inactivity timer
        resetInactivityTimer();
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
                extendLock();
                resetInactivityTimer();
            } else {
                // User is inactive, cleanup lock
                fetch('/api/cancel-nomor-urut-lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                alert('Session expired karena tidak aktif. Anda akan diarahkan ke halaman pemilihan mode.');
                window.location.href = '{{ route("surat.mode.selection") }}';
            }
        }, 30 * 60 * 1000); // 30 minutes
    }
    
    // Cleanup on window close
    window.addEventListener('unload', function() {
        if (currentLockData) {
            // Clear intervals
            if (window.lockExtensionInterval) {
                clearInterval(window.lockExtensionInterval);
            }
            if (window.heartbeatInterval) {
                clearInterval(window.heartbeatInterval);
            }
            if (inactivityTimeout) {
                clearTimeout(inactivityTimeout);
            }
            
            navigator.sendBeacon('/api/cancel-nomor-urut-lock', JSON.stringify({
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }));
        }
    });
    
    function extendLock() {
        if (currentLockData) {
            fetch('/api/extend-nomor-urut-lock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        }
    }
    
    // Event listeners
    jenisSuratSelect.addEventListener('change', function() {
        console.log('Jenis surat changed to:', this.value);
        updateNomorSuratInstant(); // Instant update for dropdown changes
    });
    
    // Real-time update untuk tanggal surat
    tanggalSuratInput.addEventListener('change', function() {
        console.log('Tanggal surat changed to:', this.value);
        updateNomorSuratInstant(); // Instant update for date changes
    });
    
    // Real-time update saat user mengetik tanggal
    tanggalSuratInput.addEventListener('input', function() {
        console.log('Tanggal surat input:', this.value);
        updateNomorSuratDebounced(); 
    });
    
    // Real-time update untuk perihal
    perihalInput.addEventListener('input', function() {
        console.log('Perihal input:', this.value);
        updateNomorSuratDebounced();
    });
    
    // File input change
    fileInput.addEventListener('change', function() {
        uploadBtn.disabled = !this.files.length;
        verificationResult.style.display = 'none';
        console.log('File selected:', this.files.length > 0 ? this.files[0].name : 'none');
        console.log('Upload button enabled:', !uploadBtn.disabled);
    });
    
    // Upload button click
    uploadBtn.addEventListener('click', function() {
        if (!fileInput.files.length) {
            alert('Pilih file terlebih dahulu!');
            return;
        }
        
        if (!currentNomorSurat) {
            alert('Generate nomor surat terlebih dahulu sebelum upload!');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('nomor_surat', currentNomorSurat);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        
        fetch('{{ route("surat.manual.handleUpload") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success === true && data.redirect) {
                // sukses upload dengan redirect
                window.location.href = data.redirect;
            } else if (data && data.success === false && data.redirect) {
                // gagal verifikasi dengan redirect
                window.location.href = data.redirect;
            } else if (data && data.success === false && data.message) {
                // error
                verificationResult.className = 'alert alert-danger';
                verificationResult.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
                verificationResult.style.display = 'block';
                
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload';
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            verificationResult.className = 'alert alert-danger';
            verificationResult.innerHTML = '<i class="fas fa-times-circle"></i> Error upload file';
            verificationResult.style.display = 'block';
            
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload';
        });
    });
    
    // Initial check saat halaman load
    setTimeout(function() {
        if (jenisSuratSelect.value && tanggalSuratInput.value && perihalInput.value.trim()) {
            updateNomorSuratInstant();
        }
    }, 500);
});

function copyNomorSurat() {
    console.log('copyNomorSurat called, currentNomorSurat:', currentNomorSurat);
    if (!currentNomorSurat) {
        alert('Belum ada nomor surat yang digenerate');
        return;
    }
    
    // Clipboard
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(currentNomorSurat).then(function() {
            const btn = document.getElementById('copyNomorBtn');
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }
            console.log('Nomor surat copied successfully:', currentNomorSurat);
        }).catch(function(err) {
            console.error('Clipboard API failed:', err);
            fallbackCopyToClipboard(currentNomorSurat);
        });
    } else {
        fallbackCopyToClipboard(currentNomorSurat);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        document.body.removeChild(textArea);
        console.log('Fallback copy success');
        
        // Update button
        const btn = document.getElementById('copyNomorBtn');
        if (btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        }
        
    } catch (err) {
        document.body.removeChild(textArea);
        console.error('Fallback copy failed:', err);
        alert('Copy gagal. Nomor surat: ' + text);
    }
}

// ============================= USER SELECTION FUNCTIONS =============================

let allUsers = [];
let filteredUsers = [];
let selectedUserIds = new Set(); // Track selected user IDs

function toggleUserSelection() {
    const isPrivate = document.getElementById('is_private').checked;
    const userSection = document.getElementById('userSelectionSection');
    
    if (isPrivate) {
        userSection.style.display = 'block';
        loadUsers();
    } else {
        userSection.style.display = 'none';
        selectedUserIds.clear(); // Clear selections when hiding
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
    
    // Save current selections before filtering
    saveCurrentSelections();
    
    filteredUsers = allUsers.filter(user => 
        user.full_name.toLowerCase().includes(searchTerm) ||
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );
    renderUserList();
}

function saveCurrentSelections() {
    // Save currently checked checkboxes to selectedUserIds
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]:checked');
    checkboxes.forEach(checkbox => {
        selectedUserIds.add(parseInt(checkbox.value));
    });
    
    // Also remove unchecked ones
    const allCheckboxes = document.querySelectorAll('input[name="selected_users[]"]');
    allCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            selectedUserIds.delete(parseInt(checkbox.value));
        }
    });
}

function renderUserList() {
    const userList = document.getElementById('userList');
    
    if (filteredUsers.length === 0) {
        userList.innerHTML = '<div class="text-center text-muted">Tidak ada user ditemukan</div>';
        return;
    }
    
    let html = '';
    filteredUsers.forEach(user => {
        const isChecked = selectedUserIds.has(user.id) ? 'checked' : '';
        html += `
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="selected_users[]" value="${user.id}" id="user_${user.id}" ${isChecked} onchange="updateUserSelection(${user.id}, this.checked)">
                <label class="form-check-label" for="user_${user.id}">
                    <strong>${user.full_name}</strong><br>
                    <small class="text-muted">${user.username} • ${user.email}</small>
                </label>
            </div>
        `;
    });
    
    userList.innerHTML = html;
}

function updateUserSelection(userId, isChecked) {
    if (isChecked) {
        selectedUserIds.add(userId);
    } else {
        selectedUserIds.delete(userId);
    }
}

function selectAllUsers() {
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        const userId = parseInt(checkbox.value);
        const shouldCheck = !allChecked;
        checkbox.checked = shouldCheck;
        
        if (shouldCheck) {
            selectedUserIds.add(userId);
        } else {
            selectedUserIds.delete(userId);
        }
    });
}

</script>
@endsection
