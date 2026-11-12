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
        DB::table('roles')->updateOrInsert(
            ['id' => 12],
            [
                'name' => 'CLEANING SERVICE',
                'page_permissions' => json_encode([
                    'dashboard',
                    'services.index',
                    'pansinar.index',
                    'reports.history',
                    'technicians.position',
                    'settings.index',
                ]),
            ]
        );

        $adminRole = DB::table('roles')->where('id', 1)->first();
        if ($adminRole) {
            $perms = json_decode($adminRole->page_permissions ?? '[]', true);
            if (is_array($perms) && !in_array('pansinar.index', $perms, true)) {
                $perms[] = 'pansinar.index';
                DB::table('roles')->where('id', 1)->update([
                    'page_permissions' => json_encode(array_values($perms)),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('id', 12)->delete();
    }
};

