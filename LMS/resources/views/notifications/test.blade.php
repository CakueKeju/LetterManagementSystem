@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Test Notification System</h4>
                    <div>
                        <span class="badge bg-primary" id="unreadCount">Loading...</span>
                    </div>
                </div>
                <div class="card-body">
                    <p>This is a test page to verify the notification system is working.</p>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> How to test:</h5>
                        <ol>
                            <li>Upload a new letter (automatic or manual mode)</li>
                            <li>Make sure it's either public (for division users) or private with selected users</li>
                            <li>Check the notification bell in the header - it should show a red badge with count</li>
                            <li>Click the bell to see recent notifications</li>
                            <li>Visit the <a href="{{ route('notifications.index') }}">full notifications page</a> to see all notifications</li>
                        </ol>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <a href="{{ route('surat.mode.selection') }}" class="btn btn-primary me-2">
                                        <i class="fas fa-upload"></i> Upload Letter
                                    </a>
                                    <a href="{{ route('notifications.index') }}" class="btn btn-success">
                                        <i class="fas fa-bell"></i> View All Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Current User Info</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> {{ Auth::user()->full_name }}</p>
                                    <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
                                    <p><strong>Division:</strong> {{ Auth::user()->division->nama_divisi }}</p>
                                    <p><strong>Admin:</strong> {{ Auth::user()->is_admin ? 'Yes' : 'No' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load notification count
    fetch('/api/notifications/unread-count', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('unreadCount').textContent = data.unread_count + ' unread notifications';
    })
    .catch(error => {
        console.error('Error loading notification count:', error);
        document.getElementById('unreadCount').textContent = 'Error loading count';
    });
});
</script>
@endsection
