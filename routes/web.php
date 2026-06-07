<?php

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Http\Controllers\Master\ChairController;
use App\Http\Controllers\Master\LinkedOutletUserController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\UserController;
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
});

require __DIR__.'/settings.php';
