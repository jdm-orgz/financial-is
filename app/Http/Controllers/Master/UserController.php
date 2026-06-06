<?php

namespace App\Http\Controllers\Master;

use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Repositories\RoleRepositoryInterface;
use App\Domain\UserAccess\Repositories\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\User\StoreUserRequest;
use App\Http\Requests\Master\User\UpdateUserRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
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

        $users = $this->userRepository->getPaginated($perPage, $search, $sortBy, $sortDirection);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => request()->only(['search', 'sort_by', 'sort_direction']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $rolesQuery = Role::select('id', 'name', 'description')->where('is_active', '1');

        if (auth()->user()->role?->name !== 'super_admin') {
            $rolesQuery->where('name', '!=', 'super_admin');
        }

        $roles = $rolesQuery->get()->toArray();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Auto-assign password
        $validated['password'] = Hash::make('Success123!');

        // TODO: need to verify with project plan
        $validated['email'] = $validated['username'].'@mail.com';

        $this->userRepository->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User created successfully.']);

        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = $this->userRepository->findById($decryptedId);

        if (! $user) {
            abort(404);
        }

        if ($user->role && $user->role->name === 'super_admin' && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You do not have permission to edit a super admin user.');
        }

        $rolesQuery = Role::select('id', 'name', 'description')->where('is_active', '1');

        if (auth()->user()->role?->name !== 'super_admin') {
            $rolesQuery->where('name', '!=', 'super_admin');
        }

        $roles = $rolesQuery->get();
        $rolesArray = $roles->toArray();

        $userArray = $user->toArray();

        $matchingRole = collect($rolesArray)->first(function ($roleArray, $index) use ($user, $roles) {
            return $roles[$index]->id === $user->role_id;
        });

        $userArray['role_id'] = $matchingRole ? $matchingRole['id'] : '';

        return Inertia::render('Users/Edit', [
            'user' => $userArray,
            'roles' => $rolesArray,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id): RedirectResponse
    {
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = $this->userRepository->findById($decryptedId);

        if (! $user) {
            abort(404);
        }

        if ($user->role && $user->role->name === 'super_admin' && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You do not have permission to update a super admin user.');
        }

        $validated = $request->validated();

        // Optional: Update email if username changes?
        // Let's keep it simple and just update the validated fields (username, name, role_id).
        $this->userRepository->update($decryptedId, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User updated successfully.']);

        return redirect()->route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = $this->userRepository->findById($decryptedId);

        if (! $user) {
            abort(404);
        }

        if ($user->role && $user->role->name === 'super_admin' && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You do not have permission to delete a super admin user.');
        }

        if ($user->id === auth()->id() && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot delete your own account.');
        }

        $this->userRepository->delete($decryptedId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User deleted successfully.']);

        return redirect()->route('users.index');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(string $id): RedirectResponse
    {
        try {
            $decryptedId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = $this->userRepository->findById($decryptedId);

        if (! $user) {
            abort(404);
        }

        if ($user->role && $user->role->name === 'super_admin' && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You do not have permission to change status of a super admin user.');
        }

        if ($user->id === auth()->id() && auth()->user()->role?->name !== 'super_admin') {
            abort(403, 'You cannot change the status of your own account.');
        }

        $newStatus = $user->is_active === '1' ? '0' : '1';
        $this->userRepository->update($decryptedId, ['is_active' => $newStatus]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User status updated successfully.']);

        return redirect()->back();
    }
}
