@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Admin Dashboard
                    </h4>
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
                                    @if($recent_surat->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Kode Surat</th>
                                                        <th>Deskripsi</th>
                                                        <th>Uploader</th>
                                                        <th>Divisi</th>
                                                        <th>Status</th>
                                                        <th>Tanggal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recent_surat as $surat)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('admin.surat.edit', $surat->id) }}" class="text-decoration-none">
                                                                {{ $surat->kode_surat }}
                                                            </a>
                                                        </td>
                                                        <td>{{ Str::limit($surat->deskripsi, 30) }}</td>
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
                                    @if($recent_users->count() > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($recent_users as $user)
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