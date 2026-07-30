<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed dummy demo users for development testing.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        $demoUsers = [
            // 2. Director (DIRECTOR)
            [
                'role_id' => 2,
                'room_id' => null,
                'supporting_unit_id' => null,
                'nip' => '197003151998031002',
                'username' => 'direktur',
                'name' => 'dr. Budi Santoso, Sp.B',
                'email' => 'direktur@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 3. Division Head - Medik (DIVISION_HEAD)
            [
                'role_id' => 3,
                'room_id' => null,
                'supporting_unit_id' => null,
                'nip' => '197508202003121003',
                'username' => 'kabid_medik',
                'name' => 'dr. Siti Rahma, Sp.A',
                'email' => 'kabid.medik@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 4. Division Head - Non Medik (DIVISION_HEAD)
            [
                'role_id' => 3,
                'room_id' => null,
                'supporting_unit_id' => null,
                'nip' => '197811052005011004',
                'username' => 'kabid_nonmedik',
                'name' => 'H. Ahmad Fauzi, S.T.',
                'email' => 'kabid.nonmedik@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 5. Section Head - Sarpras (SECTION_HEAD)
            [
                'role_id' => 4,
                'room_id' => null,
                'supporting_unit_id' => null,
                'nip' => '198204122008041005',
                'username' => 'kasie_sarpras',
                'name' => 'Ir. Hendra Wijaya',
                'email' => 'kasie.sarpras@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 6. Unit Head - IPSRS (UNIT_HEAD, supporting_unit_id = 8)
            [
                'role_id' => 5,
                'room_id' => null,
                'supporting_unit_id' => 8,
                'nip' => '198609252011011006',
                'username' => 'kaunit_ipsrs',
                'name' => 'Agus Setiawan, S.T. (Ka. Unit IPSRS)',
                'email' => 'kaunit.ipsrs@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 7. Technician 1 - IPSRS (TECHNICIAN, supporting_unit_id = 8)
            [
                'role_id' => 6,
                'room_id' => null,
                'supporting_unit_id' => 8,
                'nip' => '199002142014021007',
                'username' => 'teknisi1_ipsrs',
                'name' => 'Rizky Pratama (Teknisi Listrik/AC)',
                'email' => 'teknisi1.ipsrs@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 8. Technician 2 - IPSRS (TECHNICIAN, supporting_unit_id = 8)
            [
                'role_id' => 6,
                'room_id' => null,
                'supporting_unit_id' => 8,
                'nip' => '199207302015031008',
                'username' => 'teknisi2_ipsrs',
                'name' => 'Dedi Kurniawan (Teknisi Sanitasi/Plumbing)',
                'email' => 'teknisi2.ipsrs@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 9. Room Head - IGD (ROOM_HEAD, room_id = 1)
            [
                'role_id' => 7,
                'room_id' => 1,
                'supporting_unit_id' => null,
                'nip' => '198805182012012009',
                'username' => 'karu_igd',
                'name' => 'Ns. Dewi Lestari, S.Kep (Karu IGD)',
                'email' => 'karu.igd@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 10. Reporter / Staff IGD (REPORTER, room_id = 1)
            [
                'role_id' => 8,
                'room_id' => 1,
                'supporting_unit_id' => null,
                'nip' => '199512102018012010',
                'username' => 'staf_igd',
                'name' => 'Bambang Sujatmiko (Perawat IGD)',
                'email' => 'staf.igd@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
        ];

        foreach ($demoUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
