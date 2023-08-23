<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class AddSuperAdmin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::find(1);
        $superadmin->assignRole(['name' => 'Super Admin']);
    }
}
