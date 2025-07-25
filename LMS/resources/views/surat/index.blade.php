@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Daftar Surat</h2>
    <form method="GET" action="{{ route('surat.index') }}" class="row g-3 mb-4">
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
                <th>Private</th>
                <th>File</th>
                <th>Tanggal Upload</th>
            </tr>
        </thead>
        <tbody>
            @forelse($letters as $i => $surat)
                <tr>
                    <td>{{ $letters->firstItem() + $i }}</td>
                    <td>{{ $surat->nomor_surat }}</td>
                    <td>{{ $surat->perihal }}</td>
                    <td>{{ $surat->division->nama_divisi }}</td>
                    <td>{{ $surat->jenisSurat->nama_jenis }}</td>
                    <td>{{ $surat->tanggal_surat->format('d-m-Y') }}</td>
                    <td>{{ $surat->uploader->full_name ?? '-' }}</td>
                    <td>{{ $surat->is_private ? 'Ya' : 'Tidak' }}</td>
                    <td><a href="{{ asset('storage/' . $surat->file_path) }}" target="_blank">Lihat</a></td>
                    <td>{{ $surat->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">Tidak ada surat ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div>
        {{ $letters->withQueryString()->links() }}
    </div>
</div>
@endsection 