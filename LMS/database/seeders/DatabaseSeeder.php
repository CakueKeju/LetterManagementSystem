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
        $userNames = [
            'Matikanetannhauser',
            'Agnes Tachyon',
            'King Halo',
            'Sweep Tosho',
            'Gentildonna',
            'Hishi Akebono',
            'Mayano Top Gun',
            'Smart Falcon',
            'Ines Fujin',
            'Curren Chan',
            'T.M. Opera O',
            'Orfevre'
        ];

        $userIndex = 0;
        foreach ($createdDivisions as $division) {
            for ($i = 0; $i < 3; $i++) {
                $fullName = $userNames[$userIndex];
                // Create email by shortening long names and replacing spaces with dots
                $emailName = strtolower(str_replace(' ', '.', $fullName));
                
                // Shorten if too long
                if (strlen($emailName) > 15) {
                    $parts = explode('.', $emailName);
                    if (count($parts) > 1) {
                        // Take first name and first letter of last name
                        $emailName = $parts[0] . '.' . substr($parts[1], 0, 1);
                    } else {
                        $emailName = substr($emailName, 0, 15);
                    }
                }
                
                // Create username from email name
                $username = str_replace('.', '', $emailName);
                
                User::create([
                    'username' => $username,
                    'full_name' => $fullName,
                    'email' => $emailName . '@example.com',
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
