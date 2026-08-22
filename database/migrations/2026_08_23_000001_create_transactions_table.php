<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('draft');
            $table->text('spg_notes')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('supervisor_actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_actioned_at')->nullable();
            $table->foreignUuid('admin_actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_actioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['outlet_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
