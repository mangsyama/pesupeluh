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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('location_floor', 50)->nullable();
            $table->dateTimeTz('created_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('updated_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
