<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['reset' => false]);

// ================================= MIDDLEWARE AUTH =================================

Route::middleware(['auth', 'active'])->group(function () {
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // ================================= SURAT MODE SELECTION =================================
    
    Route::get('/surat/mode-selection', [App\Http\Controllers\SuratController::class, 'showModeSelection'])->name('surat.mode.selection');
    
    // ================================= SURAT AUTOMATIC MODE =================================
    
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
    
    // ================================= SURAT MANUAL MODE =================================
    
    Route::get('/surat/manual/form', [App\Http\Controllers\SuratController::class, 'showManualForm'])->name('surat.manual.form');
    Route::post('/surat/manual/generate', [App\Http\Controllers\SuratController::class, 'manualGenerate'])->name('surat.manual.generate');
    Route::post('/surat/manual/upload', [App\Http\Controllers\SuratController::class, 'manualHandleUpload'])->name('surat.manual.handleUpload');
    Route::get('/surat/manual/result', [App\Http\Controllers\SuratController::class, 'manualVerification'])->name('surat.manual.result');
    Route::get('/surat/manual/re-edit', [App\Http\Controllers\SuratController::class, 'manualReEdit'])->name('surat.manual.reEdit');
    Route::post('/surat/manual/re-upload', [App\Http\Controllers\SuratController::class, 'manualReUpload'])->name('surat.manual.reUpload');
    
    // ================================= FILE SERVE & DOWNLOAD =================================
    
    Route::get('/surat/file/{id}', [App\Http\Controllers\SuratController::class, 'serveFile'])->name('surat.file');
    Route::get('/surat/download/{id}', [App\Http\Controllers\SuratController::class, 'downloadFile'])->name('surat.download');
    
    // ================================= API ROUTES =================================
    
    // API next nomor urut
    Route::match(['GET', 'POST'], '/api/next-nomor-urut', function (Request $request) {
        $divisiId = $request->input('divisi_id');
        $jenisSuratId = $request->input('jenis_surat_id');
        $tanggalSurat = $request->input('tanggal_surat');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Divisi ID dan Jenis Surat ID diperlukan'], 400);
        }
        
        $controller = new \App\Http\Controllers\SuratController();
        $nextNomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat);
        
        return response()->json(['next_nomor_urut' => $nextNomorUrut]);
    })->middleware('auth');
    
    // API jenis surat by division
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
    
    // API get users non-admin
    Route::get('/api/users', function () {
        $users = \App\Models\User::select('id', 'username', 'full_name', 'email')
            ->where('is_active', true)
            ->where('is_admin', false) // exclude admin
            ->where('id', '!=', \Auth::id()) // exclude current user
            ->orderBy('full_name')
            ->get();
        
        return response()->json($users);
    })->middleware('auth');
    
    // API lock nomor urut
    Route::get('/api/lock-nomor-urut', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        $tanggalSurat = $request->get('tanggal_surat');
        
        if (!$divisiId || !$jenisSuratId) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }
        
        // cleanup lock expired
        \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        \Log::info('Lock API called:', [
            'user_id' => \Auth::id(),
            'divisi_id' => $divisiId,
            'jenis_surat_id' => $jenisSuratId,
            'tanggal_surat' => $tanggalSurat
        ]);
        
        // hapus lock user untuk prevent duplikat
        $deletedLocks = \App\Models\NomorUrutLock::where('user_id', \Auth::id())->delete();
        
        \Log::info('User locks cleaned:', [
            'user_id' => \Auth::id(),
            'deleted_count' => $deletedLocks
        ]);
        
        $controller = new App\Http\Controllers\SuratController();
        $nomorUrut = $controller->getNextNomorUrut($divisiId, $jenisSuratId, $tanggalSurat);
        
        // get month-year untuk lock
        $monthYear = $tanggalSurat ? 
            \Carbon\Carbon::parse($tanggalSurat)->format('Y-m') : 
            \Carbon\Carbon::now()->format('Y-m');
        
        if ($nomorUrut) {
            // cek nomor urut di-lock user lain
            if (\App\Models\NomorUrutLock::isLockedByOtherUser($divisiId, $jenisSuratId, $nomorUrut, \Auth::id(), $monthYear)) {
                return response()->json([
                    'error' => 'Nomor urut ini sedang digunakan oleh pengguna lain',
                    'nomor_urut' => null
                ], 409);
            }
            
            // create atau extend lock 30 menit
            $lock = \App\Models\NomorUrutLock::createOrExtendLock($divisiId, $jenisSuratId, $nomorUrut, \Auth::id(), $monthYear);
            
            \Log::info('Lock created/extended:', [
                'lock_id' => $lock->id,
                'user_id' => \Auth::id(),
                'nomor_urut' => $nomorUrut,
                'month_year' => $monthYear
            ]);
        }
        
        return response()->json(['nomor_urut' => $nomorUrut]);
    });
    
    // API preview nomor urut tanpa lock
    Route::get('/api/preview-nomor-urut', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        $tanggalSurat = $request->get('tanggal_surat');
        
        if (!$divisiId || !$jenisSuratId || !$tanggalSurat) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }
        
        try {
            $date = new DateTime($tanggalSurat);
            $year = $date->format('Y');
            $month = $date->format('m');
            
            $counter = \App\Models\JenisSuratCounter::peekNextForMonth($jenisSuratId, $year, $month);
            
            return response()->json(['nomor_urut' => $counter]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }
    });
    
    // API extend/keep alive lock
    Route::post('/api/extend-nomor-urut-lock', function (Request $request) {
        $divisiId = $request->get('divisi_id');
        $jenisSuratId = $request->get('jenis_surat_id');
        
        $extended = \App\Models\NomorUrutLock::extendUserLock(\Auth::id(), $divisiId, $jenisSuratId);
        
        return response()->json([
            'success' => $extended > 0,
            'extended_locks' => $extended
        ]);
    });
    
    // API heartbeat untuk cek user aktif
    Route::post('/api/heartbeat-nomor-urut-lock', function () {
        $userId = \Auth::id();
        
        // update last activity user locks
        $updated = \App\Models\NomorUrutLock::where('user_id', $userId)
            ->update(['locked_until' => now()->addMinutes(30)]);
        
        // cleanup lock expired dari user lain
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        // cleanup orphaned locks kadang-kadang
        $orphanedCleaned = 0;
        if (rand(1, 10) === 1) { // 10% chance
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
    
    // API manual cleanup untuk admin
    Route::post('/api/cleanup-all-nomor-urut-locks', function () {
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        $orphanedCleaned = \App\Models\NomorUrutLock::cleanupOrphanedLocks();
        
        return response()->json([
            'success' => true,
            'cleaned_expired' => $expiredCleaned,
            'cleaned_orphaned' => $orphanedCleaned,
            'message' => "Cleaned {$expiredCleaned} expired dan {$orphanedCleaned} orphaned locks"
        ]);
    })->middleware(['active', 'admin']);
    
    // API cancel lock
    Route::match(['GET', 'POST'], '/api/cancel-nomor-urut-lock', function () {
        $userId = \Auth::id();
        
        // cancel user locks
        $cancelled = \App\Models\NomorUrutLock::cancelUserLocks($userId);
        
        // cleanup expired locks
        $expiredCleaned = \App\Models\NomorUrutLock::cleanupExpiredLocks();
        
        // log cleanup activity
        if ($cancelled > 0 || $expiredCleaned > 0) {
            \Log::info('Lock cleanup by user cancel', [
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

// ================================= ADMIN ROUTES =================================

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // surat management
    Route::get('/surat', [AdminController::class, 'suratIndex'])->name('surat.index');
    Route::get('/surat/{id}/edit', [AdminController::class, 'suratEdit'])->name('surat.edit');
    Route::put('/surat/{id}', [AdminController::class, 'suratUpdate'])->name('surat.update');
    Route::delete('/surat/{id}', [AdminController::class, 'suratDestroy'])->name('surat.destroy');
    
    // user management
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'usersCreate'])->name('users.create');
    Route::post('/users', [AdminController::class, 'usersStore'])->name('users.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'usersEdit'])->name('users.edit');
    Route::put('/users/{id}', [AdminController::class, 'usersUpdate'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'usersDestroy'])->name('users.destroy');
    
    // division management
    Route::get('/divisions', [AdminController::class, 'divisionsIndex'])->name('divisions.index');
    Route::get('/divisions/create', [AdminController::class, 'divisionsCreate'])->name('divisions.create');
    Route::post('/divisions', [AdminController::class, 'divisionsStore'])->name('divisions.store');
    Route::get('/divisions/{id}/edit', [AdminController::class, 'divisionsEdit'])->name('divisions.edit');
    Route::put('/divisions/{id}', [AdminController::class, 'divisionsUpdate'])->name('divisions.update');
    Route::delete('/divisions/{id}', [AdminController::class, 'divisionsDestroy'])->name('divisions.destroy');
    
    // jenis surat management
    Route::get('/jenis-surat', [AdminController::class, 'jenisSuratIndex'])->name('jenis-surat.index');
    Route::get('/jenis-surat/create', [AdminController::class, 'jenisSuratCreate'])->name('jenis-surat.create');
    Route::post('/jenis-surat', [AdminController::class, 'jenisSuratStore'])->name('jenis-surat.store');
    Route::get('/jenis-surat/{id}/edit', [AdminController::class, 'jenisSuratEdit'])->name('jenis-surat.edit');
    Route::put('/jenis-surat/{id}', [AdminController::class, 'jenisSuratUpdate'])->name('jenis-surat.update');
    Route::delete('/jenis-surat/{id}', [AdminController::class, 'jenisSuratDestroy'])->name('jenis-surat.destroy');
    
    // reset counter untuk testing
    Route::post('/jenis-surat/{id}/reset-counter', [AdminController::class, 'resetJenisSuratCounter'])->name('jenis-surat.reset-counter');
    Route::post('/jenis-surat/reset-all-counters', [AdminController::class, 'resetAllCounters'])->name('jenis-surat.reset-all-counters');
});

// ================================= ADMIN SURAT ROUTES =================================

Route::middleware(['auth', 'active', 'admin'])->group(function() {
    // Mode selection for admin surat upload
    Route::get('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'suratModeSelection'])->name('admin.surat.mode.selection');
    
    // Automatic mode routes
    Route::get('/admin/surat/automatic/upload', [App\Http\Controllers\AdminController::class, 'automaticUploadForm'])->name('admin.surat.automatic.upload');
    Route::post('/admin/surat/automatic/upload', [App\Http\Controllers\AdminController::class, 'automaticHandleUpload'])->name('admin.surat.automatic.handleUpload');
    Route::post('/admin/surat/automatic/store', [App\Http\Controllers\AdminController::class, 'automaticStore'])->name('admin.surat.automatic.store');
    
    // Manual mode routes  
    Route::get('/admin/surat/manual/form', [App\Http\Controllers\AdminController::class, 'manualForm'])->name('admin.surat.manual.form');
    Route::post('/admin/surat/manual/upload', [App\Http\Controllers\AdminController::class, 'manualHandleUpload'])->name('admin.surat.manual.handleUpload');
    
    // Legacy routes (keep for backward compatibility)
    Route::post('/admin/surat/upload', [App\Http\Controllers\AdminController::class, 'handleUpload'])->name('admin.surat.handleUpload');
    Route::post('/admin/surat/store', [App\Http\Controllers\AdminController::class, 'store'])->name('admin.surat.store');
});
