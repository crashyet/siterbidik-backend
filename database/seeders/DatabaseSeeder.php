<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create(
          [ 'nisn' => '2234567890', 'name' => 'Adhitya Putra', 'class' => '12 IPA 1', 'role' => 'siswa', 'password' => 'password123', 'is_first_login' => true,],
        );
    }
}
