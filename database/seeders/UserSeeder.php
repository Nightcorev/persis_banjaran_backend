<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $superAdminRole = Role::where('name_role', 'Super Admin')->first();

        User::firstOrCreate([
            'email' => 'superadmin@example.com'
        ], [
            'name' => 'Super Admin',
            'username' => 'admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('admin123'),
            'role_id' => $superAdminRole->id,
            'id_anggota' => 206,
        ]);
    }
}
