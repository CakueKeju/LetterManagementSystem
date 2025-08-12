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
            ['kode_divisi' => 'DIVISI1', 'nama_divisi' => 'Divisi 1'],
            ['kode_divisi' => 'DIVISI2', 'nama_divisi' => 'Divisi 2'],
            ['kode_divisi' => 'DIVISI3', 'nama_divisi' => 'Divisi 3'],
        ];
        
        $createdDivisions = [];
        foreach ($divisions as $div) {
            $division = \App\Models\Division::create($div);
            $createdDivisions[] = $division;
        }

        // Create jenis surat for each division
        foreach ($createdDivisions as $division) {
            for ($i = 1; $i <= 3; $i++) {
                \App\Models\JenisSurat::create([
                    'divisi_id' => $division->id,
                    'kode_jenis' => 'JENIS' . $i,
                    'nama_jenis' => 'Jenis ' . $i,
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
            'password' => Hash::make('password'),
            'divisi_id' => $createdDivisions[0]->id,
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Create regular users (one for each division)
        foreach ($createdDivisions as $index => $division) {
            $userNumber = $index + 1;
            User::create([
                'username' => 'user' . $userNumber,
                'full_name' => 'User ' . $userNumber,
                'email' => 'user' . $userNumber . '@example.com',
                'password' => Hash::make('password'),
                'divisi_id' => $division->id,
                'is_active' => true,
                'is_admin' => false,
            ]);
        }
    }
}
