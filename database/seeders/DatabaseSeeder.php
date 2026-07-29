<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Polda',
            'email' => 'admin@poldasumbar.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Satker Padang',
            'email' => 'satker.padang@poldasumbar.go.id',
            'password' => Hash::make('password'),
            'role' => 'satker',
        ]);
    }
}