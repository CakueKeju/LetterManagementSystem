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
                        <i class="fas fa-building me-2"></i>
                        Manajemen Divisi
                    </h4>
                    <div>
                        <a href="{{ route('admin.divisions.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Tambah Divisi
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($divisions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Kode Divisi</th>
                                        <th>Nama Divisi</th>
                                        <th>Deskripsi</th>
                                        <th>Jumlah Users</th>
                                        <th>Jumlah Surat</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($divisions as $division)
                                    <tr>
                                        <td>
                                            <strong>{{ $division->kode_divisi }}</strong>
                                        </td>
                                        <td>{{ $division->nama_divisi }}</td>
                                        <td>{{ Str::limit($division->deskripsi, 50) ?: 'Tidak ada deskripsi' }}</td>
                                        <td class="align-middle">
                                            <span class="badge bg-info">{{ $division->users_count ?? 0 }}</span>
                                            @if($division->users_count > 0)
                                                <button class="btn btn-link btn-sm p-0 ms-2 align-baseline" type="button" data-bs-toggle="collapse" data-bs-target="#users-{{ $division->id }}" aria-expanded="false" aria-controls="users-{{ $division->id }}" title="Lihat Member" style="text-decoration: none;">
                                                    <span style="font-size:1rem;">&#9660;</span>
                                                </button>
                                            @else
                                                <button class="btn btn-link btn-sm p-0 ms-2 align-baseline text-secondary" type="button" disabled title="Tidak ada member" style="text-decoration: none;">
                                                    <span style="font-size:1rem;">&#9660;</span>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $division->surat_count ?? 0 }}</span>
                                        </td>
                                        <td>
                                            @if($division->created_at)
                                                {{ $division->created_at->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 action-buttons">
                                                <a href="{{ route('admin.divisions.edit', $division->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit Divisi">
                                                    &#9998;
                                                </a>
                                                @if($division->users_count == 0 && ($division->surat_count ?? 0) == 0)
                                                    <form method="POST" action="{{ route('admin.divisions.destroy', $division->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus divisi ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Divisi">
                                                            &#128465;
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Tidak dapat dihapus karena masih memiliki users atau surat">
                                                        &#128465;
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="users-{{ $division->id }}">
                                        <td colspan="7">
                                            <div class="card card-body mb-2">
                                                <strong>Daftar User di Divisi {{ $division->nama_divisi }}:</strong>
                                                @if($division->users && count($division->users) > 0)
                                                    <ul class="mb-0">
                                                        @foreach($division->users as $user)
                                                            <li>{{ $user->full_name }} <small class="text-muted">({{ $user->username }} - {{ $user->email }})</small></li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-muted">Tidak ada user di divisi ini.</span>
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
                            {{ $divisions->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada divisi yang dibuat</h5>
                            <p class="text-muted">Mulai dengan menambahkan divisi pertama</p>
                            <a href="{{ route('admin.divisions.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Tambah Divisi Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 