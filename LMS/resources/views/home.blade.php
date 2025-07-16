@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>
                <div class="card-body">
                    <h4>Selamat datang di Letter Management System!</h4>
                    <p class="mb-4">Silakan mulai dengan mengunggah surat baru atau melihat daftar surat yang sudah diunggah.</p>
                    <div class="d-flex gap-3 mb-3">
                        <a href="{{ route('surat.upload') }}" class="btn btn-success btn-lg">Upload Surat & Scan</a>
                        <a href="{{ route('surat.index') }}" class="btn btn-outline-primary btn-lg">Daftar Surat</a>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
