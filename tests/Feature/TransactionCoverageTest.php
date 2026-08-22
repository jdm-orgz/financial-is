<?php

namespace Tests\Feature;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Actions\CalculateVarianceAction;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\Transaction\Models\TransactionSystemIncome;
use App\Domain\Transaction\Models\TransactionTransferProof;
use App\Domain\Transaction\Repositories\EloquentTransactionDailyIncomeRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionReplacementRealizationRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionSystemIncomeRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionTransferProofRepository;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_relations()
    {
        $transaction = Transaction::factory()->create();
        
        $dailyIncome = TransactionDailyIncome::factory()->create(['transaction_id' => $transaction->id]);
        $this->assertInstanceOf(Transaction::class, $dailyIncome->transaction);
        $this->assertNotNull($dailyIncome->chair);

        $realization = TransactionReplacementRealization::factory()->create(['transaction_id' => $transaction->id]);
        $this->assertInstanceOf(Transaction::class, $realization->transaction);

        $systemIncome = TransactionSystemIncome::factory()->create(['transaction_id' => $transaction->id]);
        $this->assertInstanceOf(Transaction::class, $systemIncome->transaction);
        $this->assertNotNull($systemIncome->chair);

        $proof = TransactionTransferProof::factory()->create(['transaction_id' => $transaction->id]);
        $this->assertInstanceOf(Transaction::class, $proof->transaction);
    }

    public function test_calculate_variance_unknown_chair()
    {
        $action = new CalculateVarianceAction();
        $transaction = Transaction::factory()->create();
        $systemIncome = new TransactionSystemIncome(['amount' => 100]);
        $transaction->setRelation('systemIncomes', collect([$systemIncome]));
        
        $result = $action->execute($transaction);
        $this->assertEquals('Unknown', $result[0]['chair_name']);
    }

    public function test_transaction_repository()
    {
        $repo = new EloquentTransactionRepository();
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['name' => 'Test Outlet']);
        $transaction = Transaction::factory()->create(['created_by' => $user->id, 'outlet_id' => $outlet->id, 'status' => TransactionStatus::Draft]);

        // getPaginatedForSpg
        $repo->getPaginatedForSpg($user->id, 10, 'Test', TransactionStatus::Draft->value);
        
        // getPaginatedForSupervisor
        $repo->getPaginatedForSupervisor($user->id, 10, TransactionStatus::Draft->value);

        // getPaginatedForAdmin
        $repo->getPaginatedForAdmin(10, TransactionStatus::Draft->value);

        // getPaginatedAll
        $repo->getPaginatedAll(10, 'Test', TransactionStatus::Draft->value);

        // existsForOutletAndDate excludeId
        $repo->existsForOutletAndDate($outlet->id, $transaction->date->format('Y-m-d'), $transaction->id);

        // Not found returns
        $this->assertFalse($repo->updateStatus('invalid-id', TransactionStatus::Approval));
        $this->assertFalse($repo->delete('invalid-id'));
    }

    public function test_other_repositories()
    {
        $dailyRepo = new EloquentTransactionDailyIncomeRepository();
        $dailyRepo->upsertForTransaction('invalid-id', []);
        $dailyRepo->deleteByTransactionId('invalid-id');
        $dailyRepo->findByTransactionId('invalid-id');

        $realizationRepo = new EloquentTransactionReplacementRealizationRepository();
        $this->assertNull($realizationRepo->findById('invalid-id'));
        $this->assertFalse($realizationRepo->update('invalid-id', []));
        $this->assertFalse($realizationRepo->delete('invalid-id'));
        $realizationRepo->findByTransactionId('invalid-id');

        $systemRepo = new EloquentTransactionSystemIncomeRepository();
        $systemRepo->upsertForTransaction('invalid-id', []);
        $systemRepo->findByTransactionId('invalid-id');

        $transferRepo = new EloquentTransactionTransferProofRepository();
        $this->assertNull($transferRepo->findById('invalid-id'));
        $this->assertFalse($transferRepo->delete('invalid-id'));
        $transferRepo->findByTransactionId('invalid-id');
    }
}
