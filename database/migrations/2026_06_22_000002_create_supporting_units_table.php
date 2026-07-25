<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supporting_units', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('NON_MEDIK');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('IN_DEVELOPMENT');
        });

        // Add check constraint for status & type
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement("ALTER TABLE supporting_units ADD CONSTRAINT CHK_supporting_unit_status CHECK (status IN ('ACTIVE', 'IN_DEVELOPMENT', 'MAINTENANCE', 'INACTIVE'))");
            DB::statement("ALTER TABLE supporting_units ADD CONSTRAINT CHK_supporting_unit_type CHECK (type IN ('MEDIK', 'NON_MEDIK'))");
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

