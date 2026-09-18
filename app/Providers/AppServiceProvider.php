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
use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Events\DataUpdated;
use App\Listeners\AuthEventSubscriber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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

    public function boot(): void
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            URL::forceScheme('https');
        } elseif (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Also force it if the host contains ngrok to be safe
        if (request()->getHost() && str_contains(request()->getHost(), 'ngrok')) {
            URL::forceScheme('https');
        }

        Event::subscribe(AuthEventSubscriber::class);
        $this->configureDefaults();

        $models = [User::class, Role::class, Outlet::class, Chair::class, Transaction::class, LinkedOutletUser::class];
        foreach ($models as $model) {
            $model::saved(fn () => broadcast(new DataUpdated()));
            $model::deleted(fn () => broadcast(new DataUpdated()));
        }
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
