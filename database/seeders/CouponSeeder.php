<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME50',
                'discount_type' => 'percentage',
                'discount_value' => 50,
                'description' => '50% off for new users',
                'max_uses' => 100,
                'uses_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'SAVE20',
                'discount_type' => 'fixed',
                'discount_value' => 20,
                'description' => '$20 off any course',
                'max_uses' => null,
                'uses_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => null,
            ],
            [
                'code' => 'EARLYBIRD',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'description' => '30% early bird discount',
                'max_uses' => 50,
                'uses_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
            ],
            [
                'code' => 'SUMMER2025',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'description' => 'Summer sale - 25% off',
                'max_uses' => 200,
                'uses_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
            ],
            [
                'code' => 'FREECOURSE',
                'discount_type' => 'percentage',
                'discount_value' => 100,
                'description' => 'Free course coupon - 100% off',
                'max_uses' => 10,
                'uses_count' => 0,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addWeeks(2),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }

        $this->command->info('Coupon seeder completed successfully!');
    }
}
