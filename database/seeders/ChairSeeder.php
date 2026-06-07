<?php

namespace Database\Seeders;

use App\Domain\Outlet\Models\Chair;
use Illuminate\Database\Seeder;

class ChairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Chair::factory()->count(10)->create();
    }
}
