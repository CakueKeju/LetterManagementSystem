<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NomorUrutLock;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateLocks extends Command
{
    // ================================= COMMAND SETTINGS =================================
    
    protected $signature = 'locks:cleanup-duplicates';
    protected $description = 'Bersihin lock nomor urut yang duplikat';

    // ================================= FUNGSI UTAMA =================================

    public function handle()
    {
        $this->info('Mulai bersihin lock duplikat...');
        
        // cari user yang punya multiple locks di divisi yang sama
        $duplicates = DB::table('nomor_urut_locks')
            ->select('user_id', 'divisi_id', DB::raw('COUNT(*) as lock_count'))
            ->groupBy('user_id', 'divisi_id')
            ->having('lock_count', '>', 1)
            ->get();
            
        if ($duplicates->isEmpty()) {
            $this->info('No duplicate locks found.');
            return 0;
        }
        
        $totalCleaned = 0;
        
        foreach ($duplicates as $duplicate) {
            $this->info("Found {$duplicate->lock_count} locks for user {$duplicate->user_id} in division {$duplicate->divisi_id}");
            
            // Keep only the most recent lock for each user in each division
            $locksToDelete = NomorUrutLock::where('user_id', $duplicate->user_id)
                ->where('divisi_id', $duplicate->divisi_id)
                ->orderBy('created_at', 'desc')
                ->skip(1) // Skip the most recent one
                ->get();
                
            foreach ($locksToDelete as $lock) {
                $this->line("  Deleting lock: Division {$lock->divisi_id}, Jenis Surat {$lock->jenis_surat_id}, Nomor {$lock->nomor_urut}");
                $lock->delete();
                $totalCleaned++;
            }
        }
        
        $this->info("Cleanup completed. Removed {$totalCleaned} duplicate locks.");
        
        return 0;
    }
}
