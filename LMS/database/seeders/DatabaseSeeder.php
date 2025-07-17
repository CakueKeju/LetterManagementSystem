<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use App\Models\JenisSurat;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default divisions
        $divisions = [
            ['kode_divisi' => 'IT', 'nama_divisi' => 'Information Technology'],
            ['kode_divisi' => 'HR', 'nama_divisi' => 'Human Resources'],
            ['kode_divisi' => 'FIN', 'nama_divisi' => 'Finance'],
            ['kode_divisi' => 'MKT', 'nama_divisi' => 'Marketing'],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }

        // Create default jenis surat
        $jenisSurat = [
            ['kode_jenis' => 'SUR', 'nama_jenis' => 'Surat Biasa', 'is_active' => true],
            ['kode_jenis' => 'SURK', 'nama_jenis' => 'Surat Keluar', 'is_active' => true],
            ['kode_jenis' => 'SURM', 'nama_jenis' => 'Surat Masuk', 'is_active' => true],
            ['kode_jenis' => 'MEMO', 'nama_jenis' => 'Memo', 'is_active' => true],
        ];

        foreach ($jenisSurat as $jenis) {
            JenisSurat::create($jenis);
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
