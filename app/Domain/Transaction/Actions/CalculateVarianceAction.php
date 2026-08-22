<?php

namespace App\Domain\Transaction\Actions;

use App\Domain\Transaction\Models\Transaction;

class CalculateVarianceAction
{
    /**
     * Calculate the variance between system income and SPG-reported income per chair.
     *
     * @return array<int, array{
     *     chair_id: string,
     *     chair_name: string,
     *     system_amount: float,
     *     replacement_total: float,
     *     system_adjusted: float,
     *     spg_amount: float,
     *     variance: float,
     *     status: string
     * }>
     */
    public function execute(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'dailyIncomes.chair',
            'systemIncomes.chair',
            'replacementRealizations',
        ]);

        $spgAmounts = $transaction->dailyIncomes->keyBy('chair_id');
        $systemAmounts = $transaction->systemIncomes->keyBy('chair_id');

        // Sum replacement realizations grouped by problem_chair_id
        $replacementTotals = $transaction->replacementRealizations
            ->groupBy('problem_chair_id')
            ->map(fn ($group) => $group->sum('amount'));

        // Merge all chair IDs from both sides
        $allChairIds = $spgAmounts->keys()
            ->merge($systemAmounts->keys())
            ->unique();

        $result = [];

        foreach ($allChairIds as $chairId) {
            $systemIncome = $systemAmounts->get($chairId);
            $spgIncome = $spgAmounts->get($chairId);

            $systemAmount = $systemIncome ? (float) $systemIncome->amount : 0;
            $replacementTotal = (float) ($replacementTotals->get($chairId) ?? 0);
            $systemAdjusted = $systemAmount - $replacementTotal;
            $spgAmount = $spgIncome ? (float) $spgIncome->amount : 0;
            $variance = $systemAdjusted - $spgAmount;

            $chairName = $systemIncome?->chair?->name
                ?? $spgIncome?->chair?->name
                ?? 'Unknown';

            $result[] = [
                'chair_id' => $chairId,
                'chair_name' => $chairName,
                'system_amount' => $systemAmount,
                'replacement_total' => $replacementTotal,
                'system_adjusted' => $systemAdjusted,
                'spg_amount' => $spgAmount,
                'variance' => $variance,
                'status' => $variance != 0 ? 'warning' : 'ok',
            ];
        }

        return $result;
    }
}
