<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NomorUrutLock extends Model
{
    use HasFactory;

    protected $table = 'nomor_urut_locks';

    protected $fillable = [
        'divisi_id',
        'jenis_surat_id',
        'nomor_urut',
        'user_id',
        'locked_until',
    ];

    protected $dates = [
        'locked_until',
        'created_at',
        'updated_at',
    ];
} 