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
        \App\Models\User::truncate();
        \App\Models\JenisSurat::truncate();
        \App\Models\Division::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create divisions
        $divisions = [
            ['kode_divisi' => 'DIV1', 'nama_divisi' => 'Division One'],
            ['kode_divisi' => 'DIV2', 'nama_divisi' => 'Division Two'],
            ['kode_divisi' => 'DIV3', 'nama_divisi' => 'Division Three'],
            ['kode_divisi' => 'DIV4', 'nama_divisi' => 'Division Four'],
        ];
        
        $createdDivisions = [];
        foreach ($divisions as $div) {
            $division = \App\Models\Division::create($div);
            $createdDivisions[] = $division;
        }

        // Create jenis surat for each division
        foreach ($createdDivisions as $index => $division) {
            for ($i = 1; $i <= 4; $i++) {
                \App\Models\JenisSurat::create([
                    'divisi_id' => $division->id,
                    'kode_jenis' => 'JENIS' . $i,
                    'nama_jenis' => 'Jenis Surat ' . $i,
                    'deskripsi' => 'Jenis surat ' . $i . ' untuk ' . $division->nama_divisi,
                    'is_active' => true,
                ]);
            }
        }

        // Create admin user
        User::create([
            'username' => 'admin',
            'full_name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'divisi_id' => $createdDivisions[0]->id,
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Create dummy users (3 for each division)
        $users = [
            ['full_name' => 'Matikanetannhauser', 'username' => 'matikanetannhauser', 'email' => 'mambo@example.com'],
            ['full_name' => 'Agnes Tachyon', 'username' => 'agnestachyon', 'email' => 'tachy@example.com'],
            ['full_name' => 'King Halo', 'username' => 'kinghalo', 'email' => 'kinghalo@example.com'],
            ['full_name' => 'Sweep Tosho', 'username' => 'sweeptosho', 'email' => 'sweeptosho@example.com'],
            ['full_name' => 'Gentildonna', 'username' => 'gentildonna', 'email' => 'gentildonna@example.com'],
            ['full_name' => 'Hishi Akebono', 'username' => 'hishiakebono', 'email' => 'hishiakebono@example.com'],
            ['full_name' => 'Mayano Top Gun', 'username' => 'mayanotopgun', 'email' => 'mayanotopgun@example.com'],
            ['full_name' => 'Smart Falcon', 'username' => 'smartfalcon', 'email' => 'smartfalcon@example.com'],
            ['full_name' => 'Ines Fujin', 'username' => 'inesfujin', 'email' => 'inesfujin@example.com'],
            ['full_name' => 'Curren Chan', 'username' => 'currenchan', 'email' => 'currenchan@example.com'],
            ['full_name' => 'T.M. Opera O', 'username' => 'tmoperao', 'email' => 'TMoperao@example.com'],
            ['full_name' => 'Orfevre', 'username' => 'orfevre', 'email' => 'orfevre@example.com']
        ];

        $userIndex = 0;
        foreach ($createdDivisions as $division) {
            for ($i = 0; $i < 3; $i++) {
                $userData = $users[$userIndex];
                
                User::create([
                    'username' => $userData['username'],
                    'full_name' => $userData['full_name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password123'),
                    'divisi_id' => $division->id,
                    'is_active' => true,
                    'is_admin' => false,
                ]);
                
                $userIndex++;
            }
        }
    }
}
