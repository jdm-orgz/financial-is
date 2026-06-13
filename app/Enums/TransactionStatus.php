<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

enum TransactionStatus: int
{
    use EnumOptions;

    case DRAFT = 1;
    case UNDER_REVIEW_SPV = 2;
    case UNDER_REVIEW_ADMIN = 3;
    case ACCEPTED = 4;
    case UNDER_INVESTIGATE = 5;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNDER_REVIEW_SPV => 'Under Review Supervisor',
            self::UNDER_REVIEW_ADMIN => 'Under Review Admin',
            self::ACCEPTED => 'Accepted',
            self::UNDER_INVESTIGATE => 'Under Investigate',
        };
    }
}
