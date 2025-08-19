<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-papm6Q6lJzi10BGSAdoo6gWQBaIj++ImQxGc1dQc5sKXc5teLoI0lp4rWuIwoMvVJE9idh+NangNh4pW7x1Ymg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Alternative Font Awesome in case CDN fails -->
    <link rel="stylesheet" href="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous">
    
    <!-- Backup Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous" />

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ Auth::check() ? (Auth::user()->isAdmin() ? route('admin.dashboard') : route('home')) : url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                {{-- <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li> --}}
                            @endif
                        @else
                            <!-- Notification Dropdown -->
                            <li class="nav-item dropdown">
                                <a id="notificationDropdown" class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="pointer-events: auto !important; position: relative; z-index: 1050;">
                                    <i class="fas fa-bell me-2"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none; font-size: 0.6rem;">
                                        0
                                    </span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto; z-index: 1051;">
                                    <div class="dropdown-header">
                                        <strong>Notifikasi</strong>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div id="notificationDropdownContent">
                                        <div class="text-center p-3">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </div>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-item-text text-center">
                                        <div class="d-flex justify-content-between">
                                            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                                                Lihat Semua
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="markAllNotificationsRead()">
                                                Tandai Dibaca
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="pointer-events: auto !important; position: relative; z-index: 1050;">
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ Auth::user()->full_name ?? Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown" style="z-index: 1051;">
                                    <div class="dropdown-header">
                                        <strong>{{ Auth::user()->full_name ?? Auth::user()->name }}</strong><br>
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>{{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Force Bootstrap dropdown initialization -->
    <script>
        // Wait for DOM and all scripts to load
        window.addEventListener('load', function() {
            console.log('Initializing dropdowns...');
            
            // Force reinitialize dropdowns
            const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdowns.forEach(function(dropdown) {
                console.log('Found dropdown:', dropdown);
                
                // Remove existing instances
                const existingInstance = bootstrap.Dropdown.getInstance(dropdown);
                if (existingInstance) {
                    existingInstance.dispose();
                }
                
                // Create new instance
                new bootstrap.Dropdown(dropdown);
                
                // Add click event listener as fallback
                dropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Dropdown clicked');
                    const instance = bootstrap.Dropdown.getInstance(this);
                    if (instance) {
                        instance.toggle();
                    }
                });
            });
        });
    </script>
    
    @auth
    <!-- Notification JavaScript -->
    <script>
        let notificationPollingInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial notification count and recent notifications
            loadNotificationCount();
            
            // Set up polling for new notifications every 30 seconds
            notificationPollingInterval = setInterval(loadNotificationCount, 30000);
            
            // Load notifications when dropdown is opened
            document.getElementById('notificationDropdown').addEventListener('click', function() {
                loadRecentNotifications();
            });
        });
        
        function loadNotificationCount() {
            fetch('/api/notifications/unread-count', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error loading notification count:', error));
        }
        
        function loadRecentNotifications() {
            const content = document.getElementById('notificationDropdownContent');
            content.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch('/api/notifications/recent?limit=5', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.notifications.length === 0) {
                    content.innerHTML = '<div class="text-center p-3 text-muted"><i class="fas fa-bell-slash me-2"></i>Tidak ada notifikasi</div>';
                } else {
                    let html = '';
                    data.notifications.forEach(notification => {
                        const isUnread = !notification.is_read;
                        const typeIcon = notification.type === 'new_letter' ? 'fa-envelope' : 'fa-key';
                        const typeColor = notification.type === 'new_letter' ? 'text-primary' : 'text-success';
                        
                        html += `
                            <a class="dropdown-item ${isUnread ? 'bg-light' : ''}" href="#" onclick="viewNotificationFromDropdown(${notification.id}, ${notification.surat_id})" style="white-space: normal; text-decoration: none;">
                                <div class="d-flex align-items-start py-2">
                                    <div class="me-3">
                                        <i class="fas ${typeIcon} ${typeColor}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 ${isUnread ? 'fw-bold' : ''}" style="font-size: 0.85rem;">${notification.title}</h6>
                                        <p class="mb-1 text-muted" style="font-size: 0.75rem; line-height: 1.3;">${notification.message.substring(0, 80)}${notification.message.length > 80 ? '...' : ''}</p>
                                        <small class="text-muted" style="font-size: 0.7rem;">${formatNotificationDate(notification.created_at)}</small>
                                    </div>
                                    ${isUnread ? '<div class="ms-2"><span class="badge bg-primary rounded-pill" style="font-size: 0.6rem;">Baru</span></div>' : ''}
                                </div>
                            </a>
                        `;
                    });
                    content.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error loading recent notifications:', error);
                content.innerHTML = '<div class="text-center p-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading notifications</div>';
            });
        }
        
        function viewNotificationFromDropdown(notificationId, suratId) {
            // Mark notification as read
            fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update notification counts
                    updateUnreadCount();
                    // Redirect to surat detail
                    window.location.href = `/surats/${suratId}`;
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                // Still redirect even if marking as read fails
                window.location.href = `/surats/${suratId}`;
            });
        }
        
        function markAllNotificationsRead() {
            fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotificationCount();
                    loadRecentNotifications();
                    // Show success message
                    const content = document.getElementById('notificationDropdownContent');
                    content.innerHTML = '<div class="text-center p-3 text-success"><i class="fas fa-check"></i> Semua notifikasi telah ditandai dibaca</div>';
                    setTimeout(() => loadRecentNotifications(), 2000);
                }
            })
            .catch(error => console.error('Error marking all notifications as read:', error));
        }
        
        function formatNotificationDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffMinutes = Math.ceil(diffTime / (1000 * 60));
            const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffMinutes < 60) {
                return `${diffMinutes} menit lalu`;
            } else if (diffHours < 24) {
                return `${diffHours} jam lalu`;
            } else if (diffDays === 1) {
                return 'Kemarin';
            } else if (diffDays <= 7) {
                return `${diffDays} hari lalu`;
            } else {
                return date.toLocaleDateString('id-ID');
            }
        }
        
        // Clean up polling when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (notificationPollingInterval) {
                clearInterval(notificationPollingInterval);
            }
        });
    </script>
    @endauth
</body>
</html>
