<?php

namespace Tests\Feature\Master;

use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Outlet\Repositories\EloquentLinkedOutletUserRepository;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class LinkedOutletUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['name' => 'super_admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->outlet1 = Outlet::factory()->create();
        $this->outlet2 = Outlet::factory()->create();
    }

    public function test_can_view_index(): void
    {
        $response = $this->actingAs($this->user)->get('/linked-outlet-users');
        $response->assertStatus(200);
    }

    public function test_can_view_create(): void
    {
        $response = $this->actingAs($this->user)->get('/linked-outlet-users/create');
        $response->assertStatus(200);
    }

    public function test_can_view_create_with_existing_links(): void
    {
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $response = $this->actingAs($this->user)->get('/linked-outlet-users/create');
        $response->assertStatus(200);
    }

    public function test_store_with_invalid_encrypted_ids_fails_validation(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->user)->post('/linked-outlet-users', [
            'user_id' => 'not-an-encrypted-string',
            'outlet_ids' => ['also-not-encrypted'],
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['user_id', 'outlet_ids']);
    }

    public function test_update_with_invalid_encrypted_ids_fails_validation(): void
    {
        $targetUser = User::factory()->create();
        $encryptedId = Crypt::encryptString((string) $targetUser->id);

        $response = $this->actingAs($this->user)->put("/linked-outlet-users/{$encryptedId}", [
            'outlet_ids' => ['not-encrypted-string'],
        ]);

        $response->assertSessionHasErrors(['outlet_ids']);
    }

    public function test_can_store(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->user)->post('/linked-outlet-users', [
            'user_id' => Crypt::encryptString((string) $targetUser->id),
            'outlet_ids' => [
                Crypt::encryptString((string) $this->outlet1->id),
                Crypt::encryptString((string) $this->outlet2->id),
            ],
            'is_active' => '1',
        ]);

        $response->assertRedirect('/linked-outlet-users');
        $this->assertDatabaseHas('linked_outlets_users', [
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
        ]);
    }

    public function test_can_view_edit(): void
    {
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $encryptedId = Crypt::encryptString((string) $targetUser->id);
        $response = $this->actingAs($this->user)->get("/linked-outlet-users/{$encryptedId}/edit");

        $response->assertStatus(200);
    }

    public function test_abort_404_when_editing_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->get('/linked-outlet-users/invalid_id/edit');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_editing_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->get("/linked-outlet-users/{$encryptedId}/edit");
        $response->assertStatus(404);
    }

    public function test_can_update(): void
    {
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $encryptedId = Crypt::encryptString((string) $targetUser->id);

        $response = $this->actingAs($this->user)->put("/linked-outlet-users/{$encryptedId}", [
            'outlet_ids' => [Crypt::encryptString((string) $this->outlet2->id)],
        ]);

        $response->assertRedirect('/linked-outlet-users');
        $this->assertDatabaseMissing('linked_outlets_users', [
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'deleted_at' => null, // Should be soft deleted or hard deleted
        ]);
        $this->assertDatabaseHas('linked_outlets_users', [
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet2->id,
        ]);
    }

    public function test_abort_404_when_updating_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->put('/linked-outlet-users/invalid_id', [
            'outlet_ids' => [Crypt::encryptString((string) $this->outlet1->id)],
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_non_existent_user(): void
    {
        $encryptedId = Crypt::encryptString('99999999-9999-9999-9999-999999999999');
        $response = $this->actingAs($this->user)->put("/linked-outlet-users/{$encryptedId}", [
            'outlet_ids' => [Crypt::encryptString((string) $this->outlet1->id)],
        ]);
        $response->assertStatus(404);
    }

    public function test_can_delete(): void
    {
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $encryptedId = Crypt::encryptString((string) $targetUser->id);
        $response = $this->actingAs($this->user)->delete("/linked-outlet-users/{$encryptedId}");

        $response->assertRedirect('/linked-outlet-users');
        $this->assertSoftDeleted('linked_outlets_users', [
            'user_id' => $targetUser->id,
        ]);
    }

    public function test_abort_404_when_deleting_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->delete('/linked-outlet-users/invalid_id');
        $response->assertStatus(404);
    }

    public function test_can_update_status(): void
    {
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $encryptedId = Crypt::encryptString((string) $targetUser->id);
        $response = $this->actingAs($this->user)->patch("/linked-outlet-users/{$encryptedId}/status");

        $response->assertRedirect();
        $this->assertDatabaseHas('linked_outlets_users', [
            'user_id' => $targetUser->id,
            'is_active' => '0',
        ]);

        // Toggle back
        $this->actingAs($this->user)->patch("/linked-outlet-users/{$encryptedId}/status");
        $this->assertDatabaseHas('linked_outlets_users', [
            'user_id' => $targetUser->id,
            'is_active' => '1',
        ]);
    }

    public function test_abort_404_when_updating_status_of_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->patch('/linked-outlet-users/invalid_id/status');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_status_of_user_without_links(): void
    {
        $targetUser = User::factory()->create();
        $encryptedId = Crypt::encryptString((string) $targetUser->id);
        $response = $this->actingAs($this->user)->patch("/linked-outlet-users/{$encryptedId}/status");
        $response->assertStatus(404);
    }

    public function test_repository_get_paginated(): void
    {
        $repository = new EloquentLinkedOutletUserRepository;
        $targetUser = User::factory()->create();
        LinkedOutletUser::create([
            'user_id' => $targetUser->id,
            'outlet_id' => $this->outlet1->id,
            'is_active' => '1',
        ]);

        $result = $repository->getPaginated(10, null, 'created_at', 'desc');
        $this->assertNotEmpty($result->items());
    }
}
