<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::firstOrCreate(['name_role' => 'Super Admin']);
        Role::firstOrCreate(['name_role' => 'Admin']);
    }
}
