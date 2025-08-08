<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NomorUrutLock extends Model
{
    use HasFactory;

    // ================================= FILLABLE =================================
    
    protected $fillable = [
        'divisi_id',
        'jenis_surat_id',
        'nomor_urut',
        'month_year',
        'user_id',
        'locked_until'
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'jenis_surat_id' => 'integer',
        'divisi_id' => 'integer',
        'nomor_urut' => 'integer',
        'user_id' => 'integer',
    ];

    // ================================= RELASI =================================

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // ================================= STATIC METHODS =================================
    
    public static function createOrExtendLock(int $divisiId, int $jenisSuratId, int $nomorUrut, int $userId, string $monthYear = null): self
    {
        if (!$monthYear) {
            $monthYear = now()->format('Y-m');
        }
        
        return static::updateOrCreate([
            'divisi_id' => $divisiId,
            'jenis_surat_id' => $jenisSuratId,
            'nomor_urut' => $nomorUrut,
            'month_year' => $monthYear,
        ], [
            'user_id' => $userId,
            'locked_until' => now()->addMinutes(30),
        ]);
    }
    
    public static function cleanupExpiredLocks(): int
    {
        $deleted = static::where('locked_until', '<', now())->delete();
        
        if ($deleted > 0) {
            Log::info("Cleaned up {$deleted} expired nomor urut locks");
        }
        
        return $deleted;
    }
    
    /**
     * Aggressive cleanup of locks that might be orphaned
     * This removes locks older than 1 hour regardless of expiry
     */
    public static function cleanupOrphanedLocks(): int
    {
        $deleted = static::where('created_at', '<', now()->subHour())->delete();
        
        if ($deleted > 0) {
            Log::info("Cleaned up {$deleted} potentially orphaned nomor urut locks (older than 1 hour)");
        }
        
        return $deleted;
    }
    
    /**
     * Get locks that are about to expire (within 5 minutes)
     */
    public static function getExpiringLocks()
    {
        return static::where('locked_until', '>', now())
            ->where('locked_until', '<', now()->addMinutes(5))
            ->with(['user', 'division', 'jenisSurat'])
            ->get();
    }
    
    public static function cancelUserLocks(int $userId): int
    {
        $deleted = static::where('user_id', $userId)->delete();
        
        if ($deleted > 0) {
            Log::info("Cancelled {$deleted} locks for user {$userId}");
        }
        
        return $deleted;
    }
    
    public static function isLockedByOtherUser(int $divisiId, int $jenisSuratId, int $nomorUrut, int $userId, string $monthYear = null): bool
    {
        if (!$monthYear) {
            $monthYear = now()->format('Y-m');
        }
        
        return static::where('divisi_id', $divisiId)
            ->where('jenis_surat_id', $jenisSuratId)
            ->where('nomor_urut', $nomorUrut)
            ->where('month_year', $monthYear)
            ->where('user_id', '!=', $userId)
            ->where('locked_until', '>', now())
            ->exists();
    }
    
    public static function extendUserLock(int $userId, ?int $divisiId = null, ?int $jenisSuratId = null): int
    {
        $query = static::where('user_id', $userId);
        
        if ($divisiId) {
            $query->where('divisi_id', $divisiId);
        }
        
        if ($jenisSuratId) {
            $query->where('jenis_surat_id', $jenisSuratId);
        }
        
        $updated = $query->update([
            'locked_until' => now()->addMinutes(30)
        ]);
        
        if ($updated > 0) {
            Log::info("Extended {$updated} locks for user {$userId}");
        }
        
        return $updated;
    }
    
    public static function cleanupUserDuplicateLocks(int $userId, int $divisiId): int
    {
        $deleted = static::where('user_id', $userId)
            ->where('divisi_id', $divisiId)
            ->delete();
            
        if ($deleted > 0) {
            Log::info("Cleaned up {$deleted} duplicate locks for user {$userId} in division {$divisiId}");
        }
        
        return $deleted;
    }
}