@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 flex-grow-1">
                        <i class="fas fa-edit me-2"></i>
                        Edit Divisi: {{ $division->nama_divisi }}
                    </h4>
                    <a href="{{ route('admin.divisions.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.divisions.update', $division->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kode_divisi" class="form-label">Kode Divisi *</label>
                                    <input type="text" class="form-control @error('kode_divisi') is-invalid @enderror" 
                                           id="kode_divisi" name="kode_divisi" value="{{ old('kode_divisi', $division->kode_divisi) }}" 
                                           maxlength="10" required>
                                    <small class="form-text text-muted">Maksimal 10 karakter</small>
                                    @error('kode_divisi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_divisi" class="form-label">Nama Divisi *</label>
                                    <input type="text" class="form-control @error('nama_divisi') is-invalid @enderror" 
                                           id="nama_divisi" name="nama_divisi" value="{{ old('nama_divisi', $division->nama_divisi) }}" 
                                           maxlength="100" required>
                                    @error('nama_divisi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                      id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $division->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Manage Division Members -->
                        <div class="mb-3">
                            <label class="form-label">Anggota Divisi</label>
                            <div class="d-flex flex-column align-items-center">
                                <div class="w-100 mb-2">
                                    <div class="border rounded p-2 mb-2">
                                        <strong>Anggota Divisi</strong>
                                        <ul class="list-group" id="anggota-list">
                                            @foreach($allUsers as $user)
                                                @if($division->users->contains('id', $user->id))
                                                <li class="list-group-item d-flex justify-content-between align-items-center anggota-item" data-user-id="{{ $user->id }}">
                                                    <span>{{ $user->full_name }} <small class="text-muted">({{ $user->username }} - {{ $user->email }})</small></span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-remove" title="Keluarkan"><span>-</span></button>
                                                    <input type="hidden" name="division_users[]" value="{{ $user->id }}">
                                                </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="my-2 text-center" style="margin: 0.1rem 0;">
                                    <span style="font-size:0.85rem; margin: 0 2px;">&#8593;</span>
                                    <span style="font-size:0.85rem; margin: 0 2px;">&#8595;</span>
                                </div>
                                <div class="w-100 mt-2">
                                    <div class="border rounded p-2 mb-2">
                                        <strong>Bukan Anggota</strong>
                                        <ul class="list-group" id="bukan-anggota-list">
                                            @foreach($allUsers as $user)
                                                @if(!$division->users->contains('id', $user->id))
                                                <li class="list-group-item d-flex justify-content-between align-items-center bukan-anggota-item" data-user-id="{{ $user->id }}">
                                                    <span>{{ $user->full_name }} <small class="text-muted">({{ $user->username }} - {{ $user->email }})</small></span>
                                                    <button type="button" class="btn btn-sm btn-outline-success ms-2 btn-add" title="Jadikan Anggota"><span>+</span></button>
                                                </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">Pindahkan user antar tabel untuk mengatur anggota divisi.</small>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Tambah ke anggota
                                document.querySelectorAll('.btn-add').forEach(function(btn) {
                                    btn.addEventListener('click', function() {
                                        var li = btn.closest('li');
                                        var userId = li.getAttribute('data-user-id');
                                        var input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'division_users[]';
                                        input.value = userId;
                                        li.appendChild(input);
                                        btn.classList.remove('btn-outline-success', 'btn-add');
                                        btn.classList.add('btn-outline-danger', 'btn-remove');
                                        btn.innerHTML = '<span>-</span>';
                                        document.getElementById('anggota-list').appendChild(li);
                                    });
                                });
                                // Keluarkan dari anggota
                                document.querySelectorAll('.btn-remove').forEach(function(btn) {
                                    btn.addEventListener('click', function() {
                                        var li = btn.closest('li');
                                        var input = li.querySelector('input[type="hidden"]');
                                        if(input) input.remove();
                                        btn.classList.remove('btn-outline-danger', 'btn-remove');
                                        btn.classList.add('btn-outline-success', 'btn-add');
                                        btn.innerHTML = '<span>+</span>';
                                        document.getElementById('bukan-anggota-list').appendChild(li);
                                    });
                                });
                                // Delegasi event untuk tombol yang baru dipindah
                                document.getElementById('anggota-list').addEventListener('click', function(e) {
                                    if(e.target.closest('.btn-remove')) {
                                        var btn = e.target.closest('.btn-remove');
                                        var li = btn.closest('li');
                                        var input = li.querySelector('input[type="hidden"]');
                                        if(input) input.remove();
                                        btn.classList.remove('btn-outline-danger', 'btn-remove');
                                        btn.classList.add('btn-outline-success', 'btn-add');
                                        btn.innerHTML = '<span>+</span>';
                                        document.getElementById('bukan-anggota-list').appendChild(li);
                                    }
                                });
                                document.getElementById('bukan-anggota-list').addEventListener('click', function(e) {
                                    if(e.target.closest('.btn-add')) {
                                        var btn = e.target.closest('.btn-add');
                                        var li = btn.closest('li');
                                        var userId = li.getAttribute('data-user-id');
                                        var input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'division_users[]';
                                        input.value = userId;
                                        li.appendChild(input);
                                        btn.classList.remove('btn-outline-success', 'btn-add');
                                        btn.classList.add('btn-outline-danger', 'btn-remove');
                                        btn.innerHTML = '<span>-</span>';
                                        document.getElementById('anggota-list').appendChild(li);
                                    }
                                });
                            });
                        </script>

                        <!-- Division Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Informasi Divisi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Tanggal Dibuat:</strong> 
                                            @if($division->created_at)
                                                {{ $division->created_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                        <p><strong>Terakhir Update:</strong> 
                                            @if($division->updated_at)
                                                {{ $division->updated_at->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Jumlah Users:</strong> <span class="badge bg-info">{{ $division->users_count ?? 0 }}</span></p>
                                        <p><strong>Jumlah Surat:</strong> <span class="badge bg-primary">{{ $division->surat_count ?? 0 }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Divisi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 