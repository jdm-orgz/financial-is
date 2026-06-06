<?php

namespace App\Http\Controllers\Master;

use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Outlet\Repositories\LinkedOutletUserRepositoryInterface;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\LinkedOutletUser\StoreLinkedOutletUserRequest;
use App\Http\Requests\Master\LinkedOutletUser\UpdateLinkedOutletUserRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class LinkedOutletUserController extends Controller
{
    public function __construct(
        private readonly LinkedOutletUserRepositoryInterface $linkedOutletUserRepository
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

        $roleNames = request('roles', []);

        $linkedOutletUsers = $this->linkedOutletUserRepository->getPaginated($perPage, $search, $sortBy, $sortDirection, $roleNames);

        $roles = Role::where('is_active', '1')->get(['id', 'name', 'description']);

        return Inertia::render('LinkedOutletUsers/Index', [
            'linkedOutletUsers' => $linkedOutletUsers,
            'roles' => $roles,
            'filters' => request()->only(['search', 'sort_by', 'sort_direction', 'roles']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $users = User::select('id', 'name', 'email', 'role_id')->where('is_active', '1')->with('role:id,description')->get();
        $outlets = Outlet::select('id', 'name')->where('is_active', '1')->get();

        $usersArray = $users->toArray();
        $outletsArray = $outlets->toArray();

        $assignedOutletsMap = [];
        $allLinks = LinkedOutletUser::all();

        foreach ($allLinks as $link) {
            $matchingUser = collect($usersArray)->first(function ($userArray, $index) use ($link, $users) {
                return $users[$index]->id === $link->user_id;
            });
            $matchingOutlet = collect($outletsArray)->first(function ($outletArray, $index) use ($link, $outlets) {
                return $outlets[$index]->id === $link->outlet_id;
            });

            if ($matchingUser && $matchingOutlet) {
                $encryptedUserId = $matchingUser['id'];
                $encryptedOutletId = $matchingOutlet['id'];

                if (! isset($assignedOutletsMap[$encryptedUserId])) {
                    $assignedOutletsMap[$encryptedUserId] = [];
                }
                $assignedOutletsMap[$encryptedUserId][] = $encryptedOutletId;
            }
        }

        return Inertia::render('LinkedOutletUsers/Create', [
            'users' => $usersArray,
            'outlets' => $outletsArray,
            'assignedOutletsMap' => $assignedOutletsMap,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLinkedOutletUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userId = $validated['user_id'];
        $outletIds = $validated['outlet_ids'];
        $isActive = $validated['is_active'] ?? '1';

        foreach ($outletIds as $outletId) {
            $this->linkedOutletUserRepository->create([
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'is_active' => $isActive,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Linked Outlet User created successfully.']);

        return redirect()->route('linked-outlet-users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = User::find($decryptedId);
        if (! $user) {
            abort(404);
        }

        $users = User::select('id', 'name', 'email', 'role_id')->where('is_active', '1')->with('role:id,description')->get();
        $outlets = Outlet::select('id', 'name')->where('is_active', '1')->get();

        $usersArray = $users->toArray();
        $outletsArray = $outlets->toArray();

        $targetUserId = $user->id;
        $userLinks = LinkedOutletUser::where('user_id', $targetUserId)->get();

        $assignedOutletIds = [];
        foreach ($userLinks as $link) {
            $matchingOutlet = collect($outletsArray)->first(function ($outletArray, $index) use ($link, $outlets) {
                return $outlets[$index]->id === $link->outlet_id;
            });
            if ($matchingOutlet) {
                $assignedOutletIds[] = $matchingOutlet['id'];
            }
        }

        // Find the first link to get the overall is_active status (or default to 1)
        $firstLink = $userLinks->first();
        $isActive = $firstLink ? $firstLink->is_active : '1';

        $linkedOutletUserArray = [
            'id' => $id, // Passing the encrypted user ID as the resource ID
            'user_id' => $user->id,
            'is_active' => $isActive,
        ];

        $matchingUser = collect($usersArray)->first(function ($userArray, $index) use ($user, $users) {
            return $users[$index]->id === $user->id;
        });
        $linkedOutletUserArray['user_id'] = $matchingUser ? $matchingUser['id'] : '';
        $linkedOutletUserArray['outlet_ids'] = $assignedOutletIds;

        return Inertia::render('LinkedOutletUsers/Edit', [
            'linkedOutletUser' => $linkedOutletUserArray,
            'users' => $usersArray,
            'outlets' => $outletsArray,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLinkedOutletUserRequest $request, string $id): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = User::find($decryptedId);
        if (! $user) {
            abort(404);
        }

        $validated = $request->validated();
        $outletIds = $validated['outlet_ids'] ?? [];
        $isActive = $validated['is_active'] ?? '1';
        $userId = $user->id;

        $existingLinks = LinkedOutletUser::where('user_id', $userId)->get();
        $existingOutletIds = $existingLinks->pluck('outlet_id')->toArray();

        $toDelete = array_diff($existingOutletIds, $outletIds);
        $toAdd = array_diff($outletIds, $existingOutletIds);

        if (! empty($toDelete)) {
            LinkedOutletUser::where('user_id', $userId)
                ->whereIn('outlet_id', $toDelete)
                ->delete();
        }

        foreach ($toAdd as $outletId) {
            $this->linkedOutletUserRepository->create([
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'is_active' => $isActive,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Linked Outlet User updated successfully.']);

        return redirect()->route('linked-outlet-users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        // ID here is User ID
        LinkedOutletUser::where('user_id', $decryptedId)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Linked Outlet User deleted successfully.']);

        return redirect()->route('linked-outlet-users.index');
    }

    /**
     * Toggle the status of a linked outlet user.
     */
    public function updateStatus(string $id): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $links = LinkedOutletUser::where('user_id', $decryptedId)->get();
        if ($links->isEmpty()) {
            abort(404);
        }

        $firstLink = $links->first();
        $newStatus = $firstLink->is_active === '1' ? '0' : '1';

        LinkedOutletUser::where('user_id', $decryptedId)->update(['is_active' => $newStatus]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status updated successfully.']);

        return redirect()->back();
    }
}
