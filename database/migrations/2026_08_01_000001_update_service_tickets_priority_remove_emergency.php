<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update any existing EMERGENCY tickets to URGENT in production database
        DB::table('service_tickets')
            ->where('priority', 'EMERGENCY')
            ->update(['priority' => 'URGENT']);

        // 2. Update check constraint if using SQL Server
        if (DB::getDriverName() === 'sqlsrv') {
            try {
                DB::statement("ALTER TABLE service_tickets DROP CONSTRAINT CHK_ticket_priority");
            } catch (\Throwable $e) {
                // Ignore if constraint did not exist
            }
            DB::statement("ALTER TABLE service_tickets ADD CONSTRAINT CHK_ticket_priority CHECK (priority IN ('URGENT', 'ROUTINE'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            try {
                DB::statement("ALTER TABLE service_tickets DROP CONSTRAINT CHK_ticket_priority");
            } catch (\Throwable $e) {
                // Ignore if constraint did not exist
            }
            DB::statement("ALTER TABLE service_tickets ADD CONSTRAINT CHK_ticket_priority CHECK (priority IN ('EMERGENCY', 'URGENT', 'ROUTINE'))");
        }
    }
};
