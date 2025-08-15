@extends('layouts.app')

@section('content')
<style>
.action-buttons .btn {
    transition: all 0.2s ease;
    border-radius: 6px;
    font-size: 16px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-width: 1.5px;
    background-color: transparent;
    font-family: system-ui, -apple-system, sans-serif;
    line-height: 1;
    font-weight: bold;
}

.action-buttons .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.action-buttons .btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
}

.action-buttons .btn-outline-primary:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white !important;
}

.action-buttons .btn-outline-success {
    border-color: #198754;
    color: #198754;
}

.action-buttons .btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    color: white !important;
}
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Admin Dashboard
                    </h4>
                    <a href="{{ route('admin.surat.mode.selection') }}" class="btn btn-success ms-2">
                        <i class="fas fa-plus"></i> Upload Surat
                    </a>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['total_surat'] }}</h3>
                                    <p class="mb-0">Total Surat</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['total_users'] }}</h3>
                                    <p class="mb-0">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['total_divisions'] }}</h3>
                                    <p class="mb-0">Divisi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['total_jenis_surat'] }}</h3>
                                    <p class="mb-0">Jenis Surat</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['private_surat'] }}</h3>
                                    <p class="mb-0">Private Surat</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-dark text-white">
                                <div class="card-body text-center">
                                    <h3>{{ $stats['public_surat'] }}</h3>
                                    <p class="mb-0">Public Surat</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.surat.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-file-alt me-1"></i>Kelola Surat
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-success">
                                    <i class="fas fa-users me-1"></i>Kelola Users
                                </a>
                                <a href="{{ route('admin.divisions.index') }}" class="btn btn-outline-info">
                                    <i class="fas fa-building me-1"></i>Kelola Divisi
                                </a>
                                <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline-warning">
                                    <i class="fas fa-tags me-1"></i>Kelola Jenis Surat
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>Surat Terbaru
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($recentSurat->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Nomor Surat</th>
                                                        <th>Deskripsi</th>
                                                        <th>Uploader</th>
                                                        <th>Divisi</th>
                                                        <th>Status</th>
                                                        <th>Tanggal</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentSurat as $surat)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $surat->nomor_surat_display }}</strong>
                                                        </td>
                                                        <td>{{ Str::limit($surat->perihal, 30) }}</td>
                                                        <td>{{ $surat->uploader->full_name ?? 'N/A' }}</td>
                                                        <td>{{ $surat->division->nama_divisi ?? 'N/A' }}</td>
                                                        <td>
                                                            @if($surat->is_private)
                                                                <span class="badge bg-warning">Private</span>
                                                            @else
                                                                <span class="badge bg-success">Public</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $surat->created_at->format('d/m/Y H:i') }}</td>
                                                        <td>
                                                            <div class="d-flex gap-1 action-buttons">
                                                                <a href="{{ route('surat.file', $surat->id) }}" target="_blank" 
                                                                   class="btn btn-sm btn-outline-primary" title="Lihat Surat">
                                                                    &#128065;
                                                                </a>
                                                                <a href="{{ route('surat.download', $surat->id) }}" 
                                                                   class="btn btn-sm btn-outline-success" title="Download Surat">
                                                                    &#8595;
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-2">
                                            <a href="{{ route('admin.surat.index') }}" class="btn btn-sm btn-primary">
                                                Lihat Semua Surat
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted text-center">Belum ada surat yang diupload.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-plus me-2"></i>Users Terbaru
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($recentUsers->count() > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($recentUsers as $user)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $user->full_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $user->username }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">{{ $user->division->nama_divisi ?? 'N/A' }}</small>
                                                    @if($user->is_admin)
                                                        <br><span class="badge bg-danger">Admin</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="text-center mt-2">
                                            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-success">
                                                Lihat Semua Users
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted text-center">Belum ada user yang terdaftar.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 