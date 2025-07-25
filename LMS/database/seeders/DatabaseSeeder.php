<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use App\Models\JenisSurat;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\JenisSurat::truncate();
        \App\Models\Division::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $divisions = [
            ['kode_divisi' => 'OPS', 'nama_divisi' => 'Operasional'],
            ['kode_divisi' => 'HRD', 'nama_divisi' => 'HRD'],
            ['kode_divisi' => 'KEU', 'nama_divisi' => 'Keuangan'],
        ];
        foreach ($divisions as $div) {
            $division = \App\Models\Division::create($div);
            \App\Models\JenisSurat::create([
                'divisi_id' => $division->id,
                'kode_jenis' => 'JENIS1',
                'nama_jenis' => 'Jenis Surat 1',
                'deskripsi' => 'Jenis surat 1 untuk ' . $division->nama_divisi,
                'is_active' => true,
            ]);
            \App\Models\JenisSurat::create([
                'divisi_id' => $division->id,
                'kode_jenis' => 'JENIS2',
                'nama_jenis' => 'Jenis Surat 2',
                'deskripsi' => 'Jenis surat 2 untuk ' . $division->nama_divisi,
                'is_active' => true,
            ]);
            \App\Models\JenisSurat::create([
                'divisi_id' => $division->id,
                'kode_jenis' => 'JENIS3',
                'nama_jenis' => 'Jenis Surat 3',
                'deskripsi' => 'Jenis surat 3 untuk ' . $division->nama_divisi,
                'is_active' => true,
            ]);
        }

        // Create admin user
        User::create([
            'username' => 'admin',
            'full_name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'divisi_id' => 1, // IT division
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Create regular user
        User::create([
            'username' => 'user',
            'full_name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'divisi_id' => 1, // IT division
            'is_active' => true,
            'is_admin' => false,
        ]);
    }
}
