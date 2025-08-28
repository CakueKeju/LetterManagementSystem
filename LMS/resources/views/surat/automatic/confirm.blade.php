@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konfirmasi Data Surat</h2>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Hide PDF parser results for automatic mode as requested --}}
    {{-- @if(isset($extracted_text) && !empty($extracted_text))
    <div class="mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Hasil Ekstraksi Teks ({{ $extraction_method ?? 'Unknown' }})</h6>
            </div>
            <div class="card-body">
                <textarea class="form-control" rows="6" readonly>{{ $extracted_text }}</textarea>
            </div>
        </div>
    </div>
    @endif --}}
    
    {{-- @if(isset($ocr_error) && $ocr_error)
    <div class="mb-4">
        <div class="alert alert-warning">
            <strong>Peringatan Ekstraksi:</strong> {{ $ocr_error }}
        </div>
        </div>
    @endif --}}

    <form action="{{ route('surat.store') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $file_path }}">
        <input type="hidden" name="file_size" value="{{ $file_size }}">
        <input type="hidden" name="mime_type" value="{{ $mime_type }}">
        <input type="hidden" name="nomor_surat" value="{{ $nomor_surat ?? '' }}">
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Form Data Surat</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nomor Surat (Preview)</label>
                    <div id="nomorSuratPreview" class="form-control bg-light" style="font-weight:bold;">
                        {{ $nomor_surat ?? '.../.../.../INTENS/.../...' }}
                    </div>
                </div>
                <input type="hidden" name="nomor_urut" id="nomor_urut_hidden" value="{{ $nomor_urut ?? '' }}">
                <input type="hidden" name="nomor_surat" id="nomor_surat_hidden" value="{{ $nomor_surat ?? '.../.../.../INTENS/.../...' }}">
                
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
                            <option value="{{ $jenis->id }}" {{ ($jenis_surat_id ?? '') == $jenis->id ? 'selected' : '' }} data-kode="{{ $jenis->kode_jenis }}">
                                {{ $jenis->nama_jenis }} ({{ $jenis->kode_jenis }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="perihal" class="form-label">Perihal</label>
                    <input type="text" class="form-control" id="perihal" name="perihal" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
                    <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" value="{{ date('Y-m-d') }}" required>
                </div>
                <input type="hidden" name="tanggal_diterima" value="{{ date('Y-m-d') }}">
                <div class="mb-3">
                    <label for="is_private" class="form-label">Status</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1" onchange="toggleUserSelection()">
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
                                    Admin dan Anda (pengupload) tidak perlu dipilih karena sudah otomatis memiliki akses.
                                    Jika tidak ada user yang dipilih, hanya Anda dan Admin yang dapat mengakses.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Konfirmasi</button>
        <button type="button" class="btn btn-danger" id="btnCancelLock">Batalkan</button>
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
// Convert month number to Roman numeral
function monthToRoman(month) {
    const romanNumerals = {
        1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI',
        7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII'
    };
    return romanNumerals[month] || month;
}

// Nomor surat preview update
function updateNomorSuratPreview() {
    var divisiInput = document.getElementById('divisi_id');
    var jenisSelect = document.getElementById('jenis_surat_id');
    var tanggalSuratInput = document.getElementById('tanggal_surat');
    var tanggalSurat = tanggalSuratInput.value;
    var divisiId = divisiInput.value;
    var kodeDivisi = divisiInput.getAttribute('data-kode') || '...';
    var jenisSuratId = jenisSelect.value;
    var kodeJenis = jenisSelect.selectedOptions[0] ? jenisSelect.selectedOptions[0].getAttribute('data-kode') : '...';
    
    // Gunakan tanggal surat untuk bulan dan tahun (BUKAN tanggal upload)
    var tgl = tanggalSurat ? new Date(tanggalSurat) : new Date();
    var bulan = !isNaN(tgl.getMonth()) ? monthToRoman(tgl.getMonth() + 1) : '...';
    var tahun = !isNaN(tgl.getFullYear()) ? tgl.getFullYear() : '...';
    
    // Gunakan nomor urut yang sudah di-lock dari server
    var nomorUrut = document.getElementById('nomor_urut_hidden').value || '...';
    var nomorSurat = `${nomorUrut.toString().padStart(3, '0')}/${kodeDivisi}/${kodeJenis}/INTENS/${bulan}/${tahun}`;
    
    // Update preview display
    document.getElementById('nomorSuratPreview').textContent = nomorSurat;
    document.getElementById('nomor_surat_hidden').value = nomorSurat;
    
    // Update preview form values
    document.getElementById('preview_nomor_urut').value = nomorUrut;
    document.getElementById('preview_divisi_id').value = divisiId;
    document.getElementById('preview_jenis_surat_id').value = jenisSuratId;
    document.getElementById('preview_tanggal_surat').value = tanggalSurat || new Date().toISOString().split('T')[0];
}

// Lock nomor urut dan update preview
function lockNomorUrutAndUpdate() {
    var divisiId = document.getElementById('divisi_id').value;
    var jenisSuratId = document.getElementById('jenis_surat_id').value;
    var tanggalSurat = document.getElementById('tanggal_surat').value;
    
    if (!divisiId || !jenisSuratId) {
        // Update preview dengan data yang ada meskipun belum ada nomor urut
        updateNomorSuratPreview();
        return;
    }
    
    // Check if we already have the right lock for current selection including date
    var currentLock = window.currentLockInfo || {};
    if (currentLock.divisi_id == divisiId && 
        currentLock.jenis_surat_id == jenisSuratId && 
        currentLock.tanggal_surat == tanggalSurat) {
        // We already have the right lock, just update preview
        updateNomorSuratPreview();
        return;
    }
    
    // Update preview dulu dengan placeholder untuk responsivitas
    updateNomorSuratPreview();
    
    // Build URL with date parameter if available
    var url = `/api/lock-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}`;
    if (tanggalSurat) {
        url += `&tanggal_surat=${tanggalSurat}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.nomor_urut) {
                document.getElementById('nomor_urut_hidden').value = data.nomor_urut;
                // Store current lock info to avoid duplicate calls
                window.currentLockInfo = {
                    divisi_id: divisiId,
                    jenis_surat_id: jenisSuratId,
                    nomor_urut: data.nomor_urut,
                    tanggal_surat: tanggalSurat
                };
                // Update lagi setelah dapat nomor urut yang benar
                updateNomorSuratPreview();
            }
        })
        .catch(error => {
            console.error('Error locking nomor urut:', error);
            // Tetap update preview meskipun ada error
            updateNomorSuratPreview();
        });
}

// Update preview tanpa delay untuk perubahan tanggal
function updateNomorSuratPreviewInstant() {
    // Untuk perubahan tanggal, kita perlu cek nomor urut yang baru
    updateNomorUrutForDate();
}

// Update nomor urut berdasarkan tanggal baru
function updateNomorUrutForDate() {
    var divisiId = document.getElementById('divisi_id').value;
    var jenisSuratId = document.getElementById('jenis_surat_id').value;
    var tanggalSurat = document.getElementById('tanggal_surat').value;
    
    if (!divisiId || !jenisSuratId || !tanggalSurat) {
        // Update preview dengan data yang ada
        updateNomorSuratPreview();
        return;
    }
    
    // Check if date actually changed to avoid unnecessary API calls
    if (window.lastLockedDate === tanggalSurat && window.currentLockInfo && 
        window.currentLockInfo.divisi_id == divisiId && 
        window.currentLockInfo.jenis_surat_id == jenisSuratId) {
        // Date hasn't changed for same selection, just update preview
        updateNomorSuratPreview();
        return;
    }
    
    // Store the date we're locking for
    window.lastLockedDate = tanggalSurat;
    
    // Direct call to lock dengan tanggal baru - let server handle cleanup
    fetch(`/api/lock-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}&tanggal_surat=${tanggalSurat}`)
    .then(response => response.json())
    .then(data => {
        if (data.nomor_urut) {
            // Update nomor urut hidden input dengan locked value
            document.getElementById('nomor_urut_hidden').value = data.nomor_urut;
            // Store current lock info to avoid duplicate calls
            window.currentLockInfo = {
                divisi_id: divisiId,
                jenis_surat_id: jenisSuratId,
                nomor_urut: data.nomor_urut,
                tanggal_surat: tanggalSurat
            };
            // Update preview display
            updateNomorSuratPreview();
            console.log('Updated and locked nomor urut for new date:', data.nomor_urut);
        } else if (data.error) {
            console.error('Error locking nomor urut:', data.error);
            // Show preview saja jika tidak bisa lock
            return fetch(`/api/preview-nomor-urut?divisi_id=${divisiId}&jenis_surat_id=${jenisSuratId}&tanggal_surat=${tanggalSurat}`)
                .then(response => response.json())
                .then(data => {
                    if (data.nomor_urut) {
                        document.getElementById('nomor_urut_hidden').value = data.nomor_urut;
                        updateNomorSuratPreview();
                        console.log('Updated nomor urut preview (fallback):', data.nomor_urut);
                    }
                });
        }
    })
    .catch(error => {
        console.error('Error updating nomor urut for date:', error);
        // Tetap update preview meskipun ada error
        updateNomorSuratPreview();
    });
}

// Debounced version untuk input yang sering berubah
let debounceTimer;
function updateNomorSuratPreviewDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() {
        updateNomorSuratPreview();
    }, 200); // 200ms debounce
}

// Legacy function untuk compatibility
function lockNomorUrutAjax() {
    lockNomorUrutAndUpdate();
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

// Cancel lock
function cancelLock() {
    fetch('/api/cancel-nomor-urut-lock').then(() => {
        window.location.href = "{{ route('home') }}";
    });
}

// Event listeners untuk update nomor surat
document.addEventListener('DOMContentLoaded', function() {
        // Set tanggal default jika belum ada
        var tanggalSuratInput = document.getElementById('tanggal_surat');
        if (!tanggalSuratInput.value) {
            tanggalSuratInput.value = new Date().toISOString().split('T')[0];
        }
        
        // Lock nomor urut jika jenis surat sudah dipilih
        var jenisSelect = document.getElementById('jenis_surat_id');
        if (jenisSelect.value) {
            // Initialize current lock info to prevent unnecessary calls
            window.currentLockInfo = null;
            window.lastLockedDate = null;
            lockNomorUrutAndUpdate();
        } else {
            updateNomorSuratPreview();
        }
        
        // Event listeners untuk perubahan field - real time update
        jenisSelect.addEventListener('change', function() {
            console.log('Jenis surat changed to:', this.value);
            // Reset lock info when jenis surat changes
            window.currentLockInfo = null;
            window.lastLockedDate = null;
            lockNomorUrutAndUpdate();
        });
        
        // Update preview saat tanggal berubah - real time update
        tanggalSuratInput.addEventListener('change', function() {
            // Reset lock info when date changes
            window.currentLockInfo = null;
            window.lastLockedDate = null;
            updateNomorSuratPreviewInstant();
        });
        tanggalSuratInput.addEventListener('input', function() {
            // Reset lock info when date changes
            window.currentLockInfo = null;
            window.lastLockedDate = null;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                updateNomorUrutForDate();
            }, 300); // 300ms debounce untuk input
        });
        
        // Initialize lock management
        initializeLockManagement();
    });

    // Lock management with improved timeout and page leave detection
    let lockExtensionInterval;
    let inactivityTimeout;
    let lastActivityTime = Date.now();
    
    function initializeLockManagement() {
        // Keep lock alive every 25 minutes (5 minutes before 30-minute expiry)
        lockExtensionInterval = setInterval(extendLock, 25 * 60 * 1000);
        
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
        
        // Reset inactivity timer on user interaction
        resetInactivityTimer();
        
        // Track user activity
        document.addEventListener('mousemove', trackActivity);
        document.addEventListener('keypress', trackActivity);
        document.addEventListener('click', trackActivity);
        document.addEventListener('scroll', trackActivity);
        
        // Handle page unload/leave events
        window.addEventListener('beforeunload', function(e) {
            // Cancel lock when user is actually leaving the page
            navigator.sendBeacon('/api/cancel-nomor-urut-lock', JSON.stringify({
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }));
        });
        
        // Handle visibility change (tab switching - not leaving page)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // User returned to tab, extend lock if still within reasonable time
                const timeSinceLastActivity = Date.now() - lastActivityTime;
                if (timeSinceLastActivity < 30 * 60 * 1000) { // 30 minutes
                    extendLock();
                }
            }
        });
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
            window.location.href = '{{ route("home") }}';
        })
        .catch(error => {
            console.error('Error canceling lock:', error);
            // Redirect anyway
            cleanupLockManagement();
            window.location.href = '{{ route("home") }}';
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

    // Add page unload detection for automatic lock cleanup
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

    // Add button event listener
    document.getElementById('btnCancelLock').addEventListener('click', cancelLock);
</script>
@endsection 