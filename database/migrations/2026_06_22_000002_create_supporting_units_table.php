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
        Schema::create('supporting_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('IN_DEVELOPMENT');
        });

        // Add check constraint for status
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlsrv') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE supporting_units ADD CONSTRAINT CHK_supporting_unit_status CHECK (status IN ('ACTIVE', 'IN_DEVELOPMENT', 'INACTIVE'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supporting_units');
    }
};
