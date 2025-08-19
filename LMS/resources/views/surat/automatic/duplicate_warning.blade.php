@extends('layouts.app')

@section('content')
<div class="container">
    <div class="alert alert-warning">
        <h4>⚠️ Peringatan: Nomor Urut Duplikat</h4>
        <p>Nomor urut <strong>{{ $nomor_urut }}</strong> sudah ada di divisi yang dipilih.</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Informasi Duplikat</h5>
        </div>
        <div class="card-body">
            <p><strong>Nomor yang terdeteksi:</strong> {{ $nomor_urut }}</p>
            <p><strong>Divisi:</strong> 
                @php
                    $division = \App\Models\Division::find($divisi_id);
                    echo $division ? $division->nama_divisi : 'Tidak terdeteksi';
                @endphp
            </p>
            <div class="mt-3">
                <h6>Nomor urut yang tersedia untuk divisi ini:</h6>
                <div class="row">
                    @php
                        // Generate available numbers (001-999)
                        $availableNumbers = [];
                        for ($i = 1; $i <= 999; $i++) {
                            $availableNumbers[] = sprintf('%03d', $i);
                        }
                    @endphp
                    @foreach(array_slice($availableNumbers, 0, 20) as $number)
                        <div class="col-md-2 mb-2">
                            <span class="badge bg-success">{{ $number }}</span>
                        </div>
                    @endforeach
                    @if(count($availableNumbers) > 20)
                        <div class="col-12">
                            <small class="text-muted">... dan {{ count($availableNumbers) - 20 }} nomor lainnya</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>Opsi 1: Upload Ulang dengan Nomor yang Benar</h6>
                    <p>Upload ulang dokumen dengan nomor urut yang tersedia di atas.</p>
                    <a href="{{ route('surat.upload') }}" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Ulang
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6>Opsi 2: Gunakan Nomor yang Tersedia</h6>
                    <p>Lanjutkan dengan salah satu nomor yang tersedia.</p>
                    <form action="{{ route('surat.upload') }}" method="GET">
                        <div class="mb-3">
                            <label for="suggested_number" class="form-label">Pilih Nomor:</label>
                            <select class="form-select" id="suggested_number" name="suggested_number">
                                @foreach(array_slice($availableNumbers, 0, 50) as $number)
                                    <option value="{{ $number }}">{{ $number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-arrow-right me-2"></i>Lanjutkan dengan Nomor Ini
                        </button>
                        <input type="hidden" name="file_path" value="{{ $file_path ?? '' }}">
                        <input type="hidden" name="file_size" value="{{ $file_size ?? '' }}">
                        <input type="hidden" name="mime_type" value="{{ $mime_type ?? '' }}">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 