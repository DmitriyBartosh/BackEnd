<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $plans = [
            [
                'name' => 'design',
                'periodicity' => 30,
            ],
            [
                'name' => 'frontend',
                'periodicity' => 30,
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
