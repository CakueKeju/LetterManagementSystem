<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisSuratCounter extends Model
{
    use HasFactory;

    protected $table = 'counters';

    // ================================= FILLABLE =================================
    
    protected $fillable = [
        'jenis_surat_id',
        'month_year',
        'counter',
    ];

    protected $casts = [
        'jenis_surat_id' => 'integer',
        'counter' => 'integer',
    ];

    // ================================= RELASI =================================

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    // ================================= STATIC METHODS =================================

    // get atau create counter untuk bulan tertentu
    public static function getOrCreateCounter($jenisSuratId, $monthYear)
    {
        return static::firstOrCreate([
            'jenis_surat_id' => $jenisSuratId,
            'month_year' => $monthYear
        ], [
            'counter' => 0
        ]);
    }

    // increment counter untuk bulan tertentu dan return nilai baru
    // Counter menyimpan "nomor terakhir yang sudah digunakan", bukan "nomor berikutnya"
    // fungsi ini juga sync dengan data surat untuk prevent inconsistency
    public static function incrementForMonth($jenisSuratId, $monthYear)
    {
        return \DB::transaction(function () use ($jenisSuratId, $monthYear) {
            $counter = static::lockForUpdate()
                ->where('jenis_surat_id', $jenisSuratId)
                ->where('month_year', $monthYear)
                ->first();

            // cek nomor urut max dari tabel surat (nomor terakhir yang benar-benar digunakan)
            [$year, $month] = explode('-', $monthYear);
            $maxNomorUrut = \App\Models\Surat::where('jenis_surat_id', $jenisSuratId)
                ->whereYear('tanggal_surat', $year)
                ->whereMonth('tanggal_surat', $month)
                ->max('nomor_urut');

            if (!$counter) {
                // Create new counter, set to the number we're about to use
                $nextNumber = ($maxNomorUrut ?: 0) + 1;
                $counter = static::create([
                    'jenis_surat_id' => $jenisSuratId,
                    'month_year' => $monthYear,
                    'counter' => $nextNumber  // Store the number we're using
                ]);
                
                \Log::info("Created new counter for JenisSurat {$jenisSuratId} month {$monthYear}: using {$nextNumber}");
                return $nextNumber;
            }

            // Determine the actual last used number from reliable sources
            $actualLastUsed = max($counter->counter, $maxNomorUrut ?: 0);
                // Update counter to store the number just used (actualLastUsed + 1 is the next, actualLastUsed is the last used)
                $counter->update(['counter' => $actualLastUsed]);
                \Log::info("Incremented counter for JenisSurat {$jenisSuratId} month {$monthYear}:", [
                    'old_counter' => $counter->counter,
                    'actual_max_nomor_urut' => $maxNomorUrut,
                    'actual_last_used' => $actualLastUsed,
                    'counter_updated_to' => $actualLastUsed
                ]);
                return $actualLastUsed;
        });
    }

    /**
     * Peek next counter for specific month without incrementing
     * This method checks both counter table AND actual surat data to avoid inconsistency
     */
    public static function peekNextForMonth($jenisSuratId, $monthYear)
    {
        // Get counter from counter table
        $counter = static::where('jenis_surat_id', $jenisSuratId)
            ->where('month_year', $monthYear)
            ->first();

        $counterValue = $counter ? $counter->counter : 0;
        
        // Also check actual surat data to prevent inconsistency
        [$year, $month] = explode('-', $monthYear);
        $maxNomorUrut = \App\Models\Surat::where('jenis_surat_id', $jenisSuratId)
            ->whereYear('tanggal_surat', $year)
            ->whereMonth('tanggal_surat', $month)
            ->max('nomor_urut');
            
        // Use the higher value to prevent conflicts
        $actualMax = max($counterValue, $maxNomorUrut ?: 0);
        
        \Log::info("Peek counter comparison for JenisSurat {$jenisSuratId} month {$monthYear}:", [
            'counter_table_value' => $counterValue,
            'actual_max_nomor_urut' => $maxNomorUrut,
            'using_value' => $actualMax,
            'next_value' => $actualMax + 1
        ]);
        
        return $actualMax + 1;
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
