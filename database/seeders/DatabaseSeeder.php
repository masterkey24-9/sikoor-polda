<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Polda',
            'email' => 'admin@poldasumbar.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Satker Padang',
            'email' => 'satker.padang@poldasumbar.go.id',
            'password' => bcrypt('password'),
            'role' => 'satker',
        ]);
    }
}