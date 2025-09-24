<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Plan',
                'description' => 'Perfect for beginners looking to start their investment journey',
                'min_deposit' => 100.00,
                'max_deposit' => 1000.00,
                'profit_rate' => 5.0,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Silver Plan',
                'description' => 'Great for intermediate investors seeking steady returns',
                'min_deposit' => 1000.00,
                'max_deposit' => 5000.00,
                'profit_rate' => 8.0,
                'duration_days' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Gold Plan',
                'description' => 'Premium plan for serious investors with higher returns',
                'min_deposit' => 5000.00,
                'max_deposit' => 25000.00,
                'profit_rate' => 12.0,
                'duration_days' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Diamond Plan',
                'description' => 'Elite plan for high-net-worth individuals',
                'min_deposit' => 25000.00,
                'max_deposit' => 100000.00,
                'profit_rate' => 15.0,
                'duration_days' => 120,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Plan',
                'description' => 'Exclusive plan for VIP members with maximum returns',
                'min_deposit' => 100000.00,
                'max_deposit' => 1000000.00,
                'profit_rate' => 20.0,
                'duration_days' => 180,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
