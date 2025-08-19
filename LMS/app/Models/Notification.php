<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'surat_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ================================= RELATIONSHIPS =================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    // ================================= SCOPES =================================

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // ================================= METHODS =================================

    public function markAsRead(): bool
    {
        if (!$this->is_read) {
            return $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
        return true;
    }

    public function markAsUnread(): bool
    {
        if ($this->is_read) {
            return $this->update([
                'is_read' => false,
                'read_at' => null,
            ]);
        }
        return true;
    }

    // ================================= STATIC METHODS =================================

    public static function createNotification(
        int $userId,
        int $suratId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'surat_id' => $suratId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function markAllAsReadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->update([
                      'is_read' => true,
                      'read_at' => now(),
                  ]);
    }

    public static function getUnreadCountForUser(int $userId): int
    {
        return self::unread()->forUser($userId)->count();
    }

    public static function cleanupOldNotifications(int $days = 90): int
    {
        return self::where('created_at', '<', Carbon::now()->subDays($days))->delete();
    }
}
