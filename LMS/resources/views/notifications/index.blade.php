@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bell me-2"></i>Notifikasi</h2>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="refreshNotifications">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
            <button type="button" class="btn btn-outline-success" id="markAllRead">
                <i class="fas fa-check-double me-1"></i>Tandai Semua Dibaca
            </button>
        </div>
    </div>
    
    <!-- Notification Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-primary">
                        <i class="fas fa-bell fa-2x"></i>
                    </div>
                    <h5 class="mt-2">Total Notifikasi</h5>
                    <h3 class="text-primary" id="totalCount">{{ $notifications->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-warning">
                        <i class="fas fa-bell-slash fa-2x"></i>
                    </div>
                    <h5 class="mt-2">Belum Dibaca</h5>
                    <h3 class="text-warning" id="unreadCount">{{ $notifications->where('is_read', false)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h5 class="mt-2">Sudah Dibaca</h5>
                    <h3 class="text-success" id="readCount">{{ $notifications->where('is_read', true)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-info">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h5 class="mt-2">Hari Ini</h5>
                    <h3 class="text-info" id="todayCount">{{ $notifications->filter(function($n) { return $n->created_at->isToday(); })->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="filterType" class="form-label">Filter berdasarkan Jenis</label>
                    <select class="form-select" id="filterType">
                        <option value="">Semua Jenis</option>
                        <option value="new_letter">Surat Baru</option>
                        <option value="letter_access_granted">Akses Surat Diberikan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Filter berdasarkan Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="unread">Belum Dibaca</option>
                        <option value="read">Sudah Dibaca</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filterDate" class="form-label">Filter berdasarkan Tanggal</label>
                    <select class="form-select" id="filterDate">
                        <option value="">Semua Waktu</option>
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" id="applyFilters">
                        <i class="fas fa-filter me-1"></i>Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Notifikasi</h5>
        </div>
        <div class="card-body p-0">
            <div id="notificationsContainer">
                @include('notifications.partials.list', ['notifications' => $notifications])
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Notifikasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" class="btn btn-primary" id="viewLetterBtn">Lihat Surat</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let isLoading = false;

    // CSRF token for API requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Refresh notifications
    document.getElementById('refreshNotifications').addEventListener('click', function() {
        loadNotifications(1, true);
    });

    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        if (confirm('Tandai semua notifikasi sebagai sudah dibaca?')) {
            markAllAsRead();
        }
    });

    // Apply filters
    document.getElementById('applyFilters').addEventListener('click', function() {
        loadNotifications(1, true);
    });

    // Load notifications function
    function loadNotifications(page = 1, reset = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        const filters = {
            type: document.getElementById('filterType').value,
            status: document.getElementById('filterStatus').value,
            date: document.getElementById('filterDate').value,
            page: page
        };

        const queryString = new URLSearchParams(filters).toString();
        
        fetch(`/api/notifications?${queryString}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (reset || page === 1) {
                document.getElementById('notificationsContainer').innerHTML = '';
            }
            
            // Append new notifications
            appendNotifications(data.notifications);
            
            // Update pagination info
            currentPage = data.pagination.current_page;
            
            // Update stats
            updateStats();
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            isLoading = false;
        });
    }

    // Append notifications to the list
    function appendNotifications(notifications) {
        const container = document.getElementById('notificationsContainer');
        
        notifications.forEach(notification => {
            const notificationElement = createNotificationElement(notification);
            container.appendChild(notificationElement);
        });
    }

    // Create notification element
    function createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item border-bottom p-3 ${!notification.is_read ? 'bg-light' : ''}`;
        div.setAttribute('data-id', notification.id);
        
        const typeIcon = notification.type === 'new_letter' ? 'fa-envelope' : 'fa-key';
        const typeColor = notification.type === 'new_letter' ? 'text-primary' : 'text-success';
        
        div.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <i class="fas ${typeIcon} ${typeColor} fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="mb-0 ${!notification.is_read ? 'fw-bold' : ''}">${notification.title}</h6>
                        <small class="text-muted">${formatDate(notification.created_at)}</small>
                    </div>
                    <p class="mb-1 text-muted">${notification.message}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-1">${notification.data?.division_name || 'N/A'}</span>
                            <span class="badge bg-info">${notification.data?.jenis_surat_name || 'N/A'}</span>
                        </div>
                        <div>
                            ${!notification.is_read ? '<span class="badge bg-warning">Belum Dibaca</span>' : '<span class="badge bg-success">Dibaca</span>'}
                        </div>
                    </div>
                </div>
                <div class="ms-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewNotification(${notification.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        `;
        
        return div;
    }

    // Format date function
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) {
            return 'Hari ini';
        } else if (diffDays === 2) {
            return 'Kemarin';
        } else if (diffDays <= 7) {
            return `${diffDays - 1} hari lalu`;
        } else {
            return date.toLocaleDateString('id-ID');
        }
    }

    // View notification function (global)
    window.viewNotification = function(id) {
        // Mark as read first
        markAsRead(id);
        
        // Then show details
        // This would typically load the notification details and show in modal
        // For now, we'll redirect to the letter
        const notification = findNotificationById(id);
        if (notification && notification.surat_id) {
            window.location.href = `/surat/file/${notification.surat_id}`;
        }
    };

    // Mark notification as read
    function markAsRead(id) {
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
                    const badge = notificationElement.querySelector('.badge.bg-warning');
                    if (badge) {
                        badge.className = 'badge bg-success';
                        badge.textContent = 'Dibaca';
                    }
                }
                updateStats();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    // Mark all as read
    function markAllAsRead() {
        fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(1, true);
                alert(`${data.count} notifikasi telah ditandai sebagai dibaca.`);
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }

    // Update statistics
    function updateStats() {
        fetch('/api/notifications/unread-count', {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('unreadCount').textContent = data.unread_count;
        })
        .catch(error => console.error('Error updating stats:', error));
    }

    // Helper function to find notification by id
    function findNotificationById(id) {
        // This would need to be implemented based on how you store the data
        return null;
    }

    // Load initial notifications
    loadNotifications();
});
</script>
@endsection
