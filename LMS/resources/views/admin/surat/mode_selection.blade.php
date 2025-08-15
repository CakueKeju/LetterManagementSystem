@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="text-center mb-5">Pilih Mode Upload Surat (Admin)</h2>
            
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
                                    Pilih divisi bebas
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Konfirmasi dan simpan
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="{{ route('admin.surat.automatic.upload') }}" class="btn btn-primary btn-lg w-100">
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
                                <i class="fas fa-hand-paper fa-2x me-3"></i>
                                <div>
                                    <h4 class="mb-0">Mode Manual</h4>
                                    <small>Generate → Copy → Upload</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="feature-list mb-4">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Generate nomor dulu
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Copy nomor ke file
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Pilih divisi bebas
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Upload file final
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="{{ route('admin.surat.manual.form') }}" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-edit me-2"></i>Pilih Mode Manual
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="{{ route('admin.surat.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-list me-2"></i>Lihat Semua Surat
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.mode-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.mode-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.feature-list {
    flex-grow: 1;
}

.feature-item {
    padding: 8px 0;
    font-size: 0.95rem;
}

.feature-item i {
    width: 20px;
}
</style>

<script>
// Add click handlers to cards for better UX
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.mode-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            const link = card.querySelector('a');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
});
</script>
@endsection
