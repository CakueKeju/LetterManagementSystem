<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ================================= FILLABLE =================================
    
    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'divisi_id',
        'is_active',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'divisi_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ================================= RELASI =================================

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function uploadedSurat(): HasMany
    {
        return $this->hasMany(Surat::class, 'uploaded_by');
    }

    public function accessibleSurat(): BelongsToMany
    {
        return $this->belongsToMany(Surat::class, 'surat_access', 'user_id', 'surat_id')
                    ->withPivot('granted_at', 'notes')
                    ->withTimestamps();
    }

    public function suratAccess(): HasMany
    {
        return $this->hasMany(SuratAccess::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByDivisi(Builder $query, int $divisiId): Builder
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function canAccessSurat(Surat $surat): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if (!$surat->is_private && $this->divisi_id === $surat->divisi_id) {
            return true;
        }

        if ($surat->uploaded_by === $this->id) {
            return true;
        }

        if ($surat->is_private && SuratAccess::where('surat_id', $surat->id)->where('user_id', $this->id)->exists()) {
            return true;
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}