<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'kode_divisi',
        'nama_divisi',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'divisi_id');
    }

    public function surat()
    {
        return $this->hasMany(Surat::class, 'divisi_id');
    }

    public function jenisSurat()
    {
        return $this->hasMany(JenisSurat::class, 'divisi_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query;
    }

    // Helper methods
    public function getNextNomorUrut()
    {
        $lastSurat = $this->surat()
            ->orderBy('nomor_urut', 'desc')
            ->first();

        return $lastSurat ? $lastSurat->nomor_urut + 1 : 1;
    }
}