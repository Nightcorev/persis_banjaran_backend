<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $superAdmin = Role::where('name_role', 'Super Admin')->first();
        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            $superAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
