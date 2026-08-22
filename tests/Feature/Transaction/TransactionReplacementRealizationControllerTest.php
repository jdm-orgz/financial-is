<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\UserAccess\Models\User;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionReplacementRealizationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->user = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->chair1 = Chair::factory()->create(['outlet_id' => $this->outlet->id]);
        $this->chair2 = Chair::factory()->create(['outlet_id' => $this->outlet->id]);
        $this->transaction = Transaction::factory()->create([
            'created_by' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Draft,
        ]);
        Storage::fake('public');
    }

    public function test_store_success()
    {
        $response = $this->actingAs($this->user)->post('/transactions/'.Crypt::encryptString($this->transaction->id).'/replacement-realizations', [
            'problem_chair_id' => Crypt::encryptString($this->chair1->id),
            'replacement_chair_id' => Crypt::encryptString($this->chair2->id),
            'payment_method' => PaymentMethod::Cash->value,
            'amount' => 50000,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
            'proof_video' => UploadedFile::fake()->create('proof.mp4', 100, 'video/mp4'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaction_replacement_realizations', [
            'transaction_id' => $this->transaction->id,
            'problem_chair_id' => $this->chair1->id,
        ]);
    }

    public function test_store_invalid_transaction()
    {
        $payload = [
            'problem_chair_id' => Crypt::encryptString($this->chair1->id),
            'replacement_chair_id' => Crypt::encryptString($this->chair2->id),
            'payment_method' => PaymentMethod::Cash->value,
            'amount' => 50000,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
            'proof_video' => UploadedFile::fake()->create('proof.mp4', 100, 'video/mp4'),
        ];
        $response = $this->actingAs($this->user)->post('/transactions/invalid/replacement-realizations', $payload);
        $response->assertStatus(404);

        $response = $this->actingAs($this->user)->post('/transactions/'.Crypt::encryptString(999).'/replacement-realizations', $payload);
        $response->assertStatus(404);
    }

    public function test_store_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Approval]);
        $response = $this->actingAs($this->user)->post('/transactions/'.Crypt::encryptString($this->transaction->id).'/replacement-realizations', []);
        $response->assertRedirect();
    }

    public function test_update_success()
    {
        $realization = TransactionReplacementRealization::factory()->create([
            'transaction_id' => $this->transaction->id,
            'problem_chair_id' => $this->chair1->id,
            'replacement_chair_id' => $this->chair2->id,
            'proof_image_path' => 'old_image.jpg',
            'proof_video_path' => 'old_video.mp4',
        ]);

        $response = $this->actingAs($this->user)->put(
            '/transactions/'.Crypt::encryptString($this->transaction->id).'/replacement-realizations/'.Crypt::encryptString($realization->id),
            [
                'problem_chair_id' => Crypt::encryptString($this->chair2->id),
                'amount' => 100000,
                'proof_image' => UploadedFile::fake()->image('new.jpg'),
                'proof_video' => UploadedFile::fake()->create('new.mp4', 100, 'video/mp4'),
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('transaction_replacement_realizations', [
            'id' => $realization->id,
            'problem_chair_id' => $this->chair2->id,
            'amount' => 100000,
        ]);
    }

    public function test_update_invalid_ids()
    {
        $response = $this->actingAs($this->user)->put('/transactions/invalid/replacement-realizations/invalid', []);
        $response->assertStatus(404);
    }

    public function test_destroy_success()
    {
        $realization = TransactionReplacementRealization::factory()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/transactions/'.Crypt::encryptString($this->transaction->id).'/replacement-realizations/'.Crypt::encryptString($realization->id)
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('transaction_replacement_realizations', ['id' => $realization->id]);
    }

    public function test_destroy_invalid_ids()
    {
        $response = $this->actingAs($this->user)->delete('/transactions/invalid/replacement-realizations/invalid');
        $response->assertStatus(404);
    }

    public function test_store_invalid_chair_id()
    {
        $response = $this->actingAs($this->user)->post('/transactions/'.Crypt::encryptString($this->transaction->id).'/replacement-realizations', [
            'problem_chair_id' => 'invalid-id',
            'replacement_chair_id' => 'invalid-id',
            'replacement_date' => '2026-08-01',
            'payment_method' => PaymentMethod::Cash->value,
            'amount' => 50000,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
            'proof_video' => UploadedFile::fake()->create('proof.mp4', 100, 'video/mp4'),
        ]);

        $response->assertStatus(404);
    }
}
