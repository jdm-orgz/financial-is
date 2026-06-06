<?php

namespace App\Http\Controllers\Master;

use App\Domain\Outlet\Repositories\OutletRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Outlet\StoreOutletRequest;
use App\Http\Requests\Master\Outlet\UpdateOutletRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class OutletController extends Controller
{
    public function __construct(
        private readonly OutletRepositoryInterface $outletRepository
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

        $outlets = $this->outletRepository->getPaginated($perPage, $search, $sortBy, $sortDirection);

        return Inertia::render('Outlets/Index', [
            'outlets' => $outlets,
            'filters' => request()->only(['search', 'sort_by', 'sort_direction']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Outlets/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOutletRequest $request): RedirectResponse
    {
        $this->outletRepository->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Outlet created successfully.']);

        return redirect()->route('outlets.index');
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

        $outlet = $this->outletRepository->findById($decryptedId);

        if (! $outlet) {
            abort(404);
        }

        return Inertia::render('Outlets/Edit', [
            'outlet' => $outlet,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutletRequest $request, string $id): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $this->outletRepository->update($decryptedId, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Outlet updated successfully.']);

        return redirect()->route('outlets.index');
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

        $this->outletRepository->delete($decryptedId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Outlet deleted successfully.']);

        return redirect()->route('outlets.index');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(string $id): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($id);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedId);

        if (! $outlet) {
            abort(404);
        }

        $newStatus = $outlet->is_active === '1' ? '0' : '1';
        $this->outletRepository->update($decryptedId, ['is_active' => $newStatus]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Outlet status updated successfully.']);

        return redirect()->back();
    }
}
