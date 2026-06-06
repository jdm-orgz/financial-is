<?php

namespace Database\Seeders;

use App\Domain\UserAccess\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['description' => 'Super Administrator']
        );

        Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator']
        );

        Role::firstOrCreate(
            ['name' => 'supervisor'],
            ['description' => 'Supervisor']
        );

        Role::firstOrCreate(
            ['name' => 'spg'],
            ['description' => 'SPG']
        );
    }
}
