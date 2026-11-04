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
        Schema::create('sanitation_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toilet_id')->constrained('sanitation_toilets')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('water_flush_ok')->default(true); // Pilar 1: Air & Flush
            $table->boolean('floor_mirror_clean')->default(true); // Pilar 2: Lantai & Cermin
            $table->boolean('supplies_ready')->default(true); // Pilar 3: Sabun & Tisu
            $table->boolean('air_fragrance_ok')->default(true); // Pilar 4: Bebas Bau / Wangi
            $table->string('status', 25)->default('EXCELLENT'); // EXCELLENT, NEED_ATTENTION, PENDING
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'toilet_id'], 'idx_inspections_status_toilet');
            $table->index('created_at', 'idx_inspections_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanitation_inspections');
    }
};

