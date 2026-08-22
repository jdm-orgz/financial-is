<?php

namespace Tests\Unit\Enums;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_label_returns_correct_strings()
    {
        $this->assertEquals('Cash', PaymentMethod::Cash->label());
        $this->assertEquals('QRIS', PaymentMethod::Qris->label());
    }
}
