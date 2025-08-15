@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @if($verification_success)
                {{-- SUCCESS PAGE --}}
                <div class="card border-success">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Upload Berhasil!
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle text-primary me-2"></i>Detail Surat</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Nomor Surat:</strong></td>
                                        <td><span class="badge bg-primary fs-6">{{ $surat->nomor_surat }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Perihal:</strong></td>
                                        <td>{{ $surat->perihal }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Divisi:</strong></td>
                                        <td>{{ $surat->division->nama_divisi }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jenis Surat:</strong></td>
                                        <td>{{ $surat->jenisSurat->nama_jenis }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Surat:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Diterima:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($surat->tanggal_diterima)->format('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            @if($surat->is_private)
                                                <span class="badge bg-warning">Private</span>
                                            @else
                                                <span class="badge bg-info">Public</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-file-alt text-success me-2"></i>File Info</h5>
                                <div class="alert alert-light">
                                    <p><strong>Ukuran File:</strong> {{ number_format($surat->file_size / 1024, 2) }} KB</p>
                                    <p class="mb-0"><strong>Tipe:</strong> {{ strtoupper(pathinfo($surat->file_path, PATHINFO_EXTENSION)) }}</p>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ route('surat.file', $surat->id) }}" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye me-2"></i>Lihat File
                                    </a>
                                    <a href="{{ route('surat.download', $surat->id) }}" class="btn btn-outline-success">
                                        <i class="fas fa-download me-2"></i>Download File
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Surat berhasil disimpan!</strong> Nomor surat sudah terdaftar di sistem.
                            </div>
                            
                            <div class="btn-group" role="group">
                                <a href="{{ route('surat.mode.selection') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Upload Surat Lagi
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-home me-2"></i>Kembali ke Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- FAILED VERIFICATION PAGE --}}
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white text-center">
                        <h3 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Verifikasi Gagal
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-times-circle me-2"></i>Masalah Ditemukan:</h6>
                            <p class="mb-0">{{ $failed_data['error_message'] }}</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-search text-primary me-2"></i>Perbandingan Nomor Surat</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p><strong>Yang Diharapkan:</strong></p>
                                        <div class="alert alert-info mb-3">
                                            <code>{{ $failed_data['expected_nomor_surat'] }}</code>
                                        </div>
                                        
                                        <p><strong>Yang Ditemukan di File:</strong></p>
                                        <div class="alert alert-warning">
                                            @if($failed_data['found_nomor_surat'])
                                                <code>{{ $failed_data['found_nomor_surat'] }}</code>
                                            @else
                                                <em class="text-muted">Tidak ditemukan nomor surat</em>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><i class="fas fa-file-alt text-info me-2"></i>Info File</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p><strong>Nama File:</strong> {{ $failed_data['original_filename'] }}</p>
                                        
                                        @if($failed_data['ocr_error'])
                                            <div class="alert alert-warning">
                                                <small><strong>Error OCR:</strong> {{ $failed_data['ocr_error'] }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($failed_data['extracted_text'])
                            <div class="mt-4">
                                <h6><i class="fas fa-file-text text-secondary me-2"></i>Teks yang Berhasil Diekstrak (Preview):</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <div style="max-height: 200px; overflow-y: auto; font-size: 0.9em; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                                            {{ Str::limit($failed_data['extracted_text'], 1000) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="text-center mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Solusi:</strong> Pastikan file yang di-upload sudah berisi nomor surat yang benar, atau edit form data untuk generate nomor surat yang baru.
                            </div>
                            
                            <div class="btn-group" role="group">
                                <a href="{{ route('surat.manual.reEdit') }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Edit Form & Upload Ulang
                                </a>
                                <a href="{{ route('surat.manual.form') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Mulai Upload Baru
                                </a>
                                <a href="{{ route('surat.mode.selection') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Kembali ke Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
