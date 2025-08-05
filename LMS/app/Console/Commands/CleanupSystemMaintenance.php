<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NomorUrutLock;
use App\Models\JenisSurat;
use App\Traits\DocumentProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupSystemMaintenance extends Command
{
    use DocumentProcessor;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired locks and perform monthly counter maintenance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting LMS system maintenance cleanup...');

        // 1. Clean up expired locks
        $this->cleanupExpiredLocks();

        // 2. Clean up temporary files
        $this->cleanupTempFiles();

        // 3. Handle monthly counter resets
        $this->handleMonthlyCounterResets();

        // 4. Log cleanup completion
        $this->logMaintenanceCompletion();

        $this->info('System maintenance completed successfully.');
        return 0;
    }

    /**
     * Clean up expired nomor urut locks
     */
    private function cleanupExpiredLocks()
    {
        $this->info('Cleaning up expired locks...');

        try {
            $expiredCount = NomorUrutLock::cleanupExpiredLocks();
            
            if ($expiredCount > 0) {
                $this->info("Cleaned up {$expiredCount} expired locks.");
                Log::info("System maintenance: Cleaned up {$expiredCount} expired nomor urut locks");
            } else {
                $this->info('No expired locks found.');
            }
        } catch (\Exception $e) {
            $this->error('Error cleaning up expired locks: ' . $e->getMessage());
            Log::error('Error during lock cleanup', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clean up temporary files created by document processing
     */
    private function cleanupTempFiles()
    {
        $this->info('Cleaning up temporary files...');
        
        try {
            $deletedCount = $this->cleanupOldTempFiles();
            
            if ($deletedCount > 0) {
                $this->info("Cleaned up {$deletedCount} temporary files.");
            } else {
                $this->info('No old temporary files found.');
            }
        } catch (\Exception $e) {
            $this->error('Error cleaning up temporary files: ' . $e->getMessage());
            Log::error('Error during temp file cleanup', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle monthly counter resets for jenis surat
     */
    private function handleMonthlyCounterResets()
    {
        $this->info('Checking for monthly counter resets...');

        try {
            $currentMonth = Carbon::now()->format('Y-m');
            $resetCount = 0;

            // Get all jenis surat that need reset
            $jenisSemuaSurat = JenisSurat::where(function($query) use ($currentMonth) {
                $query->whereNull('last_reset_month')
                      ->orWhere('last_reset_month', '!=', $currentMonth);
            })->get();

            foreach ($jenisSemuaSurat as $jenisSurat) {
                $oldCounter = $jenisSurat->counter;
                $oldResetMonth = $jenisSurat->last_reset_month;

                // Reset counter for new month
                $jenisSurat->counter = 0;
                $jenisSurat->last_reset_month = $currentMonth;
                $jenisSurat->save();

                $resetCount++;
                
                Log::info("Monthly counter reset", [
                    'jenis_surat_id' => $jenisSurat->id,
                    'jenis_surat_nama' => $jenisSurat->nama,
                    'old_counter' => $oldCounter,
                    'old_reset_month' => $oldResetMonth,
                    'new_reset_month' => $currentMonth
                ]);
            }

            if ($resetCount > 0) {
                $this->info("Reset counters for {$resetCount} jenis surat to month {$currentMonth}.");
                Log::info("System maintenance: Reset {$resetCount} counters for month {$currentMonth}");
            } else {
                $this->info('All counters are already up to date for current month.');
            }
        } catch (\Exception $e) {
            $this->error('Error handling monthly counter resets: ' . $e->getMessage());
            Log::error('Error during counter reset maintenance', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log maintenance completion
     */
    private function logMaintenanceCompletion()
    {
        $currentStats = [
            'active_locks' => NomorUrutLock::where('expires_at', '>', Carbon::now())->count(),
            'current_month' => Carbon::now()->format('Y-m'),
            'jenis_surat_count' => JenisSurat::count(),
            'maintenance_time' => Carbon::now()->toDateTimeString()
        ];

        Log::info('System maintenance completed', $currentStats);
    }
}
