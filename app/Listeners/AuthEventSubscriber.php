<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Inertia\Inertia;

class AuthEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Logged in successfully.']);
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Logged out successfully.']);
    }

    /**
     * Handle user registration events.
     */
    public function handleUserRegistered(Registered $event): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registered successfully.']);
    }

    /**
     * Handle user failed authentication events.
     */
    public function handleUserFailed(Failed $event): void
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => 'Authentication failed. Please check your credentials.']);
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password reset successfully.']);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events): void
    {
        $events->listen(
            Login::class,
            [AuthEventSubscriber::class, 'handleUserLogin']
        );

        $events->listen(
            Logout::class,
            [AuthEventSubscriber::class, 'handleUserLogout']
        );

        $events->listen(
            Registered::class,
            [AuthEventSubscriber::class, 'handleUserRegistered']
        );

        $events->listen(
            Failed::class,
            [AuthEventSubscriber::class, 'handleUserFailed']
        );

        $events->listen(
            PasswordReset::class,
            [AuthEventSubscriber::class, 'handlePasswordReset']
        );
    }
}
