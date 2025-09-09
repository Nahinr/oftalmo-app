<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'description' => 'Full access', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Doctor',        'description' => 'Clinical operations', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Receptionist',  'description' => 'Scheduling & reception', 'created_at' => now(), 'updated_at' => now()],
        ];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['name' => $role['name']], $role);
        }

        
        $adminEmail = 'admin@admin.com';
        $admin = DB::table('users')->where('email', $adminEmail)->first();
        $adminRole = DB::table('roles')->where('name', 'Administrator')->first();

        if ($admin && $adminRole) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $admin->id, 'role_id' => $adminRole->id],
                ['created_at' => now()]
            );
        }
    }
}
