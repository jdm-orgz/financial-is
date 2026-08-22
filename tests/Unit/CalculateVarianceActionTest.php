<?php

namespace Tests\Unit\Actions;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Actions\CalculateVarianceAction;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\Transaction\Models\TransactionSystemIncome;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateVarianceActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_variance_calculation_with_replacements()
    {
        $outlet = Outlet::factory()->create();
        $chair1 = Chair::factory()->create(['outlet_id' => $outlet->id, 'name' => 'Chair 1']);
        $chair2 = Chair::factory()->create(['outlet_id' => $outlet->id, 'name' => 'Chair 2']);

        $transaction = Transaction::factory()->create([
            'outlet_id' => $outlet->id,
            'status' => TransactionStatus::Comparing,
        ]);

        // SPG incomes
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair1->id,
            'amount' => 50000,
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair2->id,
            'amount' => 30000,
        ]);

        // System incomes
        TransactionSystemIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair1->id,
            'amount' => 60000,
        ]);
        TransactionSystemIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair2->id,
            'amount' => 30000,
        ]);

        // Replacement realization (Chair 1 broke, spent 10000)
        TransactionReplacementRealization::factory()->create([
            'transaction_id' => $transaction->id,
            'problem_chair_id' => $chair1->id,
            'replacement_chair_id' => $chair2->id,
            'payment_method' => PaymentMethod::Cash,
            'amount' => 10000,
        ]);

        $action = new CalculateVarianceAction;
        $result = $action->execute($transaction);

        // Expected Chair 1:
        // System: 60000
        // Replace: 10000
        // Sys Adj: 50000
        // SPG: 50000
        // Var: 0 (ok)

        // Expected Chair 2:
        // System: 30000
        // Replace: 0
        // Sys Adj: 30000
        // SPG: 30000
        // Var: 0 (ok)

        $this->assertCount(2, $result);
        $chair1Result = collect($result)->firstWhere('chair_id', $chair1->id);

        $this->assertEquals(60000, $chair1Result['system_amount']);
        $this->assertEquals(10000, $chair1Result['replacement_total']);
        $this->assertEquals(50000, $chair1Result['system_adjusted']);
        $this->assertEquals(50000, $chair1Result['spg_amount']);
        $this->assertEquals(0, $chair1Result['variance']);
        $this->assertEquals('ok', $chair1Result['status']);

        $chair2Result = collect($result)->firstWhere('chair_id', $chair2->id);
        $this->assertEquals(0, $chair2Result['variance']);
    }
}
