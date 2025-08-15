@if($success)
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="fas fa-check-circle"></i> Upload Berhasil!</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle"></i> Surat berhasil diupload!</h5>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Nomor Surat:</strong> {{ $surat->nomor_surat }}<br>
                        <strong>Perihal:</strong> {{ $surat->perihal }}<br>
                        <strong>Tanggal Surat:</strong> {{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d-m-Y') }}<br>
                    </div>
                    <div class="col-md-6">
                        <strong>Divisi:</strong> {{ $surat->division->nama_divisi }}<br>
                        <strong>Jenis Surat:</strong> {{ $surat->jenisSurat->nama_jenis }}<br>
                        <strong>Status:</strong> 
                        @if($surat->is_private)
                            <span class="badge bg-warning">Private</span>
                        @else
                            <span class="badge bg-info">Public</span>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="{{ route('admin.surat.mode.selection') }}" class="btn btn-info">
                    <i class="fas fa-upload"></i> Upload Lagi
                </a>
                <a href="{{ route('admin.surat.index') }}" class="btn btn-primary">
                    <i class="fas fa-list"></i> Lihat Semua Surat
                </a>
            </div>
        </div>
    </div>
@else
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0"><i class="fas fa-times-circle"></i> Upload Gagal!</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <h5><i class="fas fa-times-circle"></i> Terjadi kesalahan saat upload surat!</h5>
                <hr>
                <p><strong>Error:</strong> {{ $error ?? 'Unknown error occurred' }}</p>
                @if(isset($details))
                    <p><strong>Detail:</strong> {{ $details }}</p>
                @endif
            </div>
            
            <div class="text-center">
                <button type="button" class="btn btn-warning" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Coba Lagi
                </button>
                <a href="{{ route('admin.surat.mode.selection') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pemilihan Mode
                </a>
            </div>
        </div>
    </div>
@endif
