<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\AdminController;

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
Route::get('/surat/users-for-access', [SuratController::class, 'getUsersForAccess'])->name('surat.getUsersForAccess');
Route::post('/surat/preview', [App\Http\Controllers\SuratController::class, 'preview'])->name('surat.preview');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Surat Management
    Route::get('/surat', [AdminController::class, 'suratIndex'])->name('surat.index');
    Route::get('/surat/{id}/edit', [AdminController::class, 'suratEdit'])->name('surat.edit');
    Route::put('/surat/{id}', [AdminController::class, 'suratUpdate'])->name('surat.update');
    Route::delete('/surat/{id}', [AdminController::class, 'suratDestroy'])->name('surat.destroy');
    
    // User Management
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'usersCreate'])->name('users.create');
    Route::post('/users', [AdminController::class, 'usersStore'])->name('users.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'usersEdit'])->name('users.edit');
    Route::put('/users/{id}', [AdminController::class, 'usersUpdate'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'usersDestroy'])->name('users.destroy');
    
    // Division Management
    Route::get('/divisions', [AdminController::class, 'divisionsIndex'])->name('divisions.index');
    Route::get('/divisions/create', [AdminController::class, 'divisionsCreate'])->name('divisions.create');
    Route::post('/divisions', [AdminController::class, 'divisionsStore'])->name('divisions.store');
    Route::get('/divisions/{id}/edit', [AdminController::class, 'divisionsEdit'])->name('divisions.edit');
    Route::put('/divisions/{id}', [AdminController::class, 'divisionsUpdate'])->name('divisions.update');
    Route::delete('/divisions/{id}', [AdminController::class, 'divisionsDestroy'])->name('divisions.destroy');
    
    // Jenis Surat Management
    Route::get('/jenis-surat', [AdminController::class, 'jenisSuratIndex'])->name('jenis-surat.index');
    Route::get('/jenis-surat/create', [AdminController::class, 'jenisSuratCreate'])->name('jenis-surat.create');
    Route::post('/jenis-surat', [AdminController::class, 'jenisSuratStore'])->name('jenis-surat.store');
    Route::get('/jenis-surat/{id}/edit', [AdminController::class, 'jenisSuratEdit'])->name('jenis-surat.edit');
    Route::put('/jenis-surat/{id}', [AdminController::class, 'jenisSuratUpdate'])->name('jenis-surat.update');
    Route::delete('/jenis-surat/{id}', [AdminController::class, 'jenisSuratDestroy'])->name('jenis-surat.destroy');
});

Route::middleware(['auth', 'admin'])->group(function() {
    Route::get('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'showUploadForm'])->name('admin.surat.upload');
    Route::post('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'handleUpload'])->name('admin.surat.handleUpload');
});

Route::get('/api/next-nomor-urut', function (\Illuminate\Http\Request $request) {
    $divisiId = $request->query('divisi_id');
    $jenisSuratId = $request->query('jenis_surat_id');
    $existingNumbers = \App\Models\Surat::where('divisi_id', $divisiId)
        ->where('jenis_surat_id', $jenisSuratId)
        ->pluck('nomor_urut')
        ->sort()
        ->values();
    $nextNomorUrut = 1;
    foreach ($existingNumbers as $existingNumber) {
        if ($existingNumber > $nextNomorUrut) {
            break;
        }
        $nextNomorUrut = $existingNumber + 1;
    }
    return response()->json(['next_nomor_urut' => $nextNomorUrut]);
});

Route::get('/api/lock-nomor-urut', function (\Illuminate\Http\Request $request) {
    $divisiId = $request->query('divisi_id');
    $jenisSuratId = $request->query('jenis_surat_id');
    $userId = auth()->id();
    if (!$divisiId || !$jenisSuratId) {
        return response()->json(['error' => 'divisi_id dan jenis_surat_id wajib'], 400);
    }
    // Hapus semua lock milik user ini (untuk divisi/jenis surat apapun)
    \App\Models\NomorUrutLock::where('user_id', $userId)->delete();
    $usedNumbers = \App\Models\Surat::where('divisi_id', $divisiId)
        ->where('jenis_surat_id', $jenisSuratId)
        ->pluck('nomor_urut')
        ->toArray();
    $lockedNumbers = \App\Models\NomorUrutLock::where('divisi_id', $divisiId)
        ->where('jenis_surat_id', $jenisSuratId)
        ->where(function($q) {
            $q->whereNull('locked_until')->orWhere('locked_until', '>', now());
        })
        ->pluck('nomor_urut')
        ->toArray();
    $allUsed = array_unique(array_merge($usedNumbers, $lockedNumbers));
    $nomorUrut = null;
    for ($i = 1; $i <= 999; $i++) {
        if (!in_array($i, $allUsed)) {
            $nomorUrut = $i;
            break;
        }
    }
    if ($nomorUrut) {
        \App\Models\NomorUrutLock::updateOrCreate([
            'divisi_id' => $divisiId,
            'jenis_surat_id' => $jenisSuratId,
            'nomor_urut' => $nomorUrut,
        ], [
            'user_id' => $userId,
            'locked_until' => now()->addMinutes(10),
        ]);
    }
    return response()->json(['nomor_urut' => $nomorUrut]);
});
