<?php

namespace Tests\Feature\Transaction;

use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionTransferProof;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionTransferProofControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->user = User::factory()->create();
        $this->transaction = Transaction::factory()->create([
            'created_by' => $this->user->id,
            'status' => TransactionStatus::Draft,
        ]);
        Storage::fake('public');
    }

    public function test_store_success()
    {
        $response = $this->actingAs($this->user)->post('/transactions/'.Crypt::encryptString($this->transaction->id).'/transfer-proofs', [
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaction_transfer_proofs', [
            'transaction_id' => $this->transaction->id,
        ]);
    }

    public function test_store_invalid_transaction()
    {
        $response = $this->actingAs($this->user)->post('/transactions/invalid/transfer-proofs', [
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $response->assertStatus(404);
    }

    public function test_destroy_success()
    {
        $proof = TransactionTransferProof::factory()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/transactions/'.Crypt::encryptString($this->transaction->id).'/transfer-proofs/'.Crypt::encryptString($proof->id)
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('transaction_transfer_proofs', ['id' => $proof->id]);
    }

    public function test_destroy_invalid_ids()
    {
        $response = $this->actingAs($this->user)->delete('/transactions/invalid/transfer-proofs/invalid');
        $response->assertStatus(404);
    }
}
