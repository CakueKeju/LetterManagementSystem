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

.action-buttons .btn-outline-info {
    border-color: #0dcaf0;
    color: #0dcaf0;
}

.action-buttons .btn-outline-info:hover {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: black !important;
}
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tags me-2"></i>
                        Manajemen Jenis Surat
                    </h4>
                    <div>
                        <a href="{{ route('admin.jenis-surat.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Tambah Jenis Surat
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($jenisSurat->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Kode Jenis</th>
                                        <th>Nama Jenis</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Jumlah Surat</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($jenisSurat as $jenis)
                                    <tr>
                                        <td>
                                            <strong>{{ $jenis->kode_jenis }}</strong>
                                        </td>
                                        <td>{{ $jenis->nama_jenis }}</td>
                                        <td>{{ Str::limit($jenis->deskripsi, 50) ?: 'Tidak ada deskripsi' }}</td>
                                        <td>
                                            @if($jenis->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $jenis->surat_count }}</span>
                                        </td>
                                        <td>
                                            @if($jenis->created_at)
                                                {{ $jenis->created_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 action-buttons">
                                                <a href="{{ route('admin.jenis-surat.edit', $jenis->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit Jenis Surat">
                                                    &#9998;
                                                </a>
                                                @if($jenis->surat_count == 0)
                                                    <form method="POST" action="{{ route('admin.jenis-surat.destroy', $jenis->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jenis surat ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Jenis Surat">
                                                            &#128465;
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Tidak dapat dihapus karena masih digunakan">
                                                        &#128465;
                                                    </button>
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
                            {{ $jenisSurat->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada jenis surat yang dibuat</h5>
                            <p class="text-muted">Mulai dengan menambahkan jenis surat pertama</p>
                            <a href="{{ route('admin.jenis-surat.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Tambah Jenis Surat Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 