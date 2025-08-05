<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'file_size' => 'integer',
        'nomor_urut' => 'integer',
        'divisi_id' => 'integer',
        'jenis_surat_id' => 'integer',
        'uploaded_by' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(SuratAccess::class, 'surat_id');
    }
}