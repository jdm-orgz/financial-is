<?php

namespace Tests\Unit;

use App\Concerns\EnumOptions;
use PHPUnit\Framework\TestCase;

enum DummyEnum: string
{
    use EnumOptions;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active Status',
            self::INACTIVE => 'Inactive Status',
        };
    }
}

enum DummyEnumNoLabel: int
{
    use EnumOptions;

    case ONE = 1;
    case TWO = 2;
}

class EnumOptionsTest extends TestCase
{
    public function test_names()
    {
        $this->assertEquals(['ACTIVE', 'INACTIVE'], DummyEnum::names());
    }

    public function test_values()
    {
        $this->assertEquals(['active', 'inactive'], DummyEnum::values());
    }

    public function test_array()
    {
        $this->assertEquals([
            'ACTIVE' => 'active',
            'INACTIVE' => 'inactive',
        ], DummyEnum::array());
    }

    public function test_options_with_label_method()
    {
        $this->assertEquals([
            [
                'label' => 'Active Status',
                'value' => 'active',
            ],
            [
                'label' => 'Inactive Status',
                'value' => 'inactive',
            ]
        ], DummyEnum::options());
    }

    public function test_options_without_label_method()
    {
        $this->assertEquals([
            [
                'label' => 'O N E',
                'value' => 1,
            ],
            [
                'label' => 'T W O',
                'value' => 2,
            ]
        ], DummyEnumNoLabel::options());
    }
}
