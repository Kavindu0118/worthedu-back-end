<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a learner account
        User::updateOrCreate(
            ['username' => 'learner'],
            [
                'name' => 'nimal',
                'email' => 'nimal@gmial.com',
                'password' => bcrypt('nimal23'),
                'role' => 'learner',
            ]
        );

        // Create an instructor account
        User::updateOrCreate(
            ['username' => 'instructor'],
            [
                'name' => 'indra',
                'email' => 'indra@example.com',
                'password' => bcrypt('instructor23'),
                'role' => 'instructor',
            ]
        );
    }
}
