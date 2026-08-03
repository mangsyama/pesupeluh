<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $isSqlSrv = DB::connection()->getDriverName() === 'sqlsrv';

        // Step 1: Clear and re-populate the roles table with the 11 new roles first
        DB::table('roles')->delete();

        $disposisiPermissions = json_encode([
            'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'technicians.position', 'settings.index'
        ]);

        $reportOnlyPermissions = json_encode([
            'dashboard', 'services.index', 'reports.history', 'technicians.position', 'settings.index'
        ]);

        $roles = [
            [
                'id' => 1,
                'name' => 'ADMINISTRATOR',
                'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index',
                    'technicians.position', 'service-management.working-hours',
                    'service-management.rooms', 'service-management.categories', 'service-management.supporting-units',
                    'users.approvals', 'users.index', 'admin.wa-gateway.index', 'admin.qr-code.index', 'settings.index', 'design-system.index',
                ])
            ],
            [
                'id' => 2,
                'name' => 'DIREKTUR',
                'page_permissions' => json_encode(['dashboard', 'settings.index'])
            ],
            [
                'id' => 3,
                'name' => 'KEPALA BIDANG',
                'page_permissions' => $disposisiPermissions
            ],
            [
                'id' => 4,
                'name' => 'KEPALA BAGIAN',
                'page_permissions' => $reportOnlyPermissions
            ],
            [
                'id' => 5,
                'name' => 'KEPALA SEKSI',
                'page_permissions' => $disposisiPermissions
            ],
            [
                'id' => 6,
                'name' => 'KEPALA SUB BAGIAN',
                'page_permissions' => $reportOnlyPermissions
            ],
            [
                'id' => 7,
                'name' => 'KEPALA INSTALASI',
                'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'technicians.position', 'service-management.working-hours', 'settings.index'
                ])
            ],
            [
                'id' => 8,
                'name' => 'SEKRETARIS INSTALASI',
                'page_permissions' => $disposisiPermissions
            ],
            [
                'id' => 9,
                'name' => 'PJ RUANGAN',
                'page_permissions' => $disposisiPermissions
            ],
            [
                'id' => 10,
                'name' => 'TEKNISI',
                'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'technicians.position', 'settings.index'
                ])
            ],
            [
                'id' => 11,
                'name' => 'STAFF',
                'page_permissions' => $reportOnlyPermissions
            ],
        ];

        if ($isSqlSrv) {
            $values = [];
            foreach ($roles as $r) {
                $id = (int) $r['id'];
                $name = str_replace("'", "''", $r['name']);
                $perms = str_replace("'", "''", $r['page_permissions']);
                $values[] = "({$id}, '{$name}', '{$perms}')";
            }
            $sql = "SET IDENTITY_INSERT roles ON; ";
            $sql .= "INSERT INTO roles (id, name, page_permissions) VALUES " . implode(', ', $values) . "; ";
            $sql .= "SET IDENTITY_INSERT roles OFF;";

            DB::unprepared($sql);
        } else {
            DB::table('roles')->insert($roles);
        }

        // Step 2: Safely remap existing user role_ids in descending order
        // Old 8 (REPORTER / Staff) -> New 11 (STAFF)
        DB::table('users')->where('role_id', 8)->update(['role_id' => 11]);

        // Old 7 (ROOM_HEAD / Ka Ruangan) -> New 9 (PJ RUANGAN)
        DB::table('users')->where('role_id', 7)->update(['role_id' => 9]);

        // Old 6 (TECHNICIAN / Teknisi) -> New 10 (TEKNISI)
        DB::table('users')->where('role_id', 6)->update(['role_id' => 10]);

        // Old 5 (UNIT_HEAD / Ka Unit) -> New 7 (KEPALA INSTALASI)
        DB::table('users')->where('role_id', 5)->update(['role_id' => 7]);

        // Old 4 (SECTION_HEAD / Ka Seksi) -> New 5 (KEPALA SEKSI)
        DB::table('users')->where('role_id', 4)->update(['role_id' => 5]);

        // Old 3 (DIVISION_HEAD / Ka Bidang) -> New 3 (KEPALA BIDANG)
        DB::table('users')->where('role_id', 3)->update(['role_id' => 3]);

        // Old 2 (DIRECTOR / Direktur) -> New 2 (DIREKTUR)
        DB::table('users')->where('role_id', 2)->update(['role_id' => 2]);

        // Old 1 (ADMINISTRATOR) -> New 1 (ADMINISTRATOR)
        DB::table('users')->where('role_id', 1)->update(['role_id' => 1]);

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse mapping if needed
    }
};
