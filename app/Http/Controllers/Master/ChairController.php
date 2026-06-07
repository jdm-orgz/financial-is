<?php

namespace App\Http\Controllers\Master;

use App\Domain\Outlet\Repositories\ChairRepositoryInterface;
use App\Domain\Outlet\Repositories\OutletRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Chair\StoreBulkChairRequest;
use App\Http\Requests\Master\Chair\StoreChairRequest;
use App\Http\Requests\Master\Chair\UpdateChairRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class ChairController extends Controller
{
    public function __construct(
        private readonly ChairRepositoryInterface $chairRepository,
        private readonly OutletRepositoryInterface $outletRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(string $outletId): Response
    {
        try {
            $decryptedOutletId = (string) Crypt::decryptString($outletId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedOutletId);

        if (! $outlet) {
            abort(404);
        }

        $search = request('search');
        $sortBy = request('sort_by');
        $sortDirection = request('sort_direction', 'asc');
        $perPage = (int) request('per_page', 10);

        $chairs = $this->chairRepository->getPaginated($decryptedOutletId, $perPage, $search, $sortBy, $sortDirection);

        return Inertia::render('Chairs/Index', [
            'chairs' => $chairs,
            'outlet' => $outlet,
            'filters' => request()->only(['search', 'sort_by', 'sort_direction']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $outletId): Response
    {
        try {
            $decryptedOutletId = (string) Crypt::decryptString($outletId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedOutletId);

        if (! $outlet) {
            abort(404);
        }

        return Inertia::render('Chairs/Create', [
            'outlet' => $outlet,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChairRequest $request, string $outletId): RedirectResponse
    {
        try {
            $decryptedOutletId = (string) Crypt::decryptString($outletId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedOutletId);

        if (! $outlet) {
            abort(404);
        }

        $data = $request->validated();
        $data['outlet_id'] = $decryptedOutletId;

        if (empty($data['name'])) {
            $prefix = $outlet->chairPrefix;
            if ($prefix) {
                $prefix->increment('last_counter');
                $data['name'] = $prefix->prefix.'-'.$prefix->last_counter;
            } else {
                // Fallback if no prefix is configured
                $data['name'] = 'Chair '.uniqid();
            }
        }

        $this->chairRepository->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Chair created successfully.']);

        return redirect()->route('outlets.chairs.index', ['outlet' => $outletId]);
    }

    /**
     * Store bulk newly created resources in storage.
     */
    public function storeBulk(StoreBulkChairRequest $request, string $outletId): RedirectResponse
    {
        try {
            $decryptedOutletId = (string) Crypt::decryptString($outletId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedOutletId);

        if (! $outlet) {
            abort(404);
        }

        $chairsCount = $request->validated('chairs_count');
        $prefix = $outlet->chairPrefix;

        for ($i = 0; $i < $chairsCount; $i++) {
            $name = 'Chair '.uniqid();
            if ($prefix) {
                $prefix->increment('last_counter');
                $name = $prefix->prefix.'-'.$prefix->last_counter;
            }

            $this->chairRepository->create([
                'outlet_id' => $decryptedOutletId,
                'name' => $name,
                'is_active' => '1',
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Successfully generated {$chairsCount} chairs."]);

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $outletId, string $chairId): Response
    {
        try {
            $decryptedOutletId = (string) Crypt::decryptString($outletId);
            $decryptedChairId = (string) Crypt::decryptString($chairId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $outlet = $this->outletRepository->findById($decryptedOutletId);
        $chair = $this->chairRepository->findById($decryptedChairId);

        if (! $outlet || ! $chair) {
            abort(404);
        }

        return Inertia::render('Chairs/Edit', [
            'outlet' => $outlet,
            'chair' => $chair,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChairRequest $request, string $outletId, string $chairId): RedirectResponse
    {
        try {
            $decryptedChairId = (string) Crypt::decryptString($chairId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $this->chairRepository->update($decryptedChairId, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Chair updated successfully.']);

        return redirect()->route('outlets.chairs.index', ['outlet' => $outletId]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $outletId, string $chairId): RedirectResponse
    {
        try {
            $decryptedChairId = (string) Crypt::decryptString($chairId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $this->chairRepository->delete($decryptedChairId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Chair deleted successfully.']);

        return redirect()->route('outlets.chairs.index', ['outlet' => $outletId]);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(string $outletId, string $chairId): RedirectResponse
    {
        try {
            $decryptedChairId = (string) Crypt::decryptString($chairId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $chair = $this->chairRepository->findById($decryptedChairId);

        if (! $chair) {
            abort(404);
        }

        $newStatus = $chair->is_active === '1' ? '0' : '1';
        $this->chairRepository->update($decryptedChairId, ['is_active' => $newStatus]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Chair status updated successfully.']);

        return redirect()->back();
    }
}
