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
        'counter',
        'last_reset_month',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'counter' => 'integer',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @deprecated Use peekNextCounter() for preview and incrementCounter() for actual increment
     * This method immediately increments the counter and should not be used
     */
    public function getNextCounter(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        return DB::transaction(function () use ($currentMonth) {
            $fresh = $this->lockForUpdate()->find($this->id);
            
            if ($fresh->last_reset_month !== $currentMonth || is_null($fresh->last_reset_month)) {
                Log::info("Resetting counter for JenisSurat {$this->id} from month {$fresh->last_reset_month} to {$currentMonth}");
                
                $fresh->update([
                    'counter' => 1,
                    'last_reset_month' => $currentMonth
                ]);
                
                return 1;
            }
            
            $newCounter = $fresh->counter + 1;
            $fresh->update(['counter' => $newCounter]);
            
            Log::info("Incrementing counter for JenisSurat {$this->id} to {$newCounter}");
            
            return $newCounter;
        });
    }

    /**
     * Get the next counter WITHOUT incrementing it (for preview/lock purposes)
     */
    public function peekNextCounter(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        if ($this->last_reset_month !== $currentMonth || is_null($this->last_reset_month)) {
            return 1; // First number of new month
        }
        
        return $this->counter + 1; // Next number
    }

    /**
     * Actually increment the counter (called only on final submit)
     */
    public function incrementCounter(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        return DB::transaction(function () use ($currentMonth) {
            $fresh = $this->lockForUpdate()->find($this->id);
            
            if ($fresh->last_reset_month !== $currentMonth || is_null($fresh->last_reset_month)) {
                Log::info("Resetting and setting counter for JenisSurat {$this->id} from month {$fresh->last_reset_month} to {$currentMonth}");
                
                $fresh->update([
                    'counter' => 1,
                    'last_reset_month' => $currentMonth
                ]);
                
                return 1;
            }
            
            $newCounter = $fresh->counter + 1;
            $fresh->update(['counter' => $newCounter]);
            
            Log::info("Incrementing counter for JenisSurat {$this->id} to {$newCounter}");
            
            return $newCounter;
        });
    }

    public function resetCounter(): void
    {
        $currentMonth = Carbon::now()->format('Y-m');
        Log::info("Manual reset counter for JenisSurat {$this->id} to month {$currentMonth}");
        
        $this->update([
            'counter' => 0,
            'last_reset_month' => $currentMonth
        ]);
    }

    public function getCurrentCounter(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        if ($this->last_reset_month !== $currentMonth || is_null($this->last_reset_month)) {
            return 0;
        }
        
        return $this->counter;
    }
    
    public static function resetAllCountersForNewMonth(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        $updated = static::where('last_reset_month', '!=', $currentMonth)
            ->orWhereNull('last_reset_month')
            ->update([
                'counter' => 0,
                'last_reset_month' => $currentMonth
            ]);
            
        Log::info("Reset {$updated} JenisSurat counters for month {$currentMonth}");
        
        return $updated;
    }
}