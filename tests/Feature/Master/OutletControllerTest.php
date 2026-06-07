<?php

namespace Tests\Feature\Master;

use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Outlet\Repositories\EloquentOutletRepository;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OutletControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['name' => 'super_admin']);
        $outlet = Outlet::factory()->create(['name' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->user->outlets()->attach($outlet->id);
    }

    public function test_can_view_edit_outlet_with_encrypted_id(): void
    {
        $outletToEdit = Outlet::factory()->create(['name' => 'test_outlet']);
        $encryptedId = Crypt::encryptString((string) $outletToEdit->id);

        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/edit");

        $response->assertStatus(200);
    }

    public function test_can_update_outlet_with_encrypted_id(): void
    {
        $outletToEdit = Outlet::factory()->create(['name' => 'test_outlet']);
        $encryptedId = Crypt::encryptString((string) $outletToEdit->id);

        $response = $this->actingAs($this->user)->put("/outlets/{$encryptedId}", [
            'name' => 'updated_outlet',
            'address' => 'updated address',
        ]);

        $response->assertRedirect('/outlets');
        $this->assertDatabaseHas('outlets', [
            'id' => $outletToEdit->id,
            'name' => 'updated_outlet',
        ]);
    }

    public function test_can_delete_outlet_with_encrypted_id(): void
    {
        $outletToDelete = Outlet::factory()->create(['name' => 'delete_me']);
        $encryptedId = Crypt::encryptString((string) $outletToDelete->id);

        $response = $this->actingAs($this->user)->delete("/outlets/{$encryptedId}");

        $response->assertRedirect('/outlets');
        $this->assertSoftDeleted('outlets', [
            'id' => $outletToDelete->id,
        ]);
    }

    public function test_can_view_outlets_index(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets');
        $response->assertStatus(200);
    }

    public function test_can_search_and_sort_outlets(): void
    {
        Outlet::factory()->create(['name' => 'zebra']);
        Outlet::factory()->create(['name' => 'alpha']);

        $response = $this->actingAs($this->user)->get('/outlets?search=alpha&sort_by=name&sort_direction=desc');
        $response->assertStatus(200);
    }

    public function test_can_view_create_outlet(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets/create');
        $response->assertStatus(200);
    }

    public function test_can_store_outlet(): void
    {
        $response = $this->actingAs($this->user)->post('/outlets', [
            'name' => 'new_outlet',
            'address' => 'new outlet address',
            'prefix' => 'NEW',
        ]);

        $response->assertRedirect('/outlets');
        $this->assertDatabaseHas('outlets', [
            'name' => 'new_outlet',
        ]);
    }

    public function test_can_store_outlet_with_chairs(): void
    {
        $response = $this->actingAs($this->user)->post('/outlets', [
            'name' => 'new_outlet_with_chairs',
            'address' => 'new outlet address',
            'prefix' => 'NEWC',
            'chairs_count' => 3,
        ]);

        $response->assertRedirect('/outlets');
        $this->assertDatabaseHas('outlets', [
            'name' => 'new_outlet_with_chairs',
        ]);
        
        $outlet = Outlet::where('name', 'new_outlet_with_chairs')->first();
        
        $this->assertDatabaseHas('chair_prefixes', [
            'outlet_id' => $outlet->id,
            'prefix' => 'NEWC',
            'last_counter' => 3,
        ]);

        $this->assertDatabaseCount('chairs', 3);
        $this->assertDatabaseHas('chairs', [
            'outlet_id' => $outlet->id,
            'name' => 'NEWC-1',
        ]);
        $this->assertDatabaseHas('chairs', [
            'outlet_id' => $outlet->id,
            'name' => 'NEWC-3',
        ]);
    }

    public function test_abort_404_when_editing_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->get('/outlets/invalid_id/edit');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_editing_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('99999');
        $response = $this->actingAs($this->user)->get("/outlets/{$encryptedId}/edit");
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->put('/outlets/invalid_id', [
            'name' => 'updated_outlet',
        ]);
        $response->assertStatus(404);
    }

    public function test_abort_404_when_deleting_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->delete('/outlets/invalid_id');
        $response->assertStatus(404);
    }

    public function test_repository_update_returns_false_for_non_existent_outlet(): void
    {
        $repository = new EloquentOutletRepository;
        $this->assertFalse($repository->update('99999', ['name' => 'updated']));
    }

    public function test_repository_delete_returns_false_for_non_existent_outlet(): void
    {
        $repository = new EloquentOutletRepository;
        $this->assertFalse($repository->delete('99999'));
    }

    public function test_outlet_user_relationship(): void
    {
        $outlet = Outlet::factory()->create();
        $user = User::factory()->create();

        $outlet->users()->attach($user->id);

        $this->assertTrue($outlet->users->contains($user));

        $pivot = $outlet->users->first()->pivot;
        $this->assertInstanceOf(LinkedOutletUser::class, $pivot);
        $this->assertEquals($user->id, $pivot->user->id);
        $this->assertEquals($outlet->id, $pivot->outlet->id);
    }

    public function test_can_update_status_of_outlet_with_encrypted_id(): void
    {
        $outletToUpdate = Outlet::factory()->create(['is_active' => '1']);
        $encryptedId = Crypt::encryptString((string) $outletToUpdate->id);

        $response = $this->actingAs($this->user)->patch("/outlets/{$encryptedId}/status");

        $response->assertRedirect();
        $this->assertDatabaseHas('outlets', [
            'id' => $outletToUpdate->id,
            'is_active' => '0',
        ]);

        // Toggle back
        $this->actingAs($this->user)->patch("/outlets/{$encryptedId}/status");
        $this->assertDatabaseHas('outlets', [
            'id' => $outletToUpdate->id,
            'is_active' => '1',
        ]);
    }

    public function test_abort_404_when_updating_status_of_invalid_encrypted_id(): void
    {
        $response = $this->actingAs($this->user)->patch('/outlets/invalid_id/status');
        $response->assertStatus(404);
    }

    public function test_abort_404_when_updating_status_of_non_existent_outlet(): void
    {
        $encryptedId = Crypt::encryptString('99999');
        $response = $this->actingAs($this->user)->patch("/outlets/{$encryptedId}/status");
        $response->assertStatus(404);
    }
}
