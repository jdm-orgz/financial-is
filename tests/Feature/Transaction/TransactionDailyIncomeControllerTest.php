<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TransactionDailyIncomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->user = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->chair = Chair::factory()->create(['outlet_id' => $this->outlet->id]);
        $this->transaction = Transaction::factory()->create([
            'created_by' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Draft,
        ]);
    }

    public function test_upsert_success()
    {
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/daily-incomes', [
            'incomes' => [
                [
                    'chair_id' => Crypt::encryptString($this->chair->id),
                    'amount' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaction_daily_incomes', [
            'transaction_id' => $this->transaction->id,
            'chair_id' => $this->chair->id,
            'amount' => 50000,
        ]);
    }

    public function test_upsert_invalid_transaction()
    {
        $payload = [
            'incomes' => [
                [
                    'chair_id' => Crypt::encryptString($this->chair->id),
                    'amount' => 50000,
                ]
            ]
        ];
        $response = $this->actingAs($this->user)->post('/transactions/invalid/daily-incomes', $payload);
        $response->assertStatus(404);
        
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString(999) . '/daily-incomes', $payload);
        $response->assertStatus(404);
    }

    public function test_upsert_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Approval]);
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/daily-incomes', []);
        $response->assertRedirect();
    }
    
    public function test_upsert_invalid_chair_id()
    {
        $response = $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/daily-incomes', [
            'incomes' => [
                [
                    'chair_id' => 'invalid-chair-id',
                    'amount' => 50000,
                ]
            ]
        ]);
        $response->assertStatus(404);
    }
}
