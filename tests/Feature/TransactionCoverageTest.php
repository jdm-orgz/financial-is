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
use App\Http\Middleware\VerifyTransactionAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $action = new CalculateVarianceAction;
        $transaction = Transaction::factory()->create();
        $systemIncome = new TransactionSystemIncome(['amount' => 100]);
        $transaction->setRelation('systemIncomes', collect([$systemIncome]));

        $result = $action->execute($transaction);
        $this->assertEquals('Unknown', $result[0]['chair_name']);
    }

    public function test_transaction_repository()
    {
        $repo = new EloquentTransactionRepository;
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['name' => 'Test Outlet']);
        $transaction = Transaction::factory()->create(['created_by' => $user->id, 'outlet_id' => $outlet->id, 'status' => TransactionStatus::Draft]);

        // getPaginatedForSpg
        $repo->getPaginatedForSpg($user->id, 10, 'Test', TransactionStatus::Draft->value);
        $repo->getPaginatedForSpg($user->id, 10, null, null, 'outlet');
        $repo->getPaginatedForSpg($user->id, 10, null, null, 'time', 'desc');
        $repo->getPaginatedForSpg($user->id, 10, null, null, 'status');

        // getPaginatedForSupervisor
        $repo->getPaginatedForSupervisor($user->id, 10, 'search string');
        $repo->getPaginatedForSupervisor($user->id, 10, null, TransactionStatus::Draft->value);
        $repo->getPaginatedForSupervisor($user->id, 10, null, null, 'outlet');
        $repo->getPaginatedForSupervisor($user->id, 10, null, null, 'spg');
        $repo->getPaginatedForSupervisor($user->id, 10, null, null, 'time', 'desc');
        $repo->getPaginatedForSupervisor($user->id, 10, null, null, 'status');
        $repo->getPaginatedForSupervisor($user->id, 10, null, null); // no status
        $repo->getPaginatedForSupervisor($user->id, 10, null, 'all'); // status all
        $repo->getPaginatedForSupervisor($user->id, 10, null, ''); // empty status

        // getPaginatedForAdmin
        $repo->getPaginatedForAdmin(10, 'search string');
        $repo->getPaginatedForAdmin(10, null, TransactionStatus::Draft->value);
        $repo->getPaginatedForAdmin(10, null, 'all'); // status all
        $repo->getPaginatedForAdmin(10, null, ''); // empty status

        // existsForOutletAndDate excludeId
        $repo->existsForOutletAndDate($outlet->id, $transaction->date->format('Y-m-d'), $transaction->id);

        // Not found returns
        $this->assertFalse($repo->updateStatus('invalid-id', TransactionStatus::Approval));
        $this->assertFalse($repo->delete('invalid-id'));
    }

    public function test_other_repositories()
    {
        $dailyRepo = new EloquentTransactionDailyIncomeRepository;
        $dailyRepo->upsertForTransaction('invalid-id', []);
        $dailyRepo->deleteByTransactionId('invalid-id');
        $dailyRepo->findByTransactionId('invalid-id');

        $realizationRepo = new EloquentTransactionReplacementRealizationRepository;
        $this->assertNull($realizationRepo->findById('invalid-id'));
        $this->assertFalse($realizationRepo->update('invalid-id', []));
        $this->assertFalse($realizationRepo->delete('invalid-id'));
        $realizationRepo->findByTransactionId('invalid-id');

        $systemRepo = new EloquentTransactionSystemIncomeRepository;
        $systemRepo->upsertForTransaction('invalid-id', []);
        $systemRepo->findByTransactionId('invalid-id');

        $transferRepo = new EloquentTransactionTransferProofRepository;
        $this->assertNull($transferRepo->findById('invalid-id'));
        $this->assertFalse($transferRepo->delete('invalid-id'));
        $transferRepo->findByTransactionId('invalid-id');
    }

    public function test_verify_transaction_access_middleware()
    {
        $middleware = new VerifyTransactionAccess(new EloquentTransactionRepository);
        $request = Request::create('/test', 'GET');

        // No transactionId
        $response = $middleware->handle($request, function ($req) {
            return new Response('ok');
        }, 'spg');
        $this->assertEquals('ok', $response->getContent());

        // Invalid role context
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create();
        $transaction = Transaction::factory()->create(['created_by' => $user->id, 'outlet_id' => $outlet->id, 'status' => TransactionStatus::Draft]);

        $request = Request::create('/test/'.Crypt::encryptString($transaction->id), 'GET');
        $route = new Route('GET', '/test/{transaction}', []);
        $route->bind($request);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->actingAs($user);

        try {
            $middleware->handle($request, function ($req) {
                return new Response('ok');
            }, 'invalid');
            $this->fail('Expected abort 500');
        } catch (HttpException $e) {
            $this->assertEquals(500, $e->getStatusCode());
        }

        // expectsJson error response
        $request->headers->set('Accept', 'application/json');
        $response = $middleware->handle($request, function ($req) {
            return new Response('ok');
        }, 'spg', TransactionStatus::Approval->value);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Transaction status is invalid for this action.', json_decode($response->getContent(), true)['message']);
    }
}
