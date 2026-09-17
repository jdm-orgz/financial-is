<?php

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Http\Controllers\Master\AppConfigController;
use App\Http\Controllers\Master\ChairController;
use App\Http\Controllers\Master\LinkedOutletUserController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Transaction\AdminTransactionController;
use App\Http\Controllers\Transaction\SupervisorTransactionController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Transaction\TransactionDailyIncomeController;
use App\Http\Controllers\Transaction\TransactionReplacementRealizationController;
use App\Http\Controllers\Transaction\TransactionTransferProofController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return inertia('dashboard', [
            'totalUsers' => User::count(),
            'totalRoles' => Role::count(),
            'totalOutlets' => Outlet::count(),
            'totalChairs' => Chair::count(),
        ]);
    })->name('dashboard');

    Route::middleware(['permission:master/*,*'])->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::patch('roles/{role}/status', [RoleController::class, 'updateStatus'])->name('roles.status.update');

        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');

        Route::resource('outlets', OutletController::class)->except(['show']);
        Route::patch('outlets/{outlet}/status', [OutletController::class, 'updateStatus'])->name('outlets.status.update');
        Route::resource('outlets.chairs', ChairController::class)->except(['show', 'index']);
        Route::post('outlets/{outlet}/chairs/bulk', [ChairController::class, 'storeBulk'])->name('outlets.chairs.bulk');
        Route::get('outlets/{outlet}/chairs', [ChairController::class, 'index'])->name('outlets.chairs.index');
        Route::patch('outlets/{outlet}/chairs/{chair}/status', [ChairController::class, 'updateStatus'])->name('outlets.chairs.status.update');
    });

    Route::middleware(['permission:configuration/*,*'])->group(function () {
        Route::resource('linked-outlet-users', LinkedOutletUserController::class)->except(['show']);
        Route::patch('linked-outlet-users/{linked_outlet_user}/status', [LinkedOutletUserController::class, 'updateStatus'])->name('linked-outlet-users.status.update');
    });

    Route::middleware(['permission:app-config/*,*'])->group(function () {
        Route::get('app-config', [AppConfigController::class, 'edit'])->name('app-config.edit');
        Route::post('app-config', [AppConfigController::class, 'update'])->name('app-config.update');
    });

    // SPG Transaction Routes
    Route::middleware(['permission:transaction/management/*,*'])->group(function () {
        Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store']);

        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])
            ->name('transactions.show')
            ->middleware('transaction.access:spg');

        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])
            ->name('transactions.destroy')
            ->middleware('transaction.access:spg,draft');

        Route::post('transactions/{transaction}/submit', [TransactionController::class, 'submit'])
            ->name('transactions.submit')
            ->middleware('transaction.access:spg,draft,correction');

        Route::post('transactions/{transaction}/daily-incomes', [TransactionDailyIncomeController::class, 'upsert'])
            ->name('transactions.daily-incomes.upsert')
            ->middleware('transaction.access:spg,draft,correction');

        Route::resource('transactions.replacement-realizations', TransactionReplacementRealizationController::class)
            ->except(['index', 'show', 'edit', 'create'])
            ->middleware('transaction.access:spg,draft,correction');

        Route::delete('transactions/{transaction}/replacement-realizations/{replacement_realization}/proof/{type}', [TransactionReplacementRealizationController::class, 'destroyProof'])
            ->name('transactions.replacement-realizations.destroy-proof')
            ->middleware('transaction.access:spg,draft,correction');

        Route::resource('transactions.transfer-proofs', TransactionTransferProofController::class)
            ->only(['store', 'destroy'])
            ->middleware('transaction.access:spg,draft,correction');
    });

    // Supervisor Transaction Routes
    Route::middleware(['permission:transaction/approval/spv/*,*'])->group(function () {
        Route::get('supervisor/transactions', [SupervisorTransactionController::class, 'index'])
            ->name('supervisor.transactions.index');
        Route::get('supervisor/transactions/{transaction}', [SupervisorTransactionController::class, 'show'])
            ->name('supervisor.transactions.show')
            ->middleware('transaction.access:spv,approval,comparing,compared,done');
        Route::post('supervisor/transactions/{transaction}/approve', [SupervisorTransactionController::class, 'approve'])
            ->name('supervisor.transactions.approve')
            ->middleware('transaction.access:spv,approval');
        Route::post('supervisor/transactions/{transaction}/reject', [SupervisorTransactionController::class, 'reject'])
            ->name('supervisor.transactions.reject')
            ->middleware('transaction.access:spv,approval');
    });

    // Admin Transaction Routes
    Route::middleware(['permission:transaction/approval/admin/*,*'])->group(function () {
        Route::get('admin/transactions', [AdminTransactionController::class, 'index'])
            ->name('admin.transactions.index');
        Route::get('admin/transactions/{transaction}/compare', [AdminTransactionController::class, 'showCompare'])
            ->name('admin.transactions.compare')
            ->middleware('transaction.access:admin,comparing');
        Route::post('admin/transactions/{transaction}/system-incomes', [AdminTransactionController::class, 'storeSystemIncome'])
            ->name('admin.transactions.system-incomes.store')
            ->middleware('transaction.access:admin,comparing');
        Route::get('admin/transactions/{transaction}/result', [AdminTransactionController::class, 'showResult'])
            ->name('admin.transactions.result')
            ->middleware('transaction.access:admin,comparing,compared,done');
        Route::post('admin/transactions/{transaction}/approve', [AdminTransactionController::class, 'approve'])
            ->name('admin.transactions.approve')
            ->middleware('transaction.access:admin,compared');
        Route::post('admin/transactions/{transaction}/reject', [AdminTransactionController::class, 'reject'])
            ->name('admin.transactions.reject')
            ->middleware('transaction.access:admin,compared');
    });

});

require __DIR__.'/settings.php';
