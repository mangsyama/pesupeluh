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
        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 30);
            $table->string('action', 50)->nullable();
            $table->text('notes')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->dateTimeTz('created_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));

            $table->index(['ticket_id', 'created_at'], 'idx_histories_ticket_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_histories');
    }
};
