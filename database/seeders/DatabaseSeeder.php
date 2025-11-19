<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User Utama
        User::create([
            'name' => 'Admin1',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Tayo Coba',
            'email' => 'tayo1@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'anggota',
            'status' => 'active',
            'member_card_id' => 'LIB-001-A',
        ]);

        // 2. Kategori
        $this->call(CategorySeeder::class);

        // 3. Data Buku (membutuhkan Kategori)
        Book::factory(50)->create();
    }
}
