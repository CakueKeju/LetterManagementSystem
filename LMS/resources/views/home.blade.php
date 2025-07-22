@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Surat</h2>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="alert alert-info mb-0" style="max-width: 350px;">
                <strong>Nomor Urut Tersedia:</strong> <span class="badge bg-primary">{{ $available_nomor_urut }}</span>
            </div>
        </div>
        <a href="{{ route('surat.upload') }}" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Upload Surat
        </a>
    </div>
    <form method="GET" action="{{ route('home') }}" class="row g-3 mb-4">
        <div class="col-md-2">
            <select name="divisi_id" class="form-select">
                <option value="">Semua Divisi</option>
                @foreach($divisions as $divisi)
                    <option value="{{ $divisi->id }}" {{ ($filters['divisi_id'] ?? '') == $divisi->id ? 'selected' : '' }}>
                        {{ $divisi->nama_divisi }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="jenis_surat_id" class="form-select">
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
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Surat</th>
                    <th>Deskripsi</th>
                    <th>Divisi</th>
                    <th>Jenis</th>
                    <th>Tanggal Surat</th>
                    <th>Uploader</th>
                    <th>Akses</th>
                    <th>File</th>
                    <th>Tanggal Upload</th>
                </tr>
            </thead>
            <tbody>
                @forelse($letters as $i => $surat)
                    <tr>
                        <td>{{ $letters->firstItem() + $i }}</td>
                        <td>{{ $surat->kode_surat }}</td>
                        <td>{{ $surat->deskripsi }}</td>
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
                        <td><a href="{{ asset('storage/' . $surat->file_path) }}" target="_blank">Lihat</a></td>
                        <td>{{ $surat->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center">Tidak ada surat ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center">
        {{ $letters->withQueryString()->links() }}
    </div>
</div>
<!-- Floating Upload Button (visible on all screens) -->
{{-- <a href="{{ route('surat.upload') }}" class="btn btn-success btn-lg rounded-circle shadow position-fixed" style="bottom: 30px; right: 30px; z-index: 1050;" title="Upload Surat">
    <i class="fas fa-plus"></i>
</a> --}}
@endsection