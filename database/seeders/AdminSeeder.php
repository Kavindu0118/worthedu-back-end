<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'New Admin User',
            'username' => 'newadmin',
            'email' => 'newadmin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);
    }
}