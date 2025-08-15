@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Mode Manual - Upload Surat (Admin)</h2>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Mode Manual</h6>
                <p class="mb-0">Isi form terlebih dahulu untuk melihat nomor surat, terus upload file yang sudah memiliki nomor surat.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
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
                                    <label for="divisi_id" class="form-label">Pilih Divisi <span class="text-danger">*</span></label>
                                    <select class="form-select" id="divisi_id" name="divisi_id" required>
                                        <option value="">Pilih Divisi</option>
                                        @foreach($divisions as $divisi)
                                            <option value="{{ $divisi->id }}" data-kode="{{ $divisi->kode_divisi }}">
                                                {{ $divisi->nama_divisi }} ({{ $divisi->kode_divisi }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="jenis_surat_id" class="form-label">Jenis Surat <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jenis_surat_id" name="jenis_surat_id" required disabled>
                                        <option value="">Pilih divisi terlebih dahulu</option>
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
                                            <i class="fas fa-upload"></i> Upload & Verifikasi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('admin.surat.mode.selection') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Pilihan Mode
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Convert month number to Roman numeral  
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
    
    const divisiKode = '';
    let currentNomorSurat = '';
    let currentLockData = null;
    let lastActivityTime = Date.now();
    let inactivityTimeout = null;
    let debounceTimer = null;
    
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
        const divisiSelect = document.getElementById('divisi_id');
        const divisiId = divisiSelect.value;
        const kodeDivisi = divisiSelect.selectedOptions[0] ? divisiSelect.selectedOptions[0].getAttribute('data-kode') : '';
        const kodeJenis = jenisSuratSelect.selectedOptions[0] ? jenisSuratSelect.selectedOptions[0].getAttribute('data-kode') : '';
        
        // Buat display awal
        if (!jenisSuratId || !tanggalSurat || !divisiId) {
            nomorSuratDisplay.innerHTML = '<span class="text-muted">Isi form di atas untuk generate nomor surat</span>';
            copySection.style.display = 'none';
            uploadSection.style.display = 'none';
            return;
        }
        
        // Clear previous debounce timer
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        
        // Debounce API call dengan 500ms delay
        debounceTimer = setTimeout(() => {
            const tanggalObj = new Date(tanggalSurat + 'T00:00:00'); // Fix timezone issues
            const month = tanggalObj.getMonth() + 1; // 1-based
            const year = tanggalObj.getFullYear();
            
            console.log('Manual mode: API call dimulai...', {
                divisi_id: divisiId,
                jenis_surat_id: jenisSuratId,
                tanggal_surat: tanggalSurat
            });
            
            fetch('/api/generate-nomor-surat-manual', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    divisi_id: divisiId,
                    jenis_surat_id: jenisSuratId,
                    tanggal_surat: tanggalSurat
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('API response:', data);
                if (data.next_nomor_urut) {
                    const nomorUrut = String(data.next_nomor_urut).padStart(3, '0');
                    const nomorSurat = `${nomorUrut}/${kodeDivisi}/${kodeJenis}/INTENS/${monthToRoman(month)}/${year}`;
                    
                    currentNomorSurat = nomorSurat;
                    console.log('currentNomorSurat set to:', currentNomorSurat);
                    nomorSuratDisplay.innerHTML = `<h4 class="text-success fw-bold mb-0">${nomorSurat}</h4>`;
                    copySection.style.display = 'block';
                    console.log('copySection displayed, element:', copySection);
                    uploadSection.style.display = 'block';
                    console.log('uploadSection displayed');
                    
                    console.log('Admin manual: generated nomor surat:', nomorSurat);
                    
                    // lock nomor surat ini
                    lockNomorSurat(jenisSuratId, nomorUrut);
                } else {
                    console.error('Admin manual: ga dapet nomor_urut:', data);
                    nomorSuratDisplay.innerHTML = '<span class="text-danger">Error: Ga bisa generate nomor surat</span>';
                    copySection.style.display = 'none';
                    uploadSection.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Admin manual: Error ambil nomor surat:', error);
                nomorSuratDisplay.innerHTML = '<span class="text-danger">Error saat ngambil nomor surat</span>';
                copySection.style.display = 'none';
                uploadSection.style.display = 'none';
            });
        }, 500);
    }
    
    // Function untuk lock nomor surat
    function lockNomorSurat(jenisSuratId, nomorUrut) {
        // Send lock request to server
        fetch('/api/lock-nomor-urut', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                jenis_surat_id: jenisSuratId,
                nomor_urut: nomorUrut
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Admin manual: Lock successful:', data.data);
                currentLockData = data.data;
                setupLockCleanup(); // Setup cleanup when user leaves
            } else {
                console.error('Admin manual: Lock failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Admin manual: Lock error:', error);
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
                    console.log('Admin manual: Heartbeat successful, locks extended');
                }
            })
            .catch(error => {
                console.warn('Admin manual: Heartbeat failed:', error);
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
                console.log('Admin manual: Lock cleaned up on page leave');
            }
        });
        
        // Detect navigation away from manual page using pagehide event
        window.addEventListener('pagehide', function(e) {
            if (currentLockData) {
                navigator.sendBeacon('/api/cancel-nomor-urut-lock', JSON.stringify({
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }));
                console.log('Admin manual: Lock cleaned up on page hide');
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
                window.location.href = '{{ route("admin.surat.mode.selection") }}';
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
    
    // Event listeners untuk update nomor surat otomatis
    document.getElementById('divisi_id').addEventListener('change', function() {
        const divisiId = this.value;
        const jenisSuratSelect = document.getElementById('jenis_surat_id');
        
        if (!divisiId) {
            jenisSuratSelect.innerHTML = '<option value="">Pilih divisi terlebih dahulu</option>';
            jenisSuratSelect.disabled = true;
            updateNomorSurat();
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
                updateNomorSurat();
            })
            .catch(error => {
                console.error('Fetch error:', error);
                jenisSuratSelect.innerHTML = '<option value="">Error loading data</option>';
            });
    });
    
    jenisSuratSelect.addEventListener('change', updateNomorSurat);
    tanggalSuratInput.addEventListener('change', updateNomorSurat);
    
    // Enable/disable upload button based on required fields
    function checkFormValidity() {
        const perihal = perihalInput.value;
        const file = fileInput.files[0];
        const isFormValid = perihal && file && currentNomorSurat;
        
        uploadBtn.disabled = !isFormValid;
    }
    
    // Event listeners for form validation
    perihalInput.addEventListener('input', checkFormValidity);
    fileInput.addEventListener('change', checkFormValidity);
    
    // Upload button click handler
    uploadBtn.addEventListener('click', function() {
        if (!uploadBtn.disabled) {
            submitUpload();
        }
    });
    
    // Paste perihal otomatis dengan nomor surat
    perihalInput.addEventListener('focus', function() {
        if (!this.value && currentNomorSurat) {
            this.value = `Surat dengan nomor ${currentNomorSurat}`;
        }
    });
});

