<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NomorUrutLock;
use App\Models\JenisSurat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SystemMaintenance extends Command
{
    protected $signature = 'lms:maintenance {--force : Force cleanup without confirmation}';
    protected $description = 'Automatic system maintenance: cleanup expired locks and reset monthly counters';

    public function handle(): int
    {
        $this->info('Starting LMS system maintenance...');

        $this->cleanupExpiredLocks();
        $this->resetMonthlyCounters();
        $this->logCompletion();

        $this->info('System maintenance completed successfully.');
        return self::SUCCESS;
    }

    private function cleanupExpiredLocks(): void
    {
        $this->info('Cleaning up expired locks...');

        try {
            $expiredCount = NomorUrutLock::cleanupExpiredLocks();
            
            if ($expiredCount > 0) {
                $this->info("Cleaned up {$expiredCount} expired locks.");
                Log::info("System maintenance: cleaned up {$expiredCount} expired locks");
            } else {
                $this->info('No expired locks found.');
            }
        } catch (\Exception $e) {
            $this->error('Error cleaning up expired locks: ' . $e->getMessage());
            Log::error('Lock cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    private function resetMonthlyCounters(): void
    {
        try {
            $currentMonth = Carbon::now()->format('Y-m');
            
            // With new counter system, monthly resets are handled automatically
            // when first letter is created for a new month. No manual reset needed.
            Log::info("System maintenance: Monthly counter resets are now handled automatically by new counter system for month {$currentMonth}");
            
        } catch (\Exception $e) {
            Log::error('Counter reset check failed', ['error' => $e->getMessage()]);
        }
    }

    private function logCompletion(): void
    {
        Log::info('System maintenance completed', [
            'active_locks' => NomorUrutLock::where('locked_until', '>', Carbon::now())->count(),
            'current_month' => Carbon::now()->format('Y-m'),
            'jenis_surat_count' => JenisSurat::count(),
            'completed_at' => Carbon::now()->toDateTimeString()
        ]);
    }
}
