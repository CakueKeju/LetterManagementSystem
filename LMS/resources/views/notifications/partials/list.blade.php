@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-item border-bottom p-3 {{ !$notification->is_read ? 'bg-light' : '' }}" data-id="{{ $notification->id }}">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    @if($notification->type === 'new_letter')
                        <i class="fas fa-envelope text-primary fa-lg"></i>
                    @elseif($notification->type === 'letter_access_granted')
                        <i class="fas fa-key text-success fa-lg"></i>
                    @else
                        <i class="fas fa-bell text-info fa-lg"></i>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 {{ !$notification->is_read ? 'fw-bold' : '' }}">{{ $notification->title }}</h6>
                        <small class="text-muted">
                            @if($notification->created_at->isToday())
                                Hari ini, {{ $notification->created_at->format('H:i') }}
                            @elseif($notification->created_at->isYesterday())
                                Kemarin, {{ $notification->created_at->format('H:i') }}
                            @elseif($notification->created_at->diffInDays() <= 7)
                                {{ $notification->created_at->diffInDays() }} hari lalu
                            @else
                                {{ $notification->created_at->format('d M Y, H:i') }}
                            @endif
                        </small>
                    </div>
                    <p class="mb-2 text-muted">{{ $notification->message }}</p>
                    
                    @if($notification->data)
                        <div class="mb-2">
                            @if(isset($notification->data['division_name']))
                                <span class="badge bg-secondary me-1">{{ $notification->data['division_name'] }}</span>
                            @endif
                            @if(isset($notification->data['jenis_surat_name']))
                                <span class="badge bg-info me-1">{{ $notification->data['jenis_surat_name'] }}</span>
                            @endif
                            @if(isset($notification->data['letter_number']))
                                <span class="badge bg-dark">{{ $notification->data['letter_number'] }}</span>
                            @endif
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if(isset($notification->data['uploader_name']))
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $notification->data['uploader_name'] }}
                                </small>
                            @endif
                        </div>
                        <div>
                            @if(!$notification->is_read)
                                <span class="badge bg-warning">Belum Dibaca</span>
                            @else
                                <span class="badge bg-success">Dibaca</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="ms-2 d-flex flex-column gap-1">
                    @if(!$notification->is_read)
                        <button class="btn btn-sm btn-outline-success" onclick="markAsRead({{ $notification->id }})" title="Tandai Dibaca">
                            <i class="fas fa-check"></i>
                        </button>
                    @endif
                    
                    @if($notification->surat)
                        <a href="{{ route('surat.file', $notification->surat->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Surat">
                            <i class="fas fa-eye"></i>
                        </a>
                    @endif
                    
                    <button class="btn btn-sm btn-outline-info" onclick="showNotificationDetail({{ $notification->id }})" title="Detail">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
    
    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="p-3 border-top">
            <div class="d-flex justify-content-center">
                {{ $notifications->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
@else
    <div class="text-center p-5">
        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Tidak ada notifikasi</h5>
        <p class="text-muted">Notifikasi akan muncul di sini ketika ada surat baru atau akses surat diberikan.</p>
    </div>
@endif

<script>
// Mark single notification as read
function markAsRead(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/api/notifications/${id}/mark-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the notification item visually
            const notificationElement = document.querySelector(`[data-id="${id}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('bg-light');
                
                // Update badge
                const badge = notificationElement.querySelector('.badge.bg-warning');
                if (badge) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Dibaca';
                }
                
                // Remove mark as read button
                const markButton = notificationElement.querySelector('button[onclick="markAsRead(' + id + ')"]');
                if (markButton) {
                    markButton.remove();
                }
                
                // Remove bold from title
                const title = notificationElement.querySelector('h6.fw-bold');
                if (title) {
                    title.classList.remove('fw-bold');
                }
            }
            
            // Update stats if the updateStats function exists
            if (typeof updateStats === 'function') {
                updateStats();
            }
            
            // Update unread count in header if exists
            updateHeaderNotificationCount();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        alert('Gagal menandai notifikasi sebagai dibaca');
    });
}

// Show notification detail modal
function showNotificationDetail(id) {
    // This would show more detailed information about the notification
    // For now, we'll just show an alert
    alert('Detail notifikasi akan ditampilkan di sini');
}

// Update header notification count
function updateHeaderNotificationCount() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/api/notifications/unread-count', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Update notification badge in header if it exists
        const headerBadge = document.querySelector('.notification-badge');
        if (headerBadge) {
            if (data.unread_count > 0) {
                headerBadge.textContent = data.unread_count;
                headerBadge.style.display = 'inline';
            } else {
                headerBadge.style.display = 'none';
            }
        }
        
        // Update stats on notifications page if elements exist
        const unreadCountElement = document.getElementById('unreadCount');
        if (unreadCountElement) {
            unreadCountElement.textContent = data.unread_count;
        }
    })
    .catch(error => console.error('Error updating notification count:', error));
}
</script>
