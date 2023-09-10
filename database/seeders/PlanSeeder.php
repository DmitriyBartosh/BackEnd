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
        $designPlan = Plan::create([
            'name'             => 'design',
            'periodicity_type' => 'Month',
            'periodicity'      => 1,
        ]);
    }
}
