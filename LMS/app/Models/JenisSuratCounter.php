<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisSuratCounter extends Model
{
    use HasFactory;

    protected $table = 'counters';

    protected $fillable = [
        'jenis_surat_id',
        'month_year',
        'counter',
    ];

    protected $casts = [
        'jenis_surat_id' => 'integer',
        'counter' => 'integer',
    ];

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    /**
     * Get or create counter for specific month
     */
    public static function getOrCreateCounter($jenisSuratId, $monthYear)
    {
        return static::firstOrCreate([
            'jenis_surat_id' => $jenisSuratId,
            'month_year' => $monthYear
        ], [
            'counter' => 0
        ]);
    }

    /**
     * Increment counter for specific month and return new value
     */
    public static function incrementForMonth($jenisSuratId, $monthYear)
    {
        return \DB::transaction(function () use ($jenisSuratId, $monthYear) {
            $counter = static::lockForUpdate()
                ->where('jenis_surat_id', $jenisSuratId)
                ->where('month_year', $monthYear)
                ->first();

            if (!$counter) {
                $counter = static::create([
                    'jenis_surat_id' => $jenisSuratId,
                    'month_year' => $monthYear,
                    'counter' => 1
                ]);
                return 1;
            }

            $newCounter = $counter->counter + 1;
            $counter->update(['counter' => $newCounter]);
            
            \Log::info("Incremented counter for JenisSurat {$jenisSuratId} month {$monthYear} to {$newCounter}");
            
            return $newCounter;
        });
    }

    /**
     * Peek next counter for specific month without incrementing
     */
    public static function peekNextForMonth($jenisSuratId, $monthYear)
    {
        $counter = static::where('jenis_surat_id', $jenisSuratId)
            ->where('month_year', $monthYear)
            ->first();

        return $counter ? $counter->counter + 1 : 1;
    }

    /**
     * Get current counter for specific month
     */
    public static function getCurrentForMonth($jenisSuratId, $monthYear)
    {
        $counter = static::where('jenis_surat_id', $jenisSuratId)
            ->where('month_year', $monthYear)
            ->first();

        return $counter ? $counter->counter : 0;
    }
}
