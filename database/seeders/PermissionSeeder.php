<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name_permission' => 'show_data_anggota', 'jenis_permission' => 'show', 'fitur' => 'data_anggota'],
            ['name_permission' => 'edit_data_anggota', 'jenis_permission' => 'edit', 'fitur' => 'data_anggota'],
            ['name_permission' => 'delete_data_anggota', 'jenis_permission' => 'delete', 'fitur' => 'data_anggota'],
            ['name_permission' => 'manage_all', 'jenis_permission' => 'manage', 'fitur' => 'all'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name_permission' => $permission['name_permission']], $permission);
        }
    }
}
