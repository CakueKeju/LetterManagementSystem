<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SuratAccess extends Model
{
    use HasFactory;

    protected $table = 'surat_access';
    public $timestamps = false;

    // ================================= FILLABLE =================================
    
    protected $fillable = [
        'surat_id',
        'user_id',
        'granted_at',
        'notes',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'surat_id' => 'integer',
        'user_id' => 'integer',
    ];

    // ================================= RELASI =================================

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class, 'surat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantor(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Surat::class,
            'id',
            'id',
            'surat_id',
            'uploaded_by'
        );
    }

    public function scopeForSurat(Builder $query, int $suratId): Builder
    {
        return $query->where('surat_id', $suratId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGrantedToday(Builder $query): Builder
    {
        return $query->whereDate('granted_at', today());
    }

    public function scopeGrantedRecently(Builder $query, int $days = 7): Builder
    {
        return $query->where('granted_at', '>=', now()->subDays($days));
    }

    public function getGrantorName(): string
    {
        return $this->surat->uploader->full_name ?? 'Unknown';
    }

    public function isGrantedRecently(int $hours = 24): bool
    {
        return $this->granted_at->diffInHours(now()) <= $hours;
    }

    public function getAccessDuration(): string
    {
        return $this->granted_at->diffForHumans();
    }

    public static function grantAccess(int $suratId, int $userId, ?string $notes = null): self
    {
        return self::updateOrCreate(
            [
                'surat_id' => $suratId,
                'user_id' => $userId,
            ],
            [
                'granted_at' => now(),
                'notes' => $notes,
            ]
        );
    }

    public static function revokeAccess(int $suratId, int $userId): int
    {
        return self::where('surat_id', $suratId)
                  ->where('user_id', $userId)
                  ->delete();
    }

    public static function getUserAccessibleSurat(int $userId): Collection
    {
        return self::where('user_id', $userId)
                  ->with(['surat' => function($query) {
                      $query->where('is_private', true);
                  }])
                  ->get();
    }

    public static function getSuratAccessList(int $suratId): Collection
    {
        return self::where('surat_id', $suratId)
                  ->with('user')
                  ->orderBy('granted_at', 'desc')
                  ->get();
    }
}