function submitUpload() {
    const formData = new FormData();
    const fileInput = document.getElementById('file');
    const perihalInput = document.getElementById('perihal');
    const divisiSelect = document.getElementById('divisi_id');
    const jenisSuratSelect = document.getElementById('jenis_surat_id');
    const tanggalSuratInput = document.getElementById('tanggal_surat');
    const tanggalDiterimaInput = document.getElementById('tanggal_diterima');
    const isPrivateCheckbox = document.getElementById('is_private');
    
    // Validasi
    if (!fileInput.files[0]) {
        alert('Pilih file terlebih dahulu');
        return;
    }
    
    if (!perihalInput.value) {
        alert('Isi perihal terlebih dahulu');
        return;
    }
    
    if (!currentNomorSurat) {
        alert('Generate nomor surat terlebih dahulu');
        return;
    }
    
    // Prepare form data
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('file', fileInput.files[0]);
    formData.append('perihal', perihalInput.value);
    formData.append('divisi_id', divisiSelect.value);
    formData.append('jenis_surat_id', jenisSuratSelect.value);
    formData.append('tanggal_surat', tanggalSuratInput.value);
    formData.append('tanggal_diterima', tanggalDiterimaInput.value);
    formData.append('nomor_surat', currentNomorSurat);
    formData.append('is_private', isPrivateCheckbox.checked ? '1' : '0');
    
    // Add selected users if private
    if (isPrivateCheckbox.checked) {
        const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked');
        selectedUsers.forEach(checkbox => {
            formData.append('selected_users[]', checkbox.value);
        });
    }
    
    // Show loading
    const uploadBtn = document.getElementById('uploadBtn');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    uploadBtn.disabled = true;
    
    console.log('Admin manual: Uploading file...', {
        file: fileInput.files[0].name,
        perihal: perihalInput.value,
        nomor_surat: currentNomorSurat
    });
    
    // Submit
    fetch('{{ route("admin.surat.manual.handleUpload") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        console.log('Admin manual: Upload response received');
        
        // Display result
        const verificationResult = document.getElementById('verificationResult');
        verificationResult.innerHTML = html;
        verificationResult.style.display = 'block';
        verificationResult.className = 'alert alert-success';
        
        // Scroll to result
        verificationResult.scrollIntoView({ behavior: 'smooth' });
        
        // Reset button
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
        
        console.log('Admin manual: Upload completed');
    })
    .catch(error => {
        console.error('Admin manual: Upload error:', error);
        
        const verificationResult = document.getElementById('verificationResult');
        verificationResult.innerHTML = '<strong>Error:</strong> Terjadi kesalahan saat upload file. Silakan coba lagi.';
        verificationResult.style.display = 'block';
        verificationResult.className = 'alert alert-danger';
        
        // Reset button
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
    });
}

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

function cancelProcess() {
    if (confirm('Yakin ingin membatalkan proses upload?')) {
        window.location.href = '{{ route("admin.surat.mode.selection") }}';
    }
}
</script>
@endsection
