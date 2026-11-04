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
        Schema::create('sanitation_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('toilet_id')->constrained('sanitation_toilets')->cascadeOnDelete();
            $table->text('problem_description');
            $table->string('urgency', 20)->default('NORMAL'); // NORMAL, DARURAT
            $table->string('status', 20)->default('MENUNGGU'); // MENUNGGU, DIKERJAKAN, SELESAI
            $table->string('reporter_name', 150)->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('evidence_before')->nullable();
            $table->string('evidence_after')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTimeTz('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'urgency'], 'idx_sanitation_tickets_status_urgency');
            $table->index('created_at', 'idx_sanitation_tickets_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanitation_tickets');
    }
};

