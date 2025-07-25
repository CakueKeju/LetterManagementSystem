<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [
        'nomor_urut',
        'nomor_surat',
        'divisi_id',
        'jenis_surat_id',
        'perihal',
        'tanggal_surat',
        'tanggal_diterima',
        'file_path',
        'file_size',
        'mime_type',
        'is_private',
        'uploaded_by',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'tanggal_surat' => 'date',
        'tanggal_diterima' => 'date',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function jenisSurat()
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function accesses()
    {
        return $this->hasMany(SuratAccess::class, 'surat_id');
    }
}