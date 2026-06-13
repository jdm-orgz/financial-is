<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait EnumOptions
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function array(): array
    {
        return array_combine(self::names(), self::values());
    }

    public static function options(): array
    {
        return array_map(function ($case) {
            return [
                'label' => method_exists($case, 'label') ? $case->label() : Str::headline($case->name),
                'value' => $case->value ?? $case->name,
            ];
        }, self::cases());
    }
}
