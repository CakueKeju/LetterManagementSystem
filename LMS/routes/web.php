<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Surat routes
    Route::get('/surat/upload', [App\Http\Controllers\SuratController::class, 'showUploadForm'])->name('surat.upload');
    Route::post('/surat/upload', [App\Http\Controllers\SuratController::class, 'handleUpload'])->name('surat.handleUpload');
    Route::get('/surat/confirm', [App\Http\Controllers\SuratController::class, 'showConfirmForm'])->name('surat.confirm');
    Route::post('/surat/store', [App\Http\Controllers\SuratController::class, 'store'])->name('surat.store');
    Route::post('/surat/final-store', [App\Http\Controllers\SuratController::class, 'finalStore'])->name('surat.final-store');
    Route::post('/surat/store-from-preview', [App\Http\Controllers\SuratController::class, 'storeFromPreview'])->name('surat.store-from-preview');
    Route::post('/surat/preview', [App\Http\Controllers\SuratController::class, 'preview'])->name('surat.preview');
    Route::get('/surat/preview', [App\Http\Controllers\SuratController::class, 'preview'])->name('surat.preview.get');
    Route::get('/surat/get-users-for-access', [App\Http\Controllers\SuratController::class, 'getUsersForAccess'])->name('surat.getUsersForAccess');
    Route::get('/surat', [App\Http\Controllers\SuratController::class, 'index'])->name('surat.index');
    
    // API routes for dynamic functionality
    Route::post('/api/next-nomor-urut', function (Request $request) {
        $divisiId = $request->input('divisi_id');
        $jenisSuratId = $request->input('jenis_surat_id');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Divisi ID dan Jenis Surat ID diperlukan'], 400);
        }
        
        $controller = new \App\Http\Controllers\SuratController();
        $nextNomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId);
        
        return response()->json(['next_nomor_urut' => $nextNomorUrut]);
    })->middleware('auth');

    Route::get('/api/next-nomor-urut', function (Request $request) {
        $divisiId = $request->input('divisi_id');
        $jenisSuratId = $request->input('jenis_surat_id');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Divisi ID dan Jenis Surat ID diperlukan'], 400);
        }
        
        $controller = new \App\Http\Controllers\SuratController();
        $nextNomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId);
        
        return response()->json(['next_nomor_urut' => $nextNomorUrut]);
    })->middleware('auth');
    
    Route::get('/api/lock-nomor-urut', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }
        
        $controller = new App\Http\Controllers\SuratController();
        $nomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId);
        
        if ($nomorUrut) {
            \App\Models\NomorUrutLock::updateOrCreate([
                'divisi_id' => $divisiId,
                'jenis_surat_id' => $jenisSuratId,
                'nomor_urut' => $nomorUrut,
            ], [
                'user_id' => \Auth::id(),
                'locked_until' => now()->addMinutes(10),
            ]);
        }
        
        return response()->json(['nomor_urut' => $nomorUrut]);
    });
    
    Route::get('/api/cancel-nomor-urut-lock', function () {
        \App\Models\NomorUrutLock::where('user_id', \Auth::id())->delete();
        return response()->json(['success' => true]);
    });
});

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
