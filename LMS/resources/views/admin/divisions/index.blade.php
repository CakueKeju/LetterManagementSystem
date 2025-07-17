@extends('layouts.app')

@section('content')
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
                                        <td>
                                            <span class="badge bg-info">{{ $division->users_count }}</span>
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
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.divisions.edit', $division->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </a>
                                                @if($division->users_count == 0 && ($division->surat_count ?? 0) == 0)
                                                    <form method="POST" action="{{ route('admin.divisions.destroy', $division->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus divisi ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                            <i class="fa-solid fa-trash"></i> Hapus
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Tidak dapat dihapus karena masih memiliki users atau surat">
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