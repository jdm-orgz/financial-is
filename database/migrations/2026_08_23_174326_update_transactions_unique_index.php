<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['outlet_id', 'date']);
        });

        DB::statement('CREATE UNIQUE INDEX transactions_outlet_id_date_unique ON transactions (outlet_id, date) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX transactions_outlet_id_date_unique');

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique(['outlet_id', 'date']);
        });
    }
};
