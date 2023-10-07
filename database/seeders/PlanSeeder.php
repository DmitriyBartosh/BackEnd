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
                'title' => 'графический дизайн',
                'periodicity' => 30,
            ],
            [
                'name' => 'frontend',
                'title' => 'фронтенд разработка',
                'periodicity' => 30,
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
