<?php

namespace App\Http\Middleware;

use App\Domain\Settings\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $appName = Setting::get('app_name', config('app.name'));
        $appLogo = Setting::get('app_logo');

        return [
            ...parent::share($request),
            'name' => $appName,
            'app_logo' => $appLogo ? Storage::url($appLogo) : null,
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'role_name' => $request->user()->role->name ?? null,
                ]) : null,
                'permissions' => [
                    'master' => $request->user() ? $request->user()->hasPermissionTo('master/*', '*') : false,
                    'configuration' => $request->user() ? $request->user()->hasPermissionTo('configuration/*', '*') : false,
                    'transaction' => $request->user() ? $request->user()->hasPermissionTo('transaction/*', '*') : false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
