<?php

namespace Tests\Feature\Transaction;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Lauthz\Facades\Enforcer;
use Tests\TestCase;

class AdminTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Enforcer::shouldReceive('enforce')->andReturn(true);
        $this->admin = User::factory()->create();
        $this->outlet = Outlet::factory()->create();
        $this->chair = Chair::factory()->create(['outlet_id' => $this->outlet->id]);

        $this->transaction = Transaction::factory()->create([
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Comparing,
        ]);
    }

    public function test_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions');
        $response->assertStatus(200);
    }

    public function test_index_with_filters()
    {
        $spgUser = User::factory()->create(['username' => 'spg123']);
        $supervisorUser = User::factory()->create(['username' => 'super123']);

        $response = $this->actingAs($this->admin)->get('/admin/transactions?spg_username=spg123&supervisor_username=super123&start_date=2026-09-01&end_date=2026-09-30');
        $response->assertStatus(200);

        // Test non-existent users
        $response = $this->actingAs($this->admin)->get('/admin/transactions?spg_username=nonexistent&supervisor_username=nonexistent');
        $response->assertStatus(200);
    }

    public function test_show_compare()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/compare');
        $response->assertStatus(200);
    }

    public function test_show_compare_invalid_id()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions/invalid/compare');
        $response->assertStatus(404);
    }

    public function test_show_compare_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->admin)->get('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/compare');
        $response->assertRedirect()->assertSessionHas('toast');
    }

    public function test_store_system_income_success()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/system-incomes', [
            'system_incomes' => [
                [
                    'chair_id' => Crypt::encryptString($this->chair->id),
                    'amount' => 100000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Compared, $this->transaction->fresh()->status);
        $this->assertDatabaseHas('transaction_system_incomes', [
            'transaction_id' => $this->transaction->id,
            'chair_id' => $this->chair->id,
            'amount' => 100000,
        ]);
    }

    public function test_store_system_income_invalid_status()
    {
        $this->transaction->update(['status' => TransactionStatus::Draft]);
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/system-incomes', [
            'system_incomes' => [],
        ]);

        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Draft, $this->transaction->fresh()->status);
    }

    public function test_store_system_income_invalid_chair_id()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/system-incomes', [
            'system_incomes' => [
                [
                    'chair_id' => 'invalid',
                    'amount' => 100000,
                ],
            ],
        ]);

        $response->assertStatus(404);
    }

    public function test_show_result()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/result');
        $response->assertStatus(200);
    }

    public function test_show_result_invalid_id()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions/invalid/result');
        $response->assertStatus(404);
    }

    public function test_approve_success()
    {
        $this->transaction->update(['status' => TransactionStatus::Compared]);
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/approve');
        $response->assertRedirect('/admin/transactions');
        $this->assertEquals(TransactionStatus::Done, $this->transaction->fresh()->status);
    }

    public function test_approve_invalid_status()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/approve');
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Comparing, $this->transaction->fresh()->status);
    }

    public function test_approve_invalid_id()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/invalid/approve');
        $response->assertStatus(404);
    }

    public function test_reject_success()
    {
        $this->transaction->update(['status' => TransactionStatus::Compared]);
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/reject', [
            'admin_notes' => 'Incorrect income',
        ]);
        $response->assertRedirect('/admin/transactions');
        $this->assertEquals(TransactionStatus::Correction, $this->transaction->fresh()->status);
        $this->assertEquals('Incorrect income', $this->transaction->fresh()->admin_notes);
    }

    public function test_reject_invalid_status()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/'.Crypt::encryptString($this->transaction->id).'/reject', [
            'admin_notes' => 'Incorrect income',
        ]);
        $response->assertRedirect();
        $this->assertEquals(TransactionStatus::Comparing, $this->transaction->fresh()->status);
    }

    public function test_reject_invalid_id()
    {
        $response = $this->actingAs($this->admin)->post('/admin/transactions/invalid/reject', [
            'admin_notes' => 'Incorrect income',
        ]);
        $response->assertStatus(404);
    }
}
