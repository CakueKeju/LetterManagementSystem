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

    // ================================= FILLABLE =================================
    
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

    // ================================= RELASI =================================

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

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'surat_id');
    }

    /**
     * Get nomor surat with Roman numeral month display
     * 
     * @return string
     */
    public function getNomorSuratDisplayAttribute(): string
    {
        // Convert month number to Roman numeral in display
        $nomorSurat = $this->nomor_surat;
        
        if (preg_match('/^(\d{3})\/([^\/]+)\/([^\/]+)\/INTENS\/(\d{1,2})\/(\d{4})$/', $nomorSurat, $matches)) {
            $nomorUrut = $matches[1];
            $divisi = $matches[2];
            $jenis = $matches[3];
            $month = (int)$matches[4];
            $year = $matches[5];
            
            $romanMonth = $this->monthToRoman($month);
            
            return sprintf('%s/%s/%s/INTENS/%s/%s', $nomorUrut, $divisi, $jenis, $romanMonth, $year);
        }
        
        return $nomorSurat; // Return original if pattern doesn't match
    }

    /**
     * Convert month number to Roman numeral
     * 
     * @param int $month Month number (1-12)
     * @return string Roman numeral representation
     */
    private function monthToRoman($month): string
    {
        $romanNumerals = [
            1  => 'I',
            2  => 'II', 
            3  => 'III',
            4  => 'IV',
            5  => 'V',
            6  => 'VI',
            7  => 'VII',
            8  => 'VIII',
            9  => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        
        return $romanNumerals[$month] ?? (string)$month;
    }
}