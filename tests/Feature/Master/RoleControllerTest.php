<?php

namespace Tests\Feature\Master;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Domain\UserAccess\Repositories\EloquentRoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Lauthz\Facades\Enforcer;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_can_view_edit_role_with_encrypted_id(): void
    {
        $roleToEdit = Role::factory()->create(['name' => 'test_role']);
        $encryptedId = Crypt::encryptString((string) $roleToEdit->id);

        $response = $this->actingAs($this->user)->get("/roles/{$encryptedId}/edit");

        $response->assertStatus(200);
    }

    public function test_can_update_role_with_encrypted_id(): void
    {
        $roleToEdit = Role::factory()->create(['name' => 'test_role', 'description' => 'old description']);
        $encryptedId = Crypt::encryptString((string) $roleToEdit->id);

        $response = $this->actingAs($this->user)->put("/roles/{$encryptedId}", [
            'name' => 'updated_role',
            'description' => 'updated description',
        ]);

        $response->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', [
            'id' => $roleToEdit->id,
            'name' => 'test_role', // Should remain unchanged
            'description' => 'updated description',
        ]);
    }

    public function test_can_delete_role_with_encrypted_id(): void
    {
        $roleToDelete = Role::factory()->create(['name' => 'delete_me']);
        $encryptedId = Crypt::encryptString((string) $roleToDelete->id);

        $response = $this->actingAs($this->user)->delete("/roles/{$encryptedId}");

        $response->assertRedirect('/roles');
        $this->assertSoftDeleted('roles', [
            'id' => $roleToDelete->id,
        ]);
    }

    public function test_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->user)->get('/roles');
        $response->assertStatus(200);
    }

    public function test_can_search_and_sort_roles(): void
    {
        Role::factory()->create(['name' => 'zebra']);
        Role::factory()->create(['name' => 'alpha']);

        $response = $this->actingAs($this->user)->get('/roles?search=alpha&sort_by=name&sort_direction=desc');
        $response->assertStatus(200);
    }

    public function test_can_view_create_role(): void
    {
        $response = $this->actingAs($this->user)->get('/roles/create');
        $response->assertStatus(200);
    }

    public function test_can_store_role(): void
    {
        $response = $this->actingAs($this->user)->post('/roles', [
            'name' => 'new_role',
            'description' => 'new role description',
        ]);

        $response->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
        ]);
    }

    public function test_abort_404_when_editing_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->get('/roles/invalid_id/edit');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_editing_non_existent_role(): void
    {
        $encryptedId = Crypt::encryptString('99999');
        $response = $this->actingAs($this->user)->get("/roles/{$encryptedId}/edit");
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->put('/roles/invalid_id', [
            'name' => 'updated_role',
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_deleting_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->delete('/roles/invalid_id');
        $response->assertStatus(404);
    }

    public function test_repository_update_returns_false_for_non_existent_role(): void
    {
        $repository = new EloquentRoleRepository;
        $this->assertFalse($repository->update(99999, ['name' => 'updated']));
    }

    public function test_repository_delete_returns_false_for_non_existent_role(): void
    {
        $repository = new EloquentRoleRepository;
        $this->assertFalse($repository->delete(99999));
    }

    public function test_can_update_status_of_role_with_encrypted_id(): void
    {
        $roleToUpdate = Role::factory()->create(['is_active' => '1']);
        $encryptedId = Crypt::encryptString((string) $roleToUpdate->id);

        $response = $this->actingAs($this->user)->patch("/roles/{$encryptedId}/status");

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'id' => $roleToUpdate->id,
            'is_active' => '0',
        ]);

        // Toggle back
        $this->actingAs($this->user)->patch("/roles/{$encryptedId}/status");
        $this->assertDatabaseHas('roles', [
            'id' => $roleToUpdate->id,
            'is_active' => '1',
        ]);
    }

    public function test_abort_404_when_updating_status_of_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->patch('/roles/invalid_id/status');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_status_of_non_existent_role(): void
    {
        $encryptedId = Crypt::encryptString('99999');
        $response = $this->actingAs($this->user)->patch("/roles/{$encryptedId}/status");
        $response->assertStatus(404);
    }

    public function test_abort_403_when_non_super_admin_edits_super_admin(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedId = Crypt::encryptString((string) $superAdminRole->id);

        $response = $this->actingAs($this->user)->get("/roles/{$encryptedId}/edit");
        $response->assertStatus(403);
    }

    public function test_abort_403_when_non_super_admin_updates_super_admin(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedId = Crypt::encryptString((string) $superAdminRole->id);

        $response = $this->actingAs($this->user)->put("/roles/{$encryptedId}", [
            'name' => 'changed_name',
            'description' => 'changed desc',
        ]);
        $response->assertStatus(403);
    }

    public function test_abort_403_when_non_super_admin_deletes_super_admin(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedId = Crypt::encryptString((string) $superAdminRole->id);

        $response = $this->actingAs($this->user)->delete("/roles/{$encryptedId}");
        $response->assertStatus(403);
    }

    public function test_abort_403_when_non_super_admin_updates_status_of_super_admin(): void
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $encryptedId = Crypt::encryptString((string) $superAdminRole->id);

        $response = $this->actingAs($this->user)->patch("/roles/{$encryptedId}/status");
        $response->assertStatus(403);
    }

    public function test_cannot_store_super_admin_role(): void
    {
        $response = $this->actingAs($this->user)->post('/roles', [
            'name' => 'super_admin',
            'description' => 'cannot create this',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_store_role_with_permissions(): void
    {
        $response = $this->actingAs($this->user)->post('/roles', [
            'name' => 'new_role_with_perms',
            'description' => 'new role description',
            'permissions' => ['view_users', 'edit_users'],
        ]);

        $response->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', [
            'name' => 'new_role_with_perms',
        ]);
    }

    public function test_cannot_edit_own_role_as_non_super_admin(): void
    {
        $supervisorRole = Role::factory()->create(['name' => 'supervisor']);
        Enforcer::addPolicy('supervisor', 'master/*', '*');
        $supervisorUser = User::factory()->create(['role_id' => $supervisorRole->id]);

        $encryptedId = Crypt::encryptString((string) $supervisorRole->id);
        $response = $this->actingAs($supervisorUser)->get("/roles/{$encryptedId}/edit");
        $response->assertStatus(403);
    }

    public function test_cannot_update_own_role_as_non_super_admin(): void
    {
        $supervisorRole = Role::factory()->create(['name' => 'supervisor']);
        Enforcer::addPolicy('supervisor', 'master/*', '*');
        $supervisorUser = User::factory()->create(['role_id' => $supervisorRole->id]);

        $encryptedId = Crypt::encryptString((string) $supervisorRole->id);
        $response = $this->actingAs($supervisorUser)->put("/roles/{$encryptedId}", [
            'name' => 'supervisor',
            'description' => 'updated description',
        ]);
        $response->assertStatus(403);
    }

    public function test_can_update_role_with_permissions(): void
    {
        $roleToEdit = Role::factory()->create(['name' => 'test_role_perms']);
        $encryptedId = Crypt::encryptString((string) $roleToEdit->id);

        Enforcer::addPolicy($roleToEdit->name, 'old_permission', '*');

        $response = $this->actingAs($this->user)->put("/roles/{$encryptedId}", [
            'name' => 'test_role_perms_updated',
            'description' => 'updated description',
            'permissions' => ['new_permission'],
        ]);

        $response->assertRedirect('/roles');
    }

    public function test_cannot_delete_own_role_as_non_super_admin(): void
    {
        $supervisorRole = Role::factory()->create(['name' => 'supervisor']);
        Enforcer::addPolicy('supervisor', 'master/*', '*');
        $supervisorUser = User::factory()->create(['role_id' => $supervisorRole->id]);

        $encryptedId = Crypt::encryptString((string) $supervisorRole->id);
        $response = $this->actingAs($supervisorUser)->delete("/roles/{$encryptedId}");
        $response->assertStatus(403);
    }

    public function test_cannot_update_status_of_own_role_as_non_super_admin(): void
    {
        $supervisorRole = Role::factory()->create(['name' => 'supervisor', 'is_active' => '1']);
        Enforcer::addPolicy('supervisor', 'master/*', '*');
        $supervisorUser = User::factory()->create(['role_id' => $supervisorRole->id]);

        $encryptedId = Crypt::encryptString((string) $supervisorRole->id);
        $response = $this->actingAs($supervisorUser)->patch("/roles/{$encryptedId}/status");
        $response->assertStatus(403);
    }
}
