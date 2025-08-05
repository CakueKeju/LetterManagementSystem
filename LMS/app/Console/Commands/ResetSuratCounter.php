<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JenisSurat;
use Carbon\Carbon;

class ResetSuratCounter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surat:reset-counter {--jenis-id= : Reset specific jenis surat by ID} {--all : Reset all jenis surat counters} {--force : Force reset even if same month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset surat counter for jenis surat (monthly reset)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jenisId = $this->option('jenis-id');
        $resetAll = $this->option('all');
        $force = $this->option('force');
        
        if ($jenisId) {
            // Reset specific jenis surat
            $jenisSurat = JenisSurat::find($jenisId);
            if (!$jenisSurat) {
                $this->error("Jenis surat with ID {$jenisId} not found!");
                return 1;
            }
            
            $this->resetJenisSurat($jenisSurat, $force);
            $this->info("Counter reset for jenis surat: {$jenisSurat->nama_jenis} (ID: {$jenisId})");
            
        } elseif ($resetAll) {
            // Reset all jenis surat
            $jenisSuratList = JenisSurat::active()->get();
            $count = 0;
            
            $this->info("Resetting counters for all active jenis surat...");
            
            foreach ($jenisSuratList as $jenisSurat) {
                $this->resetJenisSurat($jenisSurat, $force);
                $count++;
            }
            
            $this->info("Successfully reset counters for {$count} jenis surat.");
            
        } else {
            // Auto reset based on month change
            $this->autoResetCounters();
        }
        
        return 0;
    }
    
    private function resetJenisSurat(JenisSurat $jenisSurat, bool $force = false)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        if (!$force) {
            $currentCounter = $jenisSurat->getCurrentCounter($currentMonth);
            if ($currentCounter === 0) {
                $this->warn("Counter for {$jenisSurat->nama_jenis} is already 0 for month {$currentMonth}. Use --force to reset anyway.");
                return;
            }
        }
        
        $oldCounter = $jenisSurat->getCurrentCounter($currentMonth);
        $jenisSurat->resetCounter($currentMonth);
        
        $this->line("Reset counter for {$jenisSurat->nama_jenis} (month {$currentMonth}): {$oldCounter} → 0");
    }
    
    private function autoResetCounters()
    {
        $jenisSuratList = JenisSurat::active()->get();
        $currentMonth = Carbon::now()->format('Y-m');
        $resetCount = 0;
        
        foreach ($jenisSuratList as $jenisSurat) {
            $currentCounter = $jenisSurat->getCurrentCounter($currentMonth);
            if ($currentCounter > 0) {
                $jenisSurat->resetCounter($currentMonth);
                $this->line("Auto-reset counter for {$jenisSurat->nama_jenis} (month {$currentMonth}): {$currentCounter} → 0");
                $resetCount++;
            }
        }
        
        if ($resetCount > 0) {
            $this->info("Auto-reset completed for {$resetCount} jenis surat.");
        } else {
            $this->info("No counters need to be reset this month ({$currentMonth}).");
        }
    }
}
