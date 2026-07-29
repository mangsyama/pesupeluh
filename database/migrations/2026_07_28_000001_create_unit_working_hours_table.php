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
        Schema::create('unit_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supporting_unit_id')->constrained('supporting_units')->onDelete('cascade');
            $table->tinyInteger('day_of_week')->comment('1=Monday, 7=Sunday');
            $table->time('start_time')->default('07:30:00');
            $table->time('end_time')->default('15:00:00');
            $table->boolean('is_active')->default(true);
            $table->string('auto_disposition_mode', 30)->default('BROADCAST'); // BROADCAST, LEAST_WORKLOAD
            $table->dateTimeTz('created_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('updated_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));

            $table->unique(['supporting_unit_id', 'day_of_week'], 'uq_unit_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_working_hours');
    }
};
