<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Division extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'kode_divisi',
        'nama_divisi',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'divisi_id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'divisi_id');
    }

    public function jenisSurat(): HasMany
    {
        return $this->hasMany(JenisSurat::class, 'divisi_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query;
    }

    public function getNextNomorUrut(): int
    {
        $lastSurat = $this->surat()
            ->orderBy('nomor_urut', 'desc')
            ->first();

        return $lastSurat ? $lastSurat->nomor_urut + 1 : 1;
    }
}