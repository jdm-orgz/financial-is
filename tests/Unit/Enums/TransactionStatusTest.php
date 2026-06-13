<?php

namespace Tests\Unit\Enums;

use App\Enums\TransactionStatus;
use PHPUnit\Framework\TestCase;

class TransactionStatusTest extends TestCase
{
    public function test_label_returns_correct_strings()
    {
        $this->assertEquals('Draft', TransactionStatus::DRAFT->label());
        $this->assertEquals('Under Review Supervisor', TransactionStatus::UNDER_REVIEW_SPV->label());
        $this->assertEquals('Under Review Admin', TransactionStatus::UNDER_REVIEW_ADMIN->label());
        $this->assertEquals('Accepted', TransactionStatus::ACCEPTED->label());
        $this->assertEquals('Under Investigate', TransactionStatus::UNDER_INVESTIGATE->label());
    }
}
