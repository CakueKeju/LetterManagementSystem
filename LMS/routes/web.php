<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/surat', [SuratController::class, 'index'])->name('surat.index');
Route::get('/surat/upload', [SuratController::class, 'showUploadForm'])->name('surat.upload');
Route::post('/surat/upload', [SuratController::class, 'handleUpload'])->name('surat.handleUpload');
Route::get('/surat/confirm', [SuratController::class, 'showConfirmForm'])->name('surat.confirm');
Route::post('/surat/store', [SuratController::class, 'store'])->name('surat.store');
