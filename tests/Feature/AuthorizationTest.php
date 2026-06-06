<?php

namespace Tests\Feature;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Database\Seeders\CasbinRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CasbinRuleSeeder::class);
    }

    public function test_super_admin_can_access_anything(): void
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermissionTo('master/anything', 'read'));
        $this->assertTrue($user->hasPermissionTo('transaction/something', 'delete'));
        $this->assertTrue($user->hasPermissionTo('random/unknown', 'create'));
    }

    public function test_admin_can_access_master_and_configuration(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermissionTo('master/users', 'read'));
        $this->assertTrue($user->hasPermissionTo('configuration/settings', 'update'));

        $this->assertFalse($user->hasPermissionTo('transaction/orders', 'read'));
    }

    public function test_supervisor_can_access_transaction_approval(): void
    {
        $role = Role::factory()->create(['name' => 'supervisor']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermissionTo('transaction/approval/orders', 'update'));

        $this->assertFalse($user->hasPermissionTo('transaction/orders', 'read'));
        $this->assertFalse($user->hasPermissionTo('master/users', 'read'));
    }

    public function test_spg_can_access_transaction(): void
    {
        $role = Role::factory()->create(['name' => 'spg']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermissionTo('transaction/orders', 'create'));
        $this->assertFalse($user->hasPermissionTo('master/users', 'read'));
    }

    public function test_casbin_middleware_denies_unauthorized_access(): void
    {
        $role = Role::factory()->create(['name' => 'spg']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get('/users');
        $response->assertStatus(403);
    }
}
