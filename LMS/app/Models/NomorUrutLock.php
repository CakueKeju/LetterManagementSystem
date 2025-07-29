<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomorUrutLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'divisi_id',
        'jenis_surat_id',
        'nomor_urut',
        'user_id',
        'locked_until'
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'jenis_surat_id' => 'integer',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function jenisSurat()
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 