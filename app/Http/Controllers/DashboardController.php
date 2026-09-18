<?php

namespace App\Http\Controllers;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Domain\UserAccess\Models\Role;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        if (!File::exists($path) || !File::isDirectory($path)) {
            return $size;
        }

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $spgRole = Role::where('name', 'spg')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();

        $spgActiveCount = $spgRole ? User::where('role_id', $spgRole->id)->where('is_active', '1')->count() : 0;
        $spgInactiveCount = $spgRole ? User::where('role_id', $spgRole->id)->where('is_active', '0')->count() : 0;

        $supervisorActiveCount = $supervisorRole ? User::where('role_id', $supervisorRole->id)->where('is_active', '1')->count() : 0;
        $supervisorInactiveCount = $supervisorRole ? User::where('role_id', $supervisorRole->id)->where('is_active', '0')->count() : 0;

        $revenueYesterday = TransactionDailyIncome::join('transactions', 'transaction_daily_incomes.transaction_id', '=', 'transactions.id')
            ->where('transactions.date', $yesterday)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->sum('transaction_daily_incomes.amount');

        $revenueToday = TransactionDailyIncome::join('transactions', 'transaction_daily_incomes.transaction_id', '=', 'transactions.id')
            ->where('transactions.date', $today)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->sum('transaction_daily_incomes.amount');

        $topOutletRevenue = Outlet::select('outlets.name')
            ->join('transactions', 'outlets.id', '=', 'transactions.outlet_id')
            ->join('transaction_daily_incomes', 'transactions.id', '=', 'transaction_daily_incomes.transaction_id')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->groupBy('outlets.id', 'outlets.name')
            ->orderByRaw('SUM(transaction_daily_incomes.amount) DESC')
            ->first();

        $topOutletBrokenChair = Outlet::select('outlets.name')
            ->join('transactions', 'outlets.id', '=', 'transactions.outlet_id')
            ->join('transaction_replacement_realizations', 'transactions.id', '=', 'transaction_replacement_realizations.transaction_id')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->groupBy('outlets.id', 'outlets.name')
            ->orderByRaw('COUNT(transaction_replacement_realizations.id) DESC')
            ->first();

        $topChairRevenue = Chair::select('chairs.name', 'outlets.name as outlet_name')
            ->join('outlets', 'chairs.outlet_id', '=', 'outlets.id')
            ->join('transaction_daily_incomes', 'chairs.id', '=', 'transaction_daily_incomes.chair_id')
            ->join('transactions', 'transaction_daily_incomes.transaction_id', '=', 'transactions.id')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->groupBy('chairs.id', 'chairs.name', 'outlets.name')
            ->orderByRaw('SUM(transaction_daily_incomes.amount) DESC')
            ->first();

        $topBrokenChair = Chair::select('chairs.name', 'outlets.name as outlet_name')
            ->join('outlets', 'chairs.outlet_id', '=', 'outlets.id')
            ->join('transaction_replacement_realizations', 'chairs.id', '=', 'transaction_replacement_realizations.problem_chair_id')
            ->join('transactions', 'transaction_replacement_realizations.transaction_id', '=', 'transactions.id')
            ->whereMonth('transactions.date', $currentMonth)
            ->whereYear('transactions.date', $currentYear)
            ->where('transactions.status', TransactionStatus::Done->value)
            ->groupBy('chairs.id', 'chairs.name', 'outlets.name')
            ->orderByRaw('COUNT(transaction_replacement_realizations.id) DESC')
            ->first();

        $startDate = $request->query('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->endOfMonth()->format('Y-m-d'));

        $revenueChartData = TransactionDailyIncome::selectRaw('transactions.date, SUM(transaction_daily_incomes.amount) as total_revenue')
            ->join('transactions', 'transaction_daily_incomes.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->where('transactions.status', TransactionStatus::Done->value)
            ->groupBy('transactions.date')
            ->orderBy('transactions.date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            });

        $mediaSize = $this->getDirectorySize(storage_path('app/public/proofs'));

        $rootTotalSpace = disk_total_space('/');
        $rootFreeSpace = disk_free_space('/');
        $usedSpace = $rootTotalSpace - $rootFreeSpace;
        $systemSize = $usedSpace - $mediaSize;

        return inertia('dashboard', [
            'totalUsers' => User::count(),
            'totalRoles' => Role::count(),
            'totalOutlets' => Outlet::count(),
            'totalChairs' => Chair::count(),
            'spgActiveCount' => $spgActiveCount,
            'spgInactiveCount' => $spgInactiveCount,
            'supervisorActiveCount' => $supervisorActiveCount,
            'supervisorInactiveCount' => $supervisorInactiveCount,
            'revenueYesterday' => (float) $revenueYesterday,
            'revenueToday' => (float) $revenueToday,
            'topOutletRevenue' => $topOutletRevenue ? $topOutletRevenue->name : null,
            'topOutletBrokenChair' => $topOutletBrokenChair ? $topOutletBrokenChair->name : null,
            'topChairRevenue' => $topChairRevenue ? $topChairRevenue->outlet_name . ' - ' . $topChairRevenue->name : null,
            'topBrokenChair' => $topBrokenChair ? $topBrokenChair->outlet_name . ' - ' . $topBrokenChair->name : null,
            'revenueChartData' => $revenueChartData,
            'storage' => [
                'total' => $rootTotalSpace,
                'used' => $usedSpace,
                'left' => $rootFreeSpace,
                'media' => $mediaSize,
                'system' => $systemSize,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
