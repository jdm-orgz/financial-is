<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

enum PaymentMethod: string
{
    use EnumOptions;

    case Cash = 'cash';
    case Qris = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Qris => 'QRIS',
        };
    }
}
