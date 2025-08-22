@extends('layouts.app')

@section('content')
<style>
.action-buttons .btn {
    transition: all 0.2s ease;
    border-radius: 6px;
    font-size: 19px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-width: 1.5px;
    background-color: transparent;
    font-family: system-ui, -apple-system, sans-serif;
    line-height: 1;
    font-weight: bold;
    transform: translateY(-1px);
}

.action-buttons .btn:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.action-buttons .btn-outline-warning {
    border-color: #ffc107;
    color: #ffc107;
}

.action-buttons .btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: black !important;
}

.action-buttons .btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
}

.action-buttons .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white !important;
}
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Manajemen Users
                    </h4>
                    <div>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Tambah User
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Cari user..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="divisi_id" class="form-select">
                                    <option value="">Semua Divisi</option>
                                    @foreach($divisions as $division)
                                        <option value="{{ $division->id }}" {{ request('divisi_id') == $division->id ? 'selected' : '' }}>
                                            {{ $division->nama_divisi }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="is_active" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Cari</button>
                            </div>
                        </div>
                    </form>

                    <!-- Users Table -->
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Divisi</th>
                                        <th>Status</th>
                                        <th>Role</th>
                                        <th>Tanggal Registrasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->full_name }}</strong>
                                        </td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->division->nama_divisi ?? 'N/A' }}</td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->is_admin)
                                                <span class="badge bg-danger">Admin</span>
                                            @else
                                                <span class="badge bg-secondary">User</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->created_at)
                                                {{ $user->created_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 action-buttons">
                                                <a href="{{ route('admin.users.edit', $user->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($user->id !== Auth::id())
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada user ditemukan</h5>
                            <p class="text-muted">Coba ubah filter pencarian Anda</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 