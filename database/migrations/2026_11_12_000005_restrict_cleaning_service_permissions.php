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
        DB::table('roles')
            ->where('id', 12)
            ->update([
                'page_permissions' => json_encode([
                    'services.index',
                    'pansinar.index',
                ]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('id', 12)
            ->update([
                'page_permissions' => json_encode([
                    'dashboard',
                    'services.index',
                    'pansinar.index',
                    'reports.history',
                    'technicians.position',
                    'settings.index',
                ]),
            ]);
    }
};

