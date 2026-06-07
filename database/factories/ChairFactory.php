<?php

namespace Database\Factories;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Outlet\Models\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chair>
 */
class ChairFactory extends Factory
{
    protected $model = Chair::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $outletId = Outlet::inRandomOrder()->first()->id;

        return [
            'outlet_id' => $outletId,
            'name' => 'Chair '.$this->faker->numberBetween(1, 100),
            'is_active' => '1',
        ];
    }
}
