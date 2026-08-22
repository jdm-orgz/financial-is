<?php

namespace App\Providers;

use App\Domain\Outlet\Repositories\ChairRepositoryInterface;
use App\Domain\Outlet\Repositories\EloquentChairRepository;
use App\Domain\Outlet\Repositories\EloquentLinkedOutletUserRepository;
use App\Domain\Outlet\Repositories\EloquentOutletRepository;
use App\Domain\Outlet\Repositories\LinkedOutletUserRepositoryInterface;
use App\Domain\Outlet\Repositories\OutletRepositoryInterface;
use App\Domain\Transaction\Repositories\EloquentTransactionDailyIncomeRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionReplacementRealizationRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionSystemIncomeRepository;
use App\Domain\Transaction\Repositories\EloquentTransactionTransferProofRepository;
use App\Domain\Transaction\Repositories\TransactionDailyIncomeRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionReplacementRealizationRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionSystemIncomeRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionTransferProofRepositoryInterface;
use App\Domain\UserAccess\Repositories\EloquentRoleRepository;
use App\Domain\UserAccess\Repositories\EloquentUserRepository;
use App\Domain\UserAccess\Repositories\RoleRepositoryInterface;
use App\Domain\UserAccess\Repositories\UserRepositoryInterface;
use App\Listeners\AuthEventSubscriber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            EloquentRoleRepository::class
        );

        $this->app->bind(
            OutletRepositoryInterface::class,
            EloquentOutletRepository::class
        );

        $this->app->bind(
            LinkedOutletUserRepositoryInterface::class,
            EloquentLinkedOutletUserRepository::class
        );

        $this->app->bind(
            ChairRepositoryInterface::class,
            EloquentChairRepository::class
        );

        $this->app->bind(
            TransactionRepositoryInterface::class,
            EloquentTransactionRepository::class
        );

        $this->app->bind(
            TransactionDailyIncomeRepositoryInterface::class,
            EloquentTransactionDailyIncomeRepository::class
        );

        $this->app->bind(
            TransactionReplacementRealizationRepositoryInterface::class,
            EloquentTransactionReplacementRealizationRepository::class
        );

        $this->app->bind(
            TransactionTransferProofRepositoryInterface::class,
            EloquentTransactionTransferProofRepository::class
        );

        $this->app->bind(
            TransactionSystemIncomeRepositoryInterface::class,
            EloquentTransactionSystemIncomeRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(AuthEventSubscriber::class);
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
