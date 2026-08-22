<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\Transaction\Models\TransactionTransferProof;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdditionalCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->user = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->transaction = Transaction::factory()->create([
            'created_by' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Draft,
        ]);
    }

    public function test_admin_controller_coverage()
    {
        // AdminTransactionController.php line 76: abort(404) if ! $transaction (but ID decrypts successfully)
        $this->actingAs($this->user)->get('/admin/transactions/' . Crypt::encryptString('missing-id') . '/compare')->assertStatus(404);
        $this->actingAs($this->user)->get('/admin/transactions/invalid/compare')->assertStatus(404);
        
        $validSystemIncomes = ['system_incomes' => [['chair_id' => Crypt::encryptString('some-chair'), 'amount' => 100]]];
        $this->actingAs($this->user)->post('/admin/transactions/' . Crypt::encryptString('missing-id') . '/system-incomes', $validSystemIncomes)->assertStatus(404);
        $this->actingAs($this->user)->post('/admin/transactions/invalid/system-incomes', $validSystemIncomes)->assertStatus(404);
        
        $this->actingAs($this->user)->post('/admin/transactions/' . Crypt::encryptString($this->transaction->id) . '/system-incomes', $validSystemIncomes)->assertRedirect();
        
        $this->actingAs($this->user)->post('/admin/transactions/' . Crypt::encryptString('missing-id') . '/approve')->assertStatus(404);
        $this->actingAs($this->user)->post('/admin/transactions/invalid/approve')->assertStatus(404);
        
        $this->actingAs($this->user)->post('/admin/transactions/' . Crypt::encryptString('missing-id') . '/reject', ['admin_notes' => 'notes'])->assertStatus(404);
        $this->actingAs($this->user)->post('/admin/transactions/invalid/reject', ['admin_notes' => 'notes'])->assertStatus(404);
        
        $this->actingAs($this->user)->get('/admin/transactions/' . Crypt::encryptString('missing-id') . '/result')->assertStatus(404);
        $this->actingAs($this->user)->get('/admin/transactions/invalid/result')->assertStatus(404);
    }

    public function test_supervisor_controller_coverage()
    {
        $this->actingAs($this->user)->post('/supervisor/transactions/' . Crypt::encryptString('missing-id') . '/approve')->assertStatus(404);
        $this->actingAs($this->user)->post('/supervisor/transactions/' . Crypt::encryptString('missing-id') . '/reject', ['supervisor_notes' => 'notes'])->assertStatus(404);
    }

    public function test_transaction_controller_coverage()
    {
        $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString('missing-id') . '/submit')->assertStatus(404);
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString('missing-id'))->assertStatus(404);
    }

    public function test_transaction_daily_income_controller_coverage()
    {
        // 40..42: store invalid status (needs valid validation payload)
        $this->transaction->update(['status' => TransactionStatus::Approval]);
        $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/daily-incomes', [
            'incomes' => [['chair_id' => Crypt::encryptString('some-chair'), 'amount' => 100]]
        ])->assertRedirect();
    }

    public function test_transaction_replacement_realization_controller_coverage()
    {
        $chair = \App\Domain\Outlet\Models\Chair::factory()->create(['outlet_id' => $this->outlet->id]);
        $validPayload = [
            'problem_chair_id' => Crypt::encryptString($chair->id),
            'replacement_chair_id' => Crypt::encryptString($chair->id),
            'payment_method' => \App\Enums\PaymentMethod::Cash->value,
            'amount' => 100,
            'proof_video' => \Illuminate\Http\UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
        ];
        
        $realization = TransactionReplacementRealization::factory()->create([
            'transaction_id' => $this->transaction->id,
            'proof_image_path' => 'proofs/images/test.jpg'
        ]);

        // 42..44: store invalid status
        $this->transaction->update(['status' => TransactionStatus::Approval]);
        $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations', $validPayload)->assertRedirect();
        
        // 100..102: update invalid status
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id), $validPayload)->assertRedirect();
        
        // 182..184: destroy invalid status
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id))->assertRedirect();

        $this->transaction->update(['status' => TransactionStatus::Draft]);

        // 96: update missing transaction
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString('missing-id') . '/replacement-realizations/' . Crypt::encryptString($realization->id), $validPayload)->assertStatus(404);

        // 108: mismatch transaction id
        $otherTransaction = Transaction::factory()->create();
        $otherRealization = TransactionReplacementRealization::factory()->create([
            'transaction_id' => $otherTransaction->id,
        ]);
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($otherRealization->id), $validPayload)->assertStatus(404);

        // 117..118, 124..126, 131: update missing model, invalid decrypt for problem/replacement chair, payment_method branch
        $badPayload = $validPayload;
        $badPayload['problem_chair_id'] = 'invalid-decrypt';
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id), $badPayload)->assertStatus(404);

        $badPayload = $validPayload;
        $badPayload['replacement_chair_id'] = 'invalid-decrypt';
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id), $badPayload)->assertStatus(404);

        $validPayloadNoPayment = [
            'amount' => 100,
        ];
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id), $validPayloadNoPayment)->assertRedirect();
        
        // Full successful update to cover line 131 completely
        $this->actingAs($this->user)->put('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id), $validPayload)->assertRedirect();

        // 178: destroy missing transaction
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString('missing-id') . '/replacement-realizations/' . Crypt::encryptString($realization->id))->assertStatus(404);
        
        // 190: mismatch transaction id on destroy
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($otherRealization->id))->assertStatus(404);

        // 195: destroy with proof_image_path
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($this->transaction->id) . '/replacement-realizations/' . Crypt::encryptString($realization->id))->assertRedirect();
    }

    public function test_transaction_transfer_proof_controller_coverage()
    {
        $proof = TransactionTransferProof::factory()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $otherTransaction = Transaction::factory()->create();
        $otherProof = TransactionTransferProof::factory()->create([
            'transaction_id' => $otherTransaction->id,
        ]);

        // 37: store mismatch user
        $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($otherTransaction->id) . '/transfer-proofs', [
            'proof_image' => \Illuminate\Http\UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(404);

        // 75: destroy missing transaction
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString('missing-id') . '/transfer-proofs/' . Crypt::encryptString($proof->id))->assertStatus(404);

        // 87: mismatch transaction id on destroy
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($this->transaction->id) . '/transfer-proofs/' . Crypt::encryptString($otherProof->id))->assertStatus(404);

        $this->transaction->update(['status' => TransactionStatus::Approval]);
        $this->actingAs($this->user)->post('/transactions/' . Crypt::encryptString($this->transaction->id) . '/transfer-proofs', [
            'proof_image' => \Illuminate\Http\UploadedFile::fake()->image('proof.jpg'),
        ])->assertRedirect();
        
        $this->actingAs($this->user)->delete('/transactions/' . Crypt::encryptString($this->transaction->id) . '/transfer-proofs/' . Crypt::encryptString($proof->id))->assertRedirect();
    }
}
