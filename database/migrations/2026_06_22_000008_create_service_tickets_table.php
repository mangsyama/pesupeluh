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
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'NEWID()' : 'NULL'))->unique();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('category_id')->constrained('feature_categories');
            $table->text('problem_description');
            
            $table->string('priority', 20)->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->dateTimeTz('validated_at')->nullable();
            
            $table->string('status', 30)->default('PENDING_VALIDATION');
            $table->dateTimeTz('responded_at')->nullable();
            $table->dateTimeTz('resolved_at')->nullable();
            
            $table->text('pending_reason')->nullable();
            $table->integer('paused_duration_seconds')->default(0);
            $table->dateTimeTz('last_paused_at')->nullable();
            $table->text('completion_notes')->nullable();
            
            $table->dateTimeTz('created_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('updated_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('deleted_at')->nullable();

            // Indexes
            $table->index(['status', 'uuid'], 'idx_tickets_status_uuid');
            $table->index('created_at', 'idx_tickets_created_at');
        });

        // Add check constraints for SQL Server
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement("ALTER TABLE service_tickets ADD CONSTRAINT CHK_ticket_priority CHECK (priority IN ('URGENT', 'ROUTINE'))");
            DB::statement("ALTER TABLE service_tickets ADD CONSTRAINT CHK_ticket_status CHECK (status IN ('PENDING_VALIDATION', 'ASSIGNED', 'IN_PROGRESS', 'PENDING', 'COMPLETED', 'CANCEL'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};
