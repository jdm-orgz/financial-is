<?php

namespace Tests\Feature\Master;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ChairControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['name' => 'super_admin']);
        $this->outlet = Outlet::factory()->create(['name' => 'admin outlet']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->user->outlets()->attach($this->outlet->id);
    }

    public function test_can_view_chairs_index_for_valid_outlet(): void
    {
        Chair::factory()->count(3)->create(['outlet_id' => $this->outlet->id]);

        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/chairs");

        $response->assertStatus(200);
    }

    public function test_abort_404_when_viewing_chairs_for_invalid_encrypted_outlet_id(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets/invalid_id/chairs');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_viewing_chairs_for_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/chairs");
        $response->assertStatus(404);
    }

    public function test_can_view_chairs_create_page(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->outlet->id);
        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/chairs/create");
        $response->assertStatus(200);
    }

    public function test_abort_404_when_creating_chair_for_invalid_encrypted_outlet_id(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets/invalid_id/chairs/create');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_creating_chair_for_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/chairs/create");
        $response->assertStatus(404);
    }

    public function test_can_store_chair_for_outlet(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs", [
            'name' => 'Test Chair',
        ]);

        $response->assertRedirect(route('outlets.chairs.index', ['outlet' => $encryptedId]));

        $this->assertDatabaseHas('chairs', [
            'name' => 'Test Chair',
            'outlet_id' => $this->outlet->id,
            'is_active' => '1',
        ]);
    }

    public function test_can_store_bulk_chairs_for_outlet(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs/bulk", [
            'chairs_count' => 5,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('chairs', 5);
    }

    public function test_can_store_bulk_chairs_with_prefix(): void
    {
        $chairPrefix = $this->outlet->chairPrefix()->create([
            'prefix' => 'BULK',
            'last_counter' => 0,
        ]);

        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs/bulk", [
            'chairs_count' => 3,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('chairs', 3);
        $this->assertDatabaseHas('chairs', [
            'name' => 'BULK-1',
            'outlet_id' => $this->outlet->id,
        ]);
        $this->assertDatabaseHas('chairs', [
            'name' => 'BULK-3',
            'outlet_id' => $this->outlet->id,
        ]);

        $this->assertInstanceOf(\App\Domain\Outlet\Models\Outlet::class, $chairPrefix->outlet);
    }

    public function test_abort_404_when_storing_chair_for_invalid_encrypted_outlet_id(): void
    {
        $response = $this->actingAs($this->user)->post('/outlets/invalid_id/chairs', [
            'name' => 'Test Chair',
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_storing_chair_for_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs", [
            'name' => 'Test Chair',
        ]);
        $response->assertStatus(404);
    }

    public function test_can_store_chair_with_prefix_fallback(): void
    {
        $this->outlet->chairPrefix()->create([
            'prefix' => 'PREFIX',
            'last_counter' => 0,
        ]);

        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs", [
            'name' => '', // Empty name triggers fallback
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chairs', [
            'name' => 'PREFIX-1',
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_can_store_chair_with_uniqid_fallback(): void
    {
        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs", [
            'name' => '', // Empty name, no prefix triggers uniqid fallback
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseCount('chairs', 1);
        $chair = Chair::first();
        $this->assertStringStartsWith('Chair ', $chair->name);
    }

    public function test_abort_404_when_storing_bulk_chairs_for_invalid_encrypted_outlet_id(): void
    {
        $response = $this->actingAs($this->user)->post('/outlets/invalid_id/chairs/bulk', [
            'chairs_count' => 5,
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_storing_bulk_chairs_for_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->post("/outlets/{$encryptedId}/chairs/bulk", [
            'chairs_count' => 5,
        ]);
        $response->assertStatus(404);
    }

    public function test_can_view_chairs_edit_page(): void
    {
        $chair = Chair::factory()->create(['outlet_id' => $this->outlet->id]);

        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $encryptedChairId = Crypt::encryptString((string) $chair->id);

        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}/edit");
        $response->assertStatus(200);
    }

    public function test_abort_404_when_editing_invalid_encrypted_outlet_or_chair_id(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets/invalid_id/chairs/invalid_id/edit');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_editing_non_existent_outlet_or_chair(): void
    {
        $encryptedOutletId = Crypt::encryptString('non-existent-id');
        $encryptedChairId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}/edit");
        $response->assertStatus(404);
    }

    public function test_can_update_chair_for_outlet(): void
    {
        $chair = Chair::factory()->create(['outlet_id' => $this->outlet->id, 'name' => 'Old Name']);

        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $encryptedChairId = Crypt::encryptString((string) $chair->id);

        $response = $this->actingAs($this->user)->put("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}", [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('outlets.chairs.index', ['outlet' => $encryptedOutletId]));

        $this->assertDatabaseHas('chairs', [
            'id' => $chair->id,
            'name' => 'New Name',
        ]);
    }

    public function test_abort_404_when_updating_invalid_encrypted_chair_id(): void
    {
        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $response = $this->actingAs($this->user)->put("/outlets/{$encryptedOutletId}/chairs/invalid_id", [
            'name' => 'New Name',
        ]);
        $response->assertStatus(404);
    }

    public function test_can_delete_chair(): void
    {
        $chair = Chair::factory()->create(['outlet_id' => $this->outlet->id]);

        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $encryptedChairId = Crypt::encryptString((string) $chair->id);

        $response = $this->actingAs($this->user)->delete("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}");

        $response->assertRedirect(route('outlets.chairs.index', ['outlet' => $encryptedOutletId]));

        $this->assertSoftDeleted('chairs', [
            'id' => $chair->id,
        ]);
    }

    public function test_abort_404_when_deleting_invalid_encrypted_chair_id(): void
    {
        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $response = $this->actingAs($this->user)->delete("/outlets/{$encryptedOutletId}/chairs/invalid_id");
        $response->assertStatus(404);
    }

    public function test_can_toggle_chair_status(): void
    {
        $chair = Chair::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => '1']);

        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $encryptedChairId = Crypt::encryptString((string) $chair->id);

        $response = $this->actingAs($this->user)->patch("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}/status");

        $response->assertRedirect();

        $this->assertDatabaseHas('chairs', [
            'id' => $chair->id,
            'is_active' => '0',
        ]);
        
        $this->assertInstanceOf(\App\Domain\Outlet\Models\Outlet::class, $chair->outlet);
    }

    public function test_abort_404_when_updating_status_of_invalid_encrypted_chair_id(): void
    {
        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $response = $this->actingAs($this->user)->patch("/outlets/{$encryptedOutletId}/chairs/invalid_id/status");
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_status_of_non_existent_chair(): void
    {
        $encryptedOutletId = Crypt::encryptString((string) $this->outlet->id);
        $encryptedChairId = Crypt::encryptString('non-existent-id');
        $response = $this->actingAs($this->user)->patch("/outlets/{$encryptedOutletId}/chairs/{$encryptedChairId}/status");
        $response->assertStatus(404);
    }

    public function test_chair_repository_update_returns_false_for_non_existent_chair(): void
    {
        $repository = new \App\Domain\Outlet\Repositories\EloquentChairRepository();
        $this->assertFalse($repository->update('99999', ['name' => 'updated']));
    }

    public function test_chair_repository_delete_returns_false_for_non_existent_chair(): void
    {
        $repository = new \App\Domain\Outlet\Repositories\EloquentChairRepository();
        $this->assertFalse($repository->delete('99999'));
    }

    public function test_can_search_and_sort_chairs(): void
    {
        Chair::factory()->create(['outlet_id' => $this->outlet->id, 'name' => 'zebra chair']);
        Chair::factory()->create(['outlet_id' => $this->outlet->id, 'name' => 'alpha chair']);

        $encryptedId = Crypt::encryptString((string) $this->outlet->id);

        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/chairs?search=alpha&sort_by=name&sort_direction=desc");
        $response->assertStatus(200);
    }
}
