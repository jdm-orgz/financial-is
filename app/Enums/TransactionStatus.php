<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

enum TransactionStatus: string
{
    use EnumOptions;

    case Draft = 'draft';
    case Approval = 'approval';
    case Correction = 'correction';
    case Comparing = 'comparing';
    case Compared = 'compared';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approval => 'Need Supervisor Approval',
            self::Correction => 'Need Correction',
            self::Comparing => 'Comparing',
            self::Compared => 'Need Admin Approval',
            self::Done => 'Done',
        };
    }
}
