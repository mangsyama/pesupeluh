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
        $existing = DB::table('roles')->where('id', 12)->orWhere('name', 'CLEANING SERVICE')->first();
        if ($existing) {
            DB::table('roles')->where('id', $existing->id)->update([
                'name' => 'CLEANING SERVICE',
                'page_permissions' => json_encode([
                    'dashboard',
                    'services.index',
                    'pansinar.index',
                    'reports.history',
                    'technicians.position',
                    'settings.index',
                ]),
            ]);
        } else {
            $perms = json_encode([
                'dashboard',
                'services.index',
                'pansinar.index',
                'reports.history',
                'technicians.position',
                'settings.index',
            ]);

            $isSqlsrv = DB::connection()->getDriverName() === 'sqlsrv';
            if ($isSqlsrv) {
                $escapedPerms = str_replace("'", "''", $perms);
                DB::unprepared("SET IDENTITY_INSERT roles ON; INSERT INTO roles (id, name, page_permissions) VALUES (12, 'CLEANING SERVICE', '{$escapedPerms}'); SET IDENTITY_INSERT roles OFF;");
            } else {
                DB::table('roles')->insert([
                    'id' => 12,
                    'name' => 'CLEANING SERVICE',
                    'page_permissions' => $perms,
                ]);
            }
        }

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

