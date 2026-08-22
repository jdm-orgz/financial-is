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

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_spg_can_create_transaction_and_submit()
    {
        $this->withoutMiddleware();
        
        $user = User::factory()->create();

        $outlet = Outlet::factory()->create();
        // Link user to outlet
        $user->outlets()->attach($outlet->id, ['is_active' => '1', 'created_by' => $user->id]);

        $chair = Chair::factory()->create(['outlet_id' => $outlet->id, 'is_active' => '1']);

        // 1. Create Transaction
        $response = $this->actingAs($user)->post('/transactions', [
            'outlet_id' => Crypt::encryptString($outlet->id),
            'date' => '2026-08-01',
        ]);

        $response->assertSessionHasNoErrors();
        
        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Draft, $transaction->status);

        // 2. Add Daily Incomes
        $this->actingAs($user)->post("/transactions/" . Crypt::encryptString($transaction->id) . "/daily-incomes", [
            'incomes' => [
                [
                    'chair_id' => Crypt::encryptString($chair->id),
                    'amount' => 50000,
                ]
            ]
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transaction_daily_incomes', [
            'transaction_id' => $transaction->id,
            'chair_id' => $chair->id,
            'amount' => 50000,
        ]);

        // 3. Upload Transfer Proof (Simulated by directly inserting for test simplicity, normally multipart)
        \App\Domain\Transaction\Models\TransactionTransferProof::factory()->create([
            'transaction_id' => $transaction->id
        ]);

        // 4. Submit Transaction
        $this->actingAs($user)->post("/transactions/" . Crypt::encryptString($transaction->id) . "/submit")
            ->assertSessionHasNoErrors();

        $this->assertEquals(TransactionStatus::Approval, $transaction->fresh()->status);
    }
}
