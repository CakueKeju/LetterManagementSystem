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

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Daftar Surat</h2>
        <a href="{{ route('surat.mode.selection') }}" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Upload Surat
        </a>
    </div>
    <form method="GET" action="{{ route('home') }}" class="row g-3 mb-4" id="filterForm">
        <div class="col-md-2">
            <input type="text" name="perihal" class="form-control" placeholder="Cari Perihal" value="{{ $filters['perihal'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <select name="jenis_surat_id" class="form-select" id="jenisSuratSelect">
                <option value="">Semua Jenis</option>
                @foreach($jenisSurat as $jenis)
                    <option value="{{ $jenis->id }}" {{ ($filters['jenis_surat_id'] ?? '') == $jenis->id ? 'selected' : '' }}>
                        {{ $jenis->nama_jenis }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="tanggal_surat" class="form-control" placeholder="Tanggal Surat" value="{{ $filters['tanggal_surat'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <select name="is_private" class="form-select">
                <option value="">Semua</option>
                <option value="0" {{ (isset($filters['is_private']) && $filters['is_private'] === '0') ? 'selected' : '' }}>Publik</option>
                <option value="1" {{ (isset($filters['is_private']) && $filters['is_private'] === '1') ? 'selected' : '' }}>Private</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="sort" class="form-select">
                <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Terbaru</option>
                <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Terlama</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
    <script>
    document.getElementById('jenisSuratSelect').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    </script>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor Surat</th>
                    <th>Perihal</th>
                    <th>Divisi</th>
                    <th>Jenis</th>
                    <th>Tanggal Surat</th>
                    <th>Uploader</th>
                    <th>Akses</th>
                    <th>Aksi</th>
                    <th>Tanggal Upload</th>
                </tr>
            </thead>
            <tbody>
                @forelse($letters as $i => $surat)
                    <tr>
                        <td>{{ $letters->firstItem() + $i }}</td>
                        <td>{{ $surat->nomor_surat_display }}</td>
                        <td>{{ $surat->perihal }}</td>
                        <td>{{ $surat->division->nama_divisi }}</td>
                        <td>{{ $surat->jenisSurat->nama_jenis }}</td>
                        <td>{{ $surat->tanggal_surat->format('d-m-Y') }}</td>
                        <td>{{ $surat->uploader->full_name ?? '-' }}</td>
                        <td>
                            @if($surat->is_private)
                                <span class="badge bg-warning">Private</span>
                            @else
                                <span class="badge bg-success">Publik</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2 action-buttons">
                                <a href="{{ route('surat.file', $surat->id) }}" target="_blank" 
                                   class="btn btn-sm btn-outline-primary action-btn" title="Lihat Surat">
                                    &#128065;
                                </a>
                                <a href="{{ route('surat.download', $surat->id) }}" 
                                   class="btn btn-sm btn-outline-success action-btn" title="Download Surat">
                                    &#8595;
                                </a>
                            </div>
                        </td>
                        <td>{{ $surat->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">Tidak ada surat ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center">
        {{ $letters->withQueryString()->links() }}
    </div>
</div>
@endsection