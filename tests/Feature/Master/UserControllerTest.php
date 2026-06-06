<?php

namespace Tests\Feature\Master;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Domain\UserAccess\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::factory()->create(['name' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $this->role->id]);
    }

    public function test_can_view_edit_user_with_encrypted_id(): void
    {
        $userToEdit = User::factory()->create(['role_id' => $this->role->id]);
        $encryptedId = Crypt::encryptString((string) $userToEdit->id);

        $response = $this->actingAs($this->user)->get("/users/{$encryptedId}/edit");

        $response->assertStatus(200);
    }

    public function test_can_update_user_with_encrypted_id_and_encrypted_role_id(): void
    {
        $userToEdit = User::factory()->create(['role_id' => $this->role->id, 'username' => 'old_user']);
        $encryptedId = Crypt::encryptString((string) $userToEdit->id);

        $newRole = Role::factory()->create(['name' => 'supervisor']);
        $encryptedRoleId = Crypt::encryptString((string) $newRole->id);

        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'new_user',
            'name' => 'New User Name',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $userToEdit->id,
            'username' => 'new_user',
            'role_id' => $newRole->id,
        ]);
    }

    public function test_can_store_user_with_encrypted_role_id(): void
    {
        $newRole = Role::factory()->create(['name' => 'spg']);
        $encryptedRoleId = Crypt::encryptString((string) $newRole->id);

        $response = $this->actingAs($this->user)->post('/users', [
            'username' => 'newly_created',
            'name' => 'Brand New User',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'newly_created',
            'role_id' => $newRole->id,
        ]);
    }

    public function test_can_delete_user_with_encrypted_id(): void
    {
        $userToDelete = User::factory()->create(['role_id' => $this->role->id]);
        $encryptedId = Crypt::encryptString((string) $userToDelete->id);

        $response = $this->actingAs($this->user)->delete("/users/{$encryptedId}");

        $response->assertRedirect('/users');
        $this->assertSoftDeleted('users', [
            'id' => $userToDelete->id,
        ]);
    }

    public function test_can_view_users_index(): void
    {
        $response = $this->actingAs($this->user)->get('/users');
        $response->assertStatus(200);
    }

    public function test_can_search_and_sort_users(): void
    {
        User::factory()->create(['name' => 'zebra', 'role_id' => $this->role->id]);
        User::factory()->create(['name' => 'alpha', 'role_id' => $this->role->id]);

        $response = $this->actingAs($this->user)->get('/users?search=alpha&sort_by=name&sort_direction=desc');
        $response->assertStatus(200);
    }

    public function test_can_view_create_user(): void
    {
        $response = $this->actingAs($this->user)->get('/users/create');
        $response->assertStatus(200);
    }

    public function test_abort_404_when_editing_invalid_encrypted_user_id(): void
    {
        $response = $this->actingAs($this->user)->get('/users/invalid_id/edit');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_editing_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->get("/users/{$encryptedId}/edit");
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_invalid_encrypted_user_id(): void
    {
        $response = $this->actingAs($this->user)->put('/users/invalid_id', [
            'username' => 'test_user',
            'name' => 'Test User',
            'role_id' => Crypt::encryptString((string) $this->role->id),
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_deleting_invalid_encrypted_user_id(): void
    {
        $response = $this->actingAs($this->user)->delete('/users/invalid_id');
        $response->assertStatus(404);
    }

    public function test_store_user_with_invalid_encrypted_role_id(): void
    {
        $response = $this->actingAs($this->user)->post('/users', [
            'username' => 'new_user',
            'name' => 'New User',
            'role_id' => 'invalid_role_id',
        ]);

        $response->assertSessionHasErrors(['role_id']);
    }

    public function test_update_user_with_invalid_encrypted_role_id(): void
    {
        $userToEdit = User::factory()->create(['role_id' => $this->role->id]);
        $encryptedId = Crypt::encryptString((string) $userToEdit->id);

        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'updated_user',
            'name' => 'Updated User',
            'role_id' => 'invalid_role_id',
        ]);

        $response->assertSessionHasErrors(['role_id']);
    }

    public function test_repository_update_returns_false_for_non_existent_user(): void
    {
        $repository = new EloquentUserRepository;
        $this->assertFalse($repository->update('99999999-9999-9999-9999-999999999999', ['name' => 'updated']));
    }

    public function test_repository_delete_returns_false_for_non_existent_user(): void
    {
        $repository = new EloquentUserRepository;
        $this->assertFalse($repository->delete('99999999-9999-9999-9999-999999999999'));
    }

    public function test_can_update_status_of_user_with_encrypted_id(): void
    {
        $userToUpdate = User::factory()->create(['is_active' => '1', 'role_id' => $this->role->id]);
        $encryptedId = Crypt::encryptString((string) $userToUpdate->id);

        $response = $this->actingAs($this->user)->patch("/users/{$encryptedId}/status");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'is_active' => '0',
        ]);

        // Toggle back
        $this->actingAs($this->user)->patch("/users/{$encryptedId}/status");
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'is_active' => '1',
        ]);
    }

    public function test_abort_404_when_updating_status_of_invalid_encrypted_user_id(): void
    {
        $response = $this->actingAs($this->user)->patch('/users/invalid_id/status');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_status_of_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->patch("/users/{$encryptedId}/status");
        $response->assertStatus(404);
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedRoleId = Crypt::encryptString((string) $superAdminRole->id);

        $response = $this->actingAs($this->user)->post('/users', [
            'username' => 'new_super_admin',
            'name' => 'New Super Admin',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_super_admin_can_assign_super_admin_role(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedRoleId = Crypt::encryptString((string) $superAdminRole->id);

        $superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);

        $response = $this->actingAs($superAdminUser)->post('/users', [
            'username' => 'another_super_admin',
            'name' => 'Another Super Admin',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'another_super_admin',
            'role_id' => $superAdminRole->id,
        ]);
    }

    public function test_non_super_admin_cannot_edit_super_admin_user(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $encryptedId = Crypt::encryptString((string) $superAdminUser->id);

        $response = $this->actingAs($this->user)->get("/users/{$encryptedId}/edit");
        $response->assertStatus(403);

        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'updated_super_admin',
            'name' => 'Updated Super Admin',
            'role_id' => Crypt::encryptString((string) $this->role->id),
        ]);
        $response->assertStatus(403);
    }

    public function test_non_super_admin_cannot_delete_super_admin_user(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $encryptedId = Crypt::encryptString((string) $superAdminUser->id);

        $response = $this->actingAs($this->user)->delete("/users/{$encryptedId}");
        $response->assertStatus(403);
    }

    public function test_non_super_admin_cannot_update_status_of_super_admin_user(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $encryptedId = Crypt::encryptString((string) $superAdminUser->id);

        $response = $this->actingAs($this->user)->patch("/users/{$encryptedId}/status");
        $response->assertStatus(403);
    }

    public function test_abort_404_when_updating_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'test_user',
            'name' => 'Test User',
            'role_id' => Crypt::encryptString((string) $this->role->id),
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_deleting_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->delete("/users/{$encryptedId}");
        $response->assertStatus(404);
    }

    public function test_non_super_admin_cannot_update_user_to_super_admin_role(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedRoleId = Crypt::encryptString((string) $superAdminRole->id);

        $userToEdit = User::factory()->create(['role_id' => $this->role->id]);
        $encryptedId = Crypt::encryptString((string) $userToEdit->id);

        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'updated_user',
            'name' => 'Updated User',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_cannot_delete_own_account_as_non_super_admin(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->user->id);

        $response = $this->actingAs($this->user)->delete("/users/{$encryptedId}");

        $response->assertStatus(403);
    }

    public function test_cannot_update_status_of_own_account_as_non_super_admin(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->user->id);

        $response = $this->actingAs($this->user)->patch("/users/{$encryptedId}/status");

        $response->assertStatus(403);
    }

    public function test_cannot_update_own_role_as_non_super_admin(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->user->id);
        
        $newRole = Role::factory()->create(['name' => 'new_role']);
        $encryptedRoleId = Crypt::encryptString((string) $newRole->id);

        $response = $this->actingAs($this->user)->put("/users/{$encryptedId}", [
            'username' => 'updated_username',
            'name' => 'Updated Name',
            'role_id' => $encryptedRoleId,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
