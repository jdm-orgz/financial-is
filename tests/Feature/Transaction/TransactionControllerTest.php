<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use App\Domain\Transaction\Models\TransactionTransferProof;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->user = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->user->outlets()->attach($this->outlet->id, ['is_active' => '1', 'created_by' => $this->user->id]);
        $this->chair = Chair::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => '1']);
    }

    public function test_index()
    {
        Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id]);
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_create()
    {
        $response = $this->actingAs($this->user)->get('/transactions/create');
        $response->assertStatus(200);
    }

    public function test_store_invalid_id()
    {
        $response = $this->actingAs($this->user)->post('/transactions', [
            'outlet_id' => 'invalid-id',
            'date' => '2026-08-01',
        ]);
        $response->assertStatus(404);
    }

    public function test_store_duplicate_date()
    {
        $this->actingAs($this->user)->post('/transactions', [
            'outlet_id' => Crypt::encryptString($this->outlet->id),
            'date' => '2026-08-01',
        ]);
        $response = $this->actingAs($this->user)->post('/transactions', [
            'outlet_id' => Crypt::encryptString($this->outlet->id),
            'date' => '2026-08-01',
        ]);
        $response->assertRedirect();
        $this->assertEquals(1, Transaction::count());
    }

    public function test_show()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id]);
        $response = $this->actingAs($this->user)->get('/transactions/' . Crypt::encryptString($transaction->id));
        $response->assertStatus(200);
    }

    public function test_show_invalid_id()
    {
        $response = $this->actingAs($this->user)->get('/transactions/invalid');
        $response->assertStatus(404);
        
        $response = $this->actingAs($this->user)->get('/transactions/' . Crypt::encryptString(9999));
        $response->assertStatus(404);
    }

    public function test_submit_invalid_status()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Approval]);
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($transaction->id) . '/submit');
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Approval, $transaction->fresh()->status);
    }

    public function test_submit_missing_chairs()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($transaction->id) . '/submit');
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Draft, $transaction->fresh()->status);
    }

    public function test_submit_missing_transfer_proof()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Draft]);
        TransactionDailyIncome::factory()->create(['transaction_id' => $transaction->id, 'chair_id' => $this->chair->id]);
        
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($transaction->id) . '/submit');
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Draft, $transaction->fresh()->status);
    }

    public function test_submit_success()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Draft]);
        TransactionDailyIncome::factory()->create(['transaction_id' => $transaction->id, 'chair_id' => $this->chair->id]);
        TransactionTransferProof::factory()->create(['transaction_id' => $transaction->id]);
        
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($transaction->id) . '/submit');
        $response->assertRedirect('/transactions');
        $this->assertEquals(TransactionStatus::Approval, $transaction->fresh()->status);
    }
    
    public function test_submit_invalid_id()
    {
        $response = $this->actingAs($this->user)->post('/transactions/invalid/submit');
        $response->assertStatus(404);
    }

    public function test_destroy_invalid_status()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Approval]);
        $response = $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($transaction->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_destroy_success()
    {
        $transaction = Transaction::factory()->create(['created_by' => $this->user->id, 'outlet_id' => $this->outlet->id, 'status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($transaction->id));
        $response->assertRedirect('/transactions');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }
    
    public function test_destroy_invalid_id()
    {
        $response = $this->actingAs($this->user)->delete('/transactions/invalid');
        $response->assertStatus(404);
    }
}
