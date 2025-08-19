@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="text-center mb-5">Pilih Mode Upload Surat</h2>
            
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            <div class="row g-4">
                <!-- Mode Otomatis -->
                <div class="col-md-6">
                    <div class="card h-100 border-primary shadow-sm mode-card">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-magic fa-2x me-3"></i>
                                <div>
                                    <h4 class="mb-0">Mode Otomatis</h4>
                                    <small>Upload → Generate → Konfirmasi</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="feature-list mb-4">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Upload file kosong
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Sistem auto-generate nomor
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Konfirmasi dan simpan
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="{{ route('surat.upload') }}" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-upload me-2"></i>Pilih Mode Otomatis
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mode Manual -->
                <div class="col-md-6">
                    <div class="card h-100 border-success shadow-sm mode-card">
                        <div class="card-header bg-success text-white text-center py-3">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-bolt fa-2x me-3"></i>
                                <div>
                                    <h4 class="mb-0">Mode Manual</h4>
                                    <small>Auto Show → Upload → Done</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="feature-list mb-4">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Nomor ditampilkan secara otomatis
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Salin nomor ke file
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Upload file
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="{{ route('surat.manual.form') }}" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-edit me-2"></i>Pilih Mode Manual
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.mode-card {
    transition: all 0.3s ease;
    border-width: 2px !important;
}

.mode-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.feature-list {
    min-height: 120px;
}

.feature-item {
    padding: 6px 0;
    display: flex;
    align-items: center;
}

.card-header h4 {
    font-weight: 600;
}

.card-header small {
    opacity: 0.9;
    font-weight: 400;
}

.btn-lg {
    padding: 12px 24px;
    font-weight: 600;
}
</style>
@endsection
