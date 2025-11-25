<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class LearnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Determine which user to attach the learner record to.
        // Priority: env LEARNER_USER_ID -> user with username 'learner' -> first user with role 'learner'
        $userId = env('LEARNER_USER_ID');

        if (! $userId) {
            $user = User::where('username', 'learner')->first();
            if (! $user) {
                $user = User::where('role', 'learner')->first();
            }
            $userId = $user?->id;
        }

        if (! $userId) {
            $this->command->info('No existing learner user found to attach sample learner record.');
            return;
        }

        // Insert or update the learners table record
        DB::table('learners')->updateOrInsert(
            ['user_id' => $userId],
            [
                'first_name' => 'Sample',
                'last_name' => 'Learner',
                'date_of_birth' => '1995-01-15',
                'address' => '123 Example St, Sample City',
                'highest_qualification' => 'degree',
                'mobile_number' => '0712345678',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Sample learner record created for user_id: ' . $userId);
    }
}
