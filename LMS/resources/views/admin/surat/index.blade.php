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
                        <i class="fas fa-file-alt me-2"></i>
                        Manajemen Surat
                    </h4>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.surat.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control" placeholder="Cari surat..." value="{{ request('search') }}">
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
                                <select name="jenis_surat_id" class="form-select">
                                    <option value="">Semua Jenis</option>
                                    @foreach($jenisSurat as $jenis)
                                        <option value="{{ $jenis->id }}" {{ request('jenis_surat_id') == $jenis->id ? 'selected' : '' }}>
                                            {{ $jenis->nama_jenis }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="tanggal_surat" class="form-control" placeholder="Tanggal Surat" value="{{ request('tanggal_surat') }}">
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <select name="is_private" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('is_private') === '1' ? 'selected' : '' }}>Private</option>
                                    <option value="0" {{ request('is_private') === '0' ? 'selected' : '' }}>Public</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="sort" class="form-select">
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Surat Table -->
                    @if($surat->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nomor Surat</th>
                                        <th>Deskripsi</th>
                                        <th>Divisi</th>
                                        <th>Jenis Surat</th>
                                        <th>Uploader</th>
                                        <th>Status</th>
                                        <th>Tanggal Surat</th>
                                        <th>Tanggal Upload</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($surat as $s)
                                    <tr>
                                        <td>
                                            <strong>{{ $s->nomor_surat_display }}</strong>
                                        </td>
                                        <td>{{ Str::limit($s->perihal, 50) }}</td>
                                        <td>{{ $s->division->nama_divisi ?? 'N/A' }}</td>
                                        <td>{{ $s->jenisSurat->nama_jenis ?? 'N/A' }}</td>
                                        <td>{{ $s->uploader->full_name ?? 'N/A' }}</td>
                                        <td>
                                            @if($s->is_private)
                                                <span class="badge bg-warning">Private</span>
                                            @else
                                                <span class="badge bg-success">Public</span>
                                            @endif
                                        </td>
                                        <td>{{ $s->tanggal_surat ? $s->tanggal_surat->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            @if($s->created_at)
                                                {{ $s->created_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 action-buttons">
                                                <a href="{{ route('surat.file', $s->id) }}" target="_blank" 
                                                   class="btn btn-sm btn-outline-primary" title="Lihat Surat">
                                                    &#128065;
                                                </a>
                                                <a href="{{ route('surat.download', $s->id) }}" 
                                                   class="btn btn-sm btn-outline-success" title="Download Surat">
                                                    &#8595;
                                                </a>
                                                <a href="{{ route('admin.surat.edit', $s->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit Surat">
                                                    &#9998;
                                                </a>
                                                <form method="POST" action="{{ route('admin.surat.destroy', $s->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus surat ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Surat">
                                                        &#128465;
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $surat->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada surat ditemukan</h5>
                            <p class="text-muted">Coba ubah filter pencarian Anda</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 