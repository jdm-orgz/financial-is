<?php

namespace App\Http\Controllers\Master;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Repositories\RoleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Role\StoreRoleRequest;
use App\Http\Requests\Master\Role\UpdateRoleRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;
use Lauthz\Facades\Enforcer;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $search = request('search');
        $sortBy = request('sort_by');
        $sortDirection = request('sort_direction', 'asc');
        $perPage = (int) request('per_page', 10);

        $roles = $this->roleRepository->getPaginated($perPage, $search, $sortBy, $sortDirection);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => request()->only(['search', 'sort_by', 'sort_direction']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'available_permissions' => Role::AVAILABLE_PERMISSIONS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = $this->roleRepository->create($validated);

        if (isset($validated['permissions']) && is_array($validated['permissions'])) {
            foreach ($validated['permissions'] as $permission) {
                Enforcer::addPolicy($role->name, $permission, '*');
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role created successfully.']);

        return redirect()->route('roles.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        try {
            $decryptedId = (int) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $role = $this->roleRepository->findById($decryptedId);

        if (! $role) {
            abort(404);
        }

        if (in_array($role->name, ['super_admin', 'admin']) && auth()->user()->role?->name !== 'super_admin') {
            abort(403, ucfirst(str_replace('_', ' ', $role->name)).' role cannot be edited.');
        }

        if ($role->name === auth()->user()->role?->name && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot edit your own role.');
        }

        $policies = Enforcer::getFilteredPolicy(0, $role->name);
        $currentPermissions = collect($policies)->map(fn ($policy) => $policy[1])->toArray();

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'available_permissions' => Role::AVAILABLE_PERMISSIONS,
            'current_permissions' => $currentPermissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, string $id): RedirectResponse
    {
        try {
            $decryptedId = (int) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $role = $this->roleRepository->findById($decryptedId);

        if ($role && in_array($role->name, ['super_admin', 'admin']) && auth()->user()->role?->name !== 'super_admin') {
            abort(403, ucfirst(str_replace('_', ' ', $role->name)).' role cannot be updated.');
        }

        if ($role && $role->name === auth()->user()->role?->name && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot update your own role.');
        }

        $validated = $request->validated();
        $this->roleRepository->update($decryptedId, $validated);

        if ($role && $role->name !== 'super_admin') {
            $newPermissions = $validated['permissions'] ?? [];
            $policies = Enforcer::getFilteredPolicy(0, $role->name);
            $currentPermissions = collect($policies)->map(fn ($policy) => $policy[1])->toArray();

            $permissionsToAdd = array_diff($newPermissions, $currentPermissions);
            $permissionsToRemove = array_diff($currentPermissions, $newPermissions);

            foreach ($permissionsToAdd as $permission) {
                Enforcer::addPolicy($role->name, $permission, '*');
            }

            foreach ($permissionsToRemove as $permission) {
                Enforcer::removePolicy($role->name, $permission, '*');
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role updated successfully.']);

        return redirect()->route('roles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $decryptedId = (int) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $role = $this->roleRepository->findById($decryptedId);

        if ($role && in_array($role->name, ['super_admin', 'admin']) && auth()->user()->role?->name !== 'super_admin') {
            abort(403, ucfirst(str_replace('_', ' ', $role->name)).' role cannot be deleted.');
        }

        if ($role && $role->name === auth()->user()->role?->name && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot delete your own role.');
        }

        $this->roleRepository->delete($decryptedId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role deleted successfully.']);

        return redirect()->route('roles.index');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(string $id): RedirectResponse
    {
        try {
            $decryptedId = (int) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $role = $this->roleRepository->findById($decryptedId);

        if (! $role) {
            abort(404);
        }

        if (in_array($role->name, ['super_admin', 'admin']) && auth()->user()->role?->name !== 'super_admin') {
            abort(403, ucfirst(str_replace('_', ' ', $role->name)).' role status cannot be changed.');
        }

        if ($role->name === auth()->user()->role?->name && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot change the status of your own role.');
        }

        $newStatus = $role->is_active === '1' ? '0' : '1';
        $this->roleRepository->update($decryptedId, ['is_active' => $newStatus]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role status updated successfully.']);

        return redirect()->back();
    }
}
