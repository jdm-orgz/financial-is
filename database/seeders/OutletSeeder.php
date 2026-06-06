<?php

namespace Database\Seeders;

use App\Domain\Outlet\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlet::firstOrCreate([
            'name' => 'Grand Indonesia',
            'address' => 'Jl. M.H. Thamrin No.1, RT.1/RW.5, Menteng, Kec. Menteng, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10310',
            'latitude' => -6.195739,
            'longitude' => 106.822450,
        ]);
    }
}
