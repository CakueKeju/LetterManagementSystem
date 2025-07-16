<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratAccess extends Model
{
    use HasFactory;

    protected $table = 'surat_access';

    public $timestamps = false;

    protected $fillable = [
        'surat_id',
        'user_id',
        'granted_at',
        'notes',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    // Relationships
    public function surat()
    {
        return $this->belongsTo(Surat::class, 'surat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantor()
    {
        // Grantor adalah pemilik surat (uploaded_by)
        return $this->hasOneThrough(
            User::class,
            Surat::class,
            'id', // Foreign key on surat table
            'id', // Foreign key on users table
            'surat_id', // Local key on surat_access table
            'uploaded_by' // Local key on surat table
        );
    }

    // Scopes
    public function scopeForSurat($query, $suratId)
    {
        return $query->where('surat_id', $suratId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGrantedToday($query)
    {
        return $query->whereDate('granted_at', today());
    }

    public function scopeGrantedRecently($query, $days = 7)
    {
        return $query->where('granted_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getGrantorName()
    {
        return $this->surat->uploader->full_name ?? 'Unknown';
    }

    public function isGrantedRecently($hours = 24)
    {
        return $this->granted_at->diffInHours(now()) <= $hours;
    }

    public function getAccessDuration()
    {
        return $this->granted_at->diffForHumans();
    }

    // Static methods
    public static function grantAccess($suratId, $userId, $notes = null)
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

    public static function revokeAccess($suratId, $userId)
    {
        return self::where('surat_id', $suratId)
                  ->where('user_id', $userId)
                  ->delete();
    }

    public static function getUserAccessibleSurat($userId)
    {
        return self::where('user_id', $userId)
                  ->with(['surat' => function($query) {
                      $query->where('is_private', true);
                  }])
                  ->get();
    }

    public static function getSuratAccessList($suratId)
    {
        return self::where('surat_id', $suratId)
                  ->with('user')
                  ->orderBy('granted_at', 'desc')
                  ->get();
    }
}