<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lauthz\Facades\Enforcer;

class CasbinRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // admin: master and configuration
        Enforcer::addPolicy('admin', 'master/*', '*');
        Enforcer::addPolicy('admin', 'configuration/*', '*');

        // supervisor: transaction approval
        Enforcer::addPolicy('supervisor', 'transaction/approval/*', '*');

        // spg: transaction
        Enforcer::addPolicy('spg', 'transaction/*', '*');
    }
}
