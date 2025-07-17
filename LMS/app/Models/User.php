<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function division()
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function uploadedSurat()
    {
        return $this->hasMany(Surat::class, 'uploaded_by');
    }

    public function accessibleSurat()
    {
        return $this->belongsToMany(Surat::class, 'surat_access', 'user_id', 'surat_id')
                    ->withPivot('granted_at', 'notes')
                    ->withTimestamps();
    }

    public function suratAccess()
    {
        return $this->hasMany(SuratAccess::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    // Helper methods
    public function canAccessSurat(Surat $surat)
    {
        // Admin can access all surat
        if ($this->is_admin) {
            return true;
        }

        // Jika surat public dan user dalam divisi yang sama
        if (!$surat->is_private && $this->divisi_id == $surat->divisi_id) {
            return true;
        }

        // Jika user adalah uploader
        if ($surat->uploaded_by == $this->id) {
            return true;
        }

        // Jika user diberi akses khusus untuk surat private
        if ($surat->is_private && SuratAccess::where('surat_id', $surat->id)->where('user_id', $this->id)->exists()) {
            return true;
        }

        return false;
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }
}