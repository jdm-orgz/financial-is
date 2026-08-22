<?php

namespace Tests\Unit\Enums;

use App\Enums\TransactionStatus;
use PHPUnit\Framework\TestCase;

class TransactionStatusTest extends TestCase
{
    public function test_label_returns_correct_strings()
    {
        $this->assertEquals('Draft', TransactionStatus::Draft->label());
        $this->assertEquals('Need Supervisor Approval', TransactionStatus::Approval->label());
        $this->assertEquals('Need Correction', TransactionStatus::Correction->label());
        $this->assertEquals('Comparing', TransactionStatus::Comparing->label());
        $this->assertEquals('Need Admin Approval', TransactionStatus::Compared->label());
        $this->assertEquals('Done', TransactionStatus::Done->label());
    }
}
