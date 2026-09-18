<?php

namespace Tests\Feature;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Lauthz\Facades\Enforcer;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Enforcer::shouldReceive('enforce')->andReturn(true);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    // =========================================================
    // super_admin role
    // =========================================================

    public function test_super_admin_can_visit_the_dashboard()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('userRole')
            ->has('totalUsers')
            ->has('totalRoles')
            ->has('totalOutlets')
            ->has('totalChairs')
            ->has('spgActiveCount')
            ->has('spgInactiveCount')
            ->has('supervisorActiveCount')
            ->has('supervisorInactiveCount')
            ->has('revenueYesterday')
            ->has('revenueToday')
            ->has('revenueChartData')
            ->has('storage')
            ->has('filters')
        );
    }

    public function test_super_admin_dashboard_with_spg_and_supervisor_roles_present()
    {
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $spgRole = Role::factory()->create(['name' => 'spg']);
        $supervisorRole = Role::factory()->create(['name' => 'supervisor']);

        $user = User::factory()->create(['role_id' => $superAdminRole->id]);

        User::factory()->create(['role_id' => $spgRole->id, 'is_active' => '1']);
        User::factory()->create(['role_id' => $spgRole->id, 'is_active' => '0']);
        User::factory()->create(['role_id' => $supervisorRole->id, 'is_active' => '1']);
        User::factory()->create(['role_id' => $supervisorRole->id, 'is_active' => '0']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('spgActiveCount', 1)
            ->where('spgInactiveCount', 1)
            ->where('supervisorActiveCount', 1)
            ->where('supervisorInactiveCount', 1)
        );
    }

    public function test_super_admin_dashboard_without_spg_or_supervisor_roles_returns_zero_counts()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        // No spg or supervisor roles exist
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('spgActiveCount', 0)
            ->where('spgInactiveCount', 0)
            ->where('supervisorActiveCount', 0)
            ->where('supervisorInactiveCount', 0)
        );
    }

    public function test_super_admin_dashboard_revenue_data()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $outlet = Outlet::factory()->create();
        $chair = Chair::factory()->create(['outlet_id' => $outlet->id]);

        // Transaction for today
        $transactionToday = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->format('Y-m-d'),
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transactionToday->id,
            'chair_id' => $chair->id,
            'amount' => 100000,
        ]);

        // Transaction for yesterday
        $transactionYesterday = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->subDay()->format('Y-m-d'),
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transactionYesterday->id,
            'chair_id' => $chair->id,
            'amount' => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('revenueToday', fn ($v) => $v == 100000)
            ->where('revenueYesterday', fn ($v) => $v == 50000)
        );
    }

    public function test_super_admin_dashboard_with_top_outlet_and_chair_data()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $outlet = Outlet::factory()->create(['name' => 'Top Outlet']);
        $chair = Chair::factory()->create(['outlet_id' => $outlet->id, 'name' => 'Chair A']);

        // Use today's date for the revenue transaction
        $transaction = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->format('Y-m-d'),
        ]);

        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair->id,
            'amount' => 200000,
        ]);

        // Use yesterday's date for the replacement realization transaction to avoid unique constraint
        $anotherChair = Chair::factory()->create(['outlet_id' => $outlet->id]);
        $transaction2 = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->subDay()->format('Y-m-d'),
        ]);

        TransactionReplacementRealization::factory()->create([
            'transaction_id' => $transaction2->id,
            'problem_chair_id' => $chair->id,
            'replacement_chair_id' => $anotherChair->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('topOutletRevenue', 'Top Outlet')
            ->where('topOutletBrokenChair', 'Top Outlet')
            ->where('topChairRevenue', 'Top Outlet - Chair A')
            ->where('topBrokenChair', 'Top Outlet - Chair A')
        );
    }

    public function test_super_admin_dashboard_returns_null_tops_when_no_transactions()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('topOutletRevenue', null)
            ->where('topOutletBrokenChair', null)
            ->where('topChairRevenue', null)
            ->where('topBrokenChair', null)
        );
    }

    public function test_super_admin_dashboard_with_custom_date_filters()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $startDate = '2025-01-01';
        $endDate = '2025-01-31';

        $response = $this->actingAs($user)->get(route('dashboard', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.start_date', $startDate)
            ->where('filters.end_date', $endDate)
        );
    }

    public function test_super_admin_dashboard_revenue_chart_data_filtered_by_date()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $outlet = Outlet::factory()->create();
        $chair = Chair::factory()->create(['outlet_id' => $outlet->id]);

        $transaction = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => '2025-01-15',
        ]);

        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transaction->id,
            'chair_id' => $chair->id,
            'amount' => 75000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('revenueChartData', 1, fn ($data) => $data
                ->where('date', '2025-01-15')
                ->where('total_revenue', fn ($v) => $v == 75000)
            )
        );
    }

    public function test_super_admin_dashboard_does_not_include_non_done_transactions_in_revenue()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $outlet = Outlet::factory()->create();
        $chair = Chair::factory()->create(['outlet_id' => $outlet->id]);

        // Draft transaction - should NOT be counted
        $draftTransaction = Transaction::factory()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->format('Y-m-d'),
            'status' => TransactionStatus::Draft,
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $draftTransaction->id,
            'chair_id' => $chair->id,
            'amount' => 999999,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('revenueToday', fn ($v) => $v == 0)
        );
    }

    public function test_super_admin_dashboard_storage_info_is_present()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('storage.total')
            ->has('storage.used')
            ->has('storage.left')
            ->has('storage.media')
            ->has('storage.system')
        );
    }

    // =========================================================
    // admin role
    // =========================================================

    public function test_admin_can_visit_the_dashboard()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('revenueYesterday')
            ->has('revenueToday')
            ->has('totalTransaction')
            ->has('transactionIncomplete')
            ->has('transactionNeedResponse')
            ->has('transactionDone')
        );
    }

    public function test_admin_dashboard_counts_transactions_correctly()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $outlet = Outlet::factory()->create();

        // Incomplete: Draft, Approval, Correction
        Transaction::factory()->create(['outlet_id' => $outlet->id, 'status' => TransactionStatus::Draft]);
        Transaction::factory()->approval()->create(['outlet_id' => $outlet->id]);
        Transaction::factory()->correction()->create(['outlet_id' => $outlet->id]);

        // Need response: Comparing, Compared
        Transaction::factory()->comparing()->create(['outlet_id' => $outlet->id]);
        Transaction::factory()->compared()->create(['outlet_id' => $outlet->id]);

        // Done
        Transaction::factory()->done()->create(['outlet_id' => $outlet->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('totalTransaction', 6)
            ->where('transactionIncomplete', 3)
            ->where('transactionNeedResponse', 2)
            ->where('transactionDone', 1)
        );
    }

    public function test_admin_dashboard_revenue_data()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $outlet = Outlet::factory()->create();
        $chair = Chair::factory()->create(['outlet_id' => $outlet->id]);

        $transactionToday = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->format('Y-m-d'),
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transactionToday->id,
            'chair_id' => $chair->id,
            'amount' => 80000,
        ]);

        $transactionYesterday = Transaction::factory()->done()->create([
            'outlet_id' => $outlet->id,
            'date' => now()->subDay()->format('Y-m-d'),
        ]);
        TransactionDailyIncome::factory()->create([
            'transaction_id' => $transactionYesterday->id,
            'chair_id' => $chair->id,
            'amount' => 40000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('revenueToday', fn ($v) => $v == 80000)
            ->where('revenueYesterday', fn ($v) => $v == 40000)
        );
    }

    // =========================================================
    // supervisor role
    // =========================================================

    public function test_supervisor_can_visit_the_dashboard()
    {
        $role = Role::factory()->create(['name' => 'supervisor']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('totalTransaction')
            ->has('transactionIncomplete')
            ->has('transactionNeedResponse')
            ->has('transactionDone')
        );
    }

    public function test_supervisor_dashboard_only_shows_their_outlet_transactions()
    {
        $role = Role::factory()->create(['name' => 'supervisor']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $linkedOutlet = Outlet::factory()->create();
        $otherOutlet = Outlet::factory()->create();

        $user->outlets()->attach($linkedOutlet->id, ['is_active' => '1', 'created_by' => $user->id]);

        // Linked outlet transactions
        Transaction::factory()->create(['outlet_id' => $linkedOutlet->id, 'status' => TransactionStatus::Draft]);
        Transaction::factory()->create(['outlet_id' => $linkedOutlet->id, 'status' => TransactionStatus::Correction]);
        Transaction::factory()->comparing()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->compared()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->approval()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->done()->create(['outlet_id' => $linkedOutlet->id]);

        // Other outlet transaction - should NOT be counted
        Transaction::factory()->done()->create(['outlet_id' => $otherOutlet->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('totalTransaction', 6)
            ->where('transactionIncomplete', 4) // Draft, Correction, Comparing, Compared
            ->where('transactionNeedResponse', 1) // Approval
            ->where('transactionDone', 1)
        );
    }

    // =========================================================
    // spg role
    // =========================================================

    public function test_spg_can_visit_the_dashboard()
    {
        $role = Role::factory()->create(['name' => 'spg']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('totalTransaction')
            ->has('transactionIncomplete')
            ->has('transactionNeedResponse')
            ->has('transactionDone')
        );
    }

    public function test_spg_dashboard_only_shows_their_outlet_transactions()
    {
        $role = Role::factory()->create(['name' => 'spg']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $linkedOutlet = Outlet::factory()->create();
        $otherOutlet = Outlet::factory()->create();

        $user->outlets()->attach($linkedOutlet->id, ['is_active' => '1', 'created_by' => $user->id]);

        // Linked outlet transactions
        Transaction::factory()->approval()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->comparing()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->compared()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->create(['outlet_id' => $linkedOutlet->id, 'status' => TransactionStatus::Draft]);
        Transaction::factory()->correction()->create(['outlet_id' => $linkedOutlet->id]);
        Transaction::factory()->done()->create(['outlet_id' => $linkedOutlet->id]);

        // Other outlet transaction - should NOT be counted
        Transaction::factory()->done()->create(['outlet_id' => $otherOutlet->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('totalTransaction', 6)
            ->where('transactionIncomplete', 3) // Approval, Comparing, Compared
            ->where('transactionNeedResponse', 2) // Draft, Correction
            ->where('transactionDone', 1)
        );
    }

    // =========================================================
    // getDirectorySize - covers the foreach branch (directory exists with files)
    // =========================================================

    public function test_get_directory_size_when_proofs_directory_does_not_exist()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Ensure the proofs directory does not exist
        $proofsPath = storage_path('app/public/proofs');
        if (File::isDirectory($proofsPath)) {
            File::deleteDirectory($proofsPath);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('storage.media', fn ($value) => $value == 0)
        );
    }

    public function test_get_directory_size_with_existing_directory_and_files()
    {
        $role = Role::factory()->create(['name' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Create the proofs directory and a dummy file inside it
        $proofsPath = storage_path('app/public/proofs');
        File::ensureDirectoryExists($proofsPath);
        $dummyFile = $proofsPath.'/test_coverage_dummy.txt';
        File::put($dummyFile, 'coverage');

        try {
            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->where('storage.media', fn ($value) => $value > 0)
            );
        } finally {
            File::delete($dummyFile);
        }
    }
}
