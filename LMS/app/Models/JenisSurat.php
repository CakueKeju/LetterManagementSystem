<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JenisSurat extends Model
{
    use HasFactory;

    protected $table = 'jenis_surat';

    protected $fillable = [
        'divisi_id',
        'kode_jenis',
        'nama_jenis',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'divisi_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'jenis_surat_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(JenisSuratCounter::class, 'jenis_surat_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the next counter WITHOUT incrementing it (for preview/lock purposes)
     * @param string|null $monthYear Format: YYYY-MM, defaults to current month
     */
    public function peekNextCounter($monthYear = null): int
    {
        if (!$monthYear) {
            $monthYear = Carbon::now()->format('Y-m');
        }
        
        return \App\Models\JenisSuratCounter::peekNextForMonth($this->id, $monthYear);
    }

    /**
     * Actually increment the counter (called only on final submit)
     * @param string|null $monthYear Format: YYYY-MM, defaults to current month
     */
    public function incrementCounter($monthYear = null): int
    {
        if (!$monthYear) {
            $monthYear = Carbon::now()->format('Y-m');
        }
        
        return \App\Models\JenisSuratCounter::incrementForMonth($this->id, $monthYear);
    }

    /**
     * Get current counter for specific month
     * @param string|null $monthYear Format: YYYY-MM, defaults to current month
     */
    public function getCurrentCounter($monthYear = null): int
    {
        if (!$monthYear) {
            $monthYear = Carbon::now()->format('Y-m');
        }
        
        return \App\Models\JenisSuratCounter::getCurrentForMonth($this->id, $monthYear);
    }
}