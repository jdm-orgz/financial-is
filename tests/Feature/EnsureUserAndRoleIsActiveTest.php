<?php

namespace Tests\Feature;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserAndRoleIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_with_active_role_can_access_dashboard()
    {
        $role = Role::factory()->create(['is_active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_inactive_user_is_logged_out_and_redirected()
    {
        $role = Role::factory()->create(['is_active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $response->assertSessionHas('inertia.flash_data');
    }

    public function test_active_user_with_inactive_role_is_logged_out_and_redirected()
    {
        $role = Role::factory()->create(['is_active' => false]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $response->assertSessionHas('inertia.flash_data');
    }

    public function test_guest_is_not_affected()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
