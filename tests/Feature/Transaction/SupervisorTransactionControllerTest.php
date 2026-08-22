<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SupervisorTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->supervisor = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->supervisor->outlets()->attach($this->outlet->id, ['is_active' => '1', 'created_by' => $this->supervisor->id]);
        
        $this->transaction = Transaction::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Approval,
        ]);
    }

    public function test_index()
    {
        $response = $this->actingAs($this->supervisor)->get('/supervisor/transactions');
        $response->assertStatus(200);
    }

    public function test_show()
    {
        $response = $this->actingAs($this->supervisor)->get('/supervisor/transactions/' . Crypt::encryptString($this->transaction->id));
        $response->assertStatus(200);
    }

    public function test_show_unauthorized_outlet()
    {
        $otherOutlet = Outlet::factory()->create();
        $otherTransaction = Transaction::factory()->create(['outlet_id' => $otherOutlet->id]);
        
        $response = $this->actingAs($this->supervisor)->get('/supervisor/transactions/' . Crypt::encryptString($otherTransaction->id));
        $response->assertStatus(403);
    }

    public function test_show_invalid_id()
    {
        $response = $this->actingAs($this->supervisor)->get('/supervisor/transactions/invalid');
        $response->assertStatus(404);
        
        $response = $this->actingAs($this->supervisor)->get('/supervisor/transactions/' . Crypt::encryptString(999));
        $response->assertStatus(404);
    }

    public function test_approve_success()
    {
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/' . Crypt::encryptString($this->transaction->id) . '/approve');
        $response->assertRedirect('/supervisor/transactions');
        $this->assertEquals(TransactionStatus::Comparing, $this->transaction->fresh()->status);
    }
    
    public function test_approve_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/' . Crypt::encryptString($this->transaction->id) . '/approve');
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Draft, $this->transaction->fresh()->status);
    }
    
    public function test_approve_invalid_id()
    {
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/invalid/approve');
        $response->assertStatus(404);
    }

    public function test_reject_success()
    {
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/' . Crypt::encryptString($this->transaction->id) . '/reject', [
            'supervisor_notes' => 'Needs fixing',
        ]);
        $response->assertRedirect('/supervisor/transactions');
        $this->assertEquals(TransactionStatus::Correction, $this->transaction->fresh()->status);
        $this->assertEquals('Needs fixing', $this->transaction->fresh()->supervisor_notes);
    }

    public function test_reject_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/' . Crypt::encryptString($this->transaction->id) . '/reject', [
            'supervisor_notes' => 'Needs fixing',
        ]);
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Draft, $this->transaction->fresh()->status);
    }
    
    public function test_reject_invalid_id()
    {
        $response = $this->actingAs($this->supervisor)->post('/supervisor/transactions/invalid/reject', [
            'supervisor_notes' => 'Needs fixing',
        ]);
        $response->assertStatus(404);
    }
}
