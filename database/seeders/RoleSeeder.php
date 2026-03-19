<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Administrator with full access',
                'permissions' => ['C', 'R', 'U', 'D'],
            ]
        );

        \App\Models\Role::firstOrCreate(
            ['name' => 'cmdcenter'],
            [
                'description' => 'CMD Center with view/edit but not delete access and download access',
                'permissions' => ['C', 'R', 'U'],
            ]
        );

        \App\Models\Role::firstOrCreate(
            ['name' => 'user'],
            [
                'description' => 'Regular user',
                'permissions' => ['R'],
            ]
        );

        // Assign admin role to admin@example.com
        $admin = \App\Models\User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
