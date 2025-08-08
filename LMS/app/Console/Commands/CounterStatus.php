<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JenisSurat;
use Carbon\Carbon;

class CounterStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surat:counter-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show counter status - reset functionality deprecated (counters auto-reset per month)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  DEPRECATION NOTICE: Counter reset is no longer needed!');
        $this->line('');
        $this->info('The new counter system automatically handles monthly resets.');
        $this->info('Each month gets its own independent counter automatically.');
        $this->line('');
        
        // Show current month counter status instead
        $this->showCounterStatus();
        
        return 0;
    }
    
    private function showCounterStatus()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $jenisSuratList = JenisSurat::active()->get();
        
        $this->info("📊 Counter Status for {$currentMonth}:");
        $this->line('');
        
        $headers = ['ID', 'Jenis Surat', 'Current Counter', 'Next Number'];
        $rows = [];
        
        foreach ($jenisSuratList as $jenisSurat) {
            $currentCounter = $jenisSurat->getCurrentCounter($currentMonth);
            $nextNumber = $jenisSurat->peekNextCounter($currentMonth);
            
            $rows[] = [
                $jenisSurat->id,
                $jenisSurat->nama_jenis,
                $currentCounter,
                $nextNumber
            ];
        }
        
        $this->table($headers, $rows);
        
        $this->line('');
        $this->info('💡 Each month automatically starts with counter 1');
        $this->info('💡 Previous months\' counters remain unchanged');
    }
}
