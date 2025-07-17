@extends('layouts.app')

@section('content')
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
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.jenis-surat.edit', $jenis->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </a>
                                                @if($jenis->surat_count == 0)
                                                    <form method="POST" action="{{ route('admin.jenis-surat.destroy', $jenis->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus jenis surat ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                            <i class="fa-solid fa-trash"></i> Hapus
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Tidak dapat dihapus karena masih digunakan">
                                                        <i class="fa-solid fa-trash"></i> Hapus
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