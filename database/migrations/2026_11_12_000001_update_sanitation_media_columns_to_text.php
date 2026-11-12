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
        Schema::table('sanitation_tickets', function (Blueprint $table) {
            $table->text('evidence_before')->nullable()->change();
            $table->text('evidence_after')->nullable()->change();
        });

        Schema::table('sanitation_inspections', function (Blueprint $table) {
            $table->text('photo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sanitation_tickets', function (Blueprint $table) {
            $table->string('evidence_before')->nullable()->change();
            $table->string('evidence_after')->nullable()->change();
        });

        Schema::table('sanitation_inspections', function (Blueprint $table) {
            $table->string('photo')->nullable()->change();
        });
    }
};

