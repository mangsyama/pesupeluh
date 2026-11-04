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
        Schema::create('sanitation_toilets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('building', 100)->default('Gedung A');
            $table->string('floor', 50)->default('Lantai 1');
            $table->string('status', 20)->default('AKTIF'); // AKTIF, NONAKTIF
            $table->timestamps();

            $table->index(['status', 'building'], 'idx_toilets_status_building');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanitation_toilets');
    }
};

