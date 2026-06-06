<?php

namespace Database\Seeders;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OutletSeeder::class,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();

        User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'superadmin',
            'name' => 'Super Administrator',
            'email' => 'superadmin@mail.com',
        ]);

        User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@mail.com',
        ]);

        User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'supervisor',
            'name' => 'Supervisor',
            'email' => 'supervisor@mail.com',
        ]);
    }
}
