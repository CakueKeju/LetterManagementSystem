<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['reset' => false]);

Route::middleware(['auth'])->group(function () {
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Surat routes
    Route::get('/surat/upload', [App\Http\Controllers\SuratController::class, 'showUploadForm'])->name('surat.upload');
    Route::post('/surat/upload', [App\Http\Controllers\SuratController::class, 'handleUpload'])->name('surat.handleUpload');
    Route::get('/surat/confirm', [App\Http\Controllers\SuratController::class, 'showConfirmForm'])->name('surat.confirm');
    Route::get('/surat/store', function() { return redirect()->route('surat.upload'); });
    Route::post('/surat/store', [App\Http\Controllers\SuratController::class, 'store'])->name('surat.store');
    Route::post('/surat/final-store', [App\Http\Controllers\SuratController::class, 'finalStore'])->name('surat.final-store');
    Route::post('/surat/store-from-preview', [App\Http\Controllers\SuratController::class, 'storeFromPreview'])->name('surat.store-from-preview');
    Route::post('/surat/preview', [App\Http\Controllers\SuratController::class, 'preview'])->name('surat.preview');
    Route::get('/surat/preview', [App\Http\Controllers\SuratController::class, 'preview'])->name('surat.preview.get');
    Route::get('/surat/get-users-for-access', [App\Http\Controllers\SuratController::class, 'getUsersForAccess'])->name('surat.getUsersForAccess');
    Route::get('/surat', [App\Http\Controllers\SuratController::class, 'index'])->name('surat.index');
    
    // Route untuk serve file surat dengan permission check
    Route::get('/surat/file/{id}', [App\Http\Controllers\SuratController::class, 'serveFile'])->name('surat.file');
    
    // Route untuk download file surat
    Route::get('/surat/download/{id}', [App\Http\Controllers\SuratController::class, 'downloadFile'])->name('surat.download');
    
    // API routes for dynamic functionality
    Route::match(['GET', 'POST'], '/api/next-nomor-urut', function (Request $request) {
        $divisiId = $request->input('divisi_id');
        $jenisSuratId = $request->input('jenis_surat_id');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Divisi ID dan Jenis Surat ID diperlukan'], 400);
        }
        
        $controller = new \App\Http\Controllers\SuratController();
        $nextNomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId);
        
        return response()->json(['next_nomor_urut' => $nextNomorUrut]);
    })->middleware('auth');
    
    // API route for getting jenis surat by division
    Route::get('/api/jenis-surat-by-division', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        
        if (!$divisiId) {
            return response()->json(['error' => 'Divisi ID diperlukan'], 400);
        }
        
        $jenisSurat = \App\Models\JenisSurat::where('divisi_id', $divisiId)
            ->where('is_active', true)
            ->select('id', 'nama_jenis', 'kode_jenis')
            ->orderBy('nama_jenis')
            ->get();
        
        return response()->json(['jenis_surat' => $jenisSurat]);
    })->middleware('auth');
    
    // API route for getting non-admin users (for private surat selection)
    Route::get('/api/users', function () {
        $users = \App\Models\User::select('id', 'username', 'full_name', 'email')
            ->where('is_active', true)
            ->where('is_admin', false) // Exclude admin users
            ->where('id', '!=', \Auth::id()) // Exclude current user (pengupload)
            ->orderBy('full_name')
            ->get();
        
        return response()->json($users);
    })->middleware('auth');
    
    Route::get('/api/lock-nomor-urut', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }
        
        // Clean up expired locks first
        \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        $controller = new App\Http\Controllers\SuratController();
        $nomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId);
        
        if ($nomorUrut) {
            // Check if this nomor is locked by another user
            if (\App\Models\NomorUrutLock::isLockedByOtherUser($divisiId, $jenisSuratId, $nomorUrut, \Auth::id())) {
                return response()->json([
                    'error' => 'Nomor urut ini sedang digunakan oleh pengguna lain',
                    'nomor_urut' => null
                ], 409);
            }
            
            // Create or extend lock for 30 minutes
            \App\Models\NomorUrutLock::createOrExtendLock($divisiId, $jenisSuratId, $nomorUrut, \Auth::id());
        }
        
        return response()->json(['nomor_urut' => $nomorUrut]);
    });
    
    // New route to extend/keep alive the lock
    Route::post('/api/extend-nomor-urut-lock', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        
        $extended = \App\Models\NomorUrutLock::extendUserLock(\Auth::id(), $divisiId, $jenisSuratId);
        
        return response()->json([
            'success' => $extended > 0,
            'extended_locks' => $extended
        ]);
    });
    
    // New heartbeat endpoint to check if user is still active
    Route::post('/api/heartbeat-nomor-urut-lock', function () {
        $userId = \Auth::id();
        
        // Update last activity for user's locks
        $updated = \App\Models\NomorUrutLock::where('user_id', $userId)
            ->update(['locked_until' => now()->addMinutes(30)]);
        
        // Cleanup expired locks from other users
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        // Also cleanup orphaned locks (older than 1 hour) occasionally
        $orphanedCleaned = 0;
        if (rand(1, 10) === 1) { // 10% chance to run orphaned cleanup
            $orphanedCleaned = \App\Models\NomorUrutLock::cleanupOrphanedLocks();
        }
        
        return response()->json([
            'success' => true,
            'updated_locks' => $updated,
            'cleaned_expired' => $expiredCleaned,
            'cleaned_orphaned' => $orphanedCleaned,
            'timestamp' => now()->toISOString()
        ]);
    });
    
    // Manual cleanup endpoint for admin
    Route::post('/api/cleanup-all-nomor-urut-locks', function () {
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        $orphanedCleaned = \App\Models\NomorUrutLock::cleanupOrphanedLocks();
        
        return response()->json([
            'success' => true,
            'cleaned_expired' => $expiredCleaned,
            'cleaned_orphaned' => $orphanedCleaned,
            'message' => "Cleaned up {$expiredCleaned} expired and {$orphanedCleaned} orphaned locks"
        ]);
    })->middleware('admin');
    
    Route::match(['GET', 'POST'], '/api/cancel-nomor-urut-lock', function () {
        $userId = \Auth::id();
        
        // Cancel user's locks
        $cancelled = \App\Models\NomorUrutLock::cancelUserLocks($userId);
        
        // Immediate cleanup of expired locks (triggered by user action)
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        // Log the cleanup activity
        if ($cancelled > 0 || $expiredCleaned > 0) {
            \Log::info('Lock cleanup triggered by user cancel', [
                'user_id' => $userId,
                'cancelled_user_locks' => $cancelled,
                'cleaned_expired_locks' => $expiredCleaned
            ]);
        }
        
        return response()->json([
            'success' => true,
            'cancelled_locks' => $cancelled,
            'cleaned_expired_locks' => $expiredCleaned
        ]);
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
    
    // Reset Counter Routes (for testing)
    Route::post('/jenis-surat/{id}/reset-counter', [AdminController::class, 'resetJenisSuratCounter'])->name('jenis-surat.reset-counter');
    Route::post('/jenis-surat/reset-all-counters', [AdminController::class, 'resetAllCounters'])->name('jenis-surat.reset-all-counters');
});

Route::middleware(['auth', 'admin'])->group(function() {
    Route::get('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'showUploadForm'])->name('admin.surat.upload');
    Route::post('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'handleUpload'])->name('admin.surat.handleUpload');
    Route::post('/admin/surat/store', [App\Http\Controllers\AdminController::class, 'store'])->name('admin.surat.store');
});
