<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $defaultPassword = Hash::make('12345678');

        $demoUsers = [
            // 2. Director (DIREKTUR)
            [
                'role_id' => Role::DIREKTUR,
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
            // 3. Division Head - Medik (KEPALA BIDANG)
            [
                'role_id' => Role::KEPALA_BIDANG,
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
            // 4. Division Head - Non Medik (KEPALA BIDANG)
            [
                'role_id' => Role::KEPALA_BIDANG,
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
            // 5. Section Head - Sarpras (KEPALA SEKSI)
            [
                'role_id' => Role::KEPALA_SEKSI,
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
            // 6. Unit Head - IPSRS (KEPALA INSTALASI, supporting_unit_id = 8)
            [
                'role_id' => Role::KEPALA_INSTALASI,
                'room_id' => null,
                'supporting_unit_id' => 8,
                'nip' => '198609252011011006',
                'username' => 'kainstal_ipsrs',
                'name' => 'Agus Setiawan, S.T. (Ka. Instalasi IPSRS)',
                'email' => 'kaunit.ipsrs@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 7. Technician 1 - IPSRS (TEKNISI, supporting_unit_id = 8)
            [
                'role_id' => Role::TEKNISI,
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
            // 8. Technician 2 - IPSRS (TEKNISI, supporting_unit_id = 8)
            [
                'role_id' => Role::TEKNISI,
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
            // 9. Room Head - IGD (PJ RUANGAN, room_id = 1)
            [
                'role_id' => Role::PJ_RUANGAN,
                'room_id' => 1,
                'supporting_unit_id' => null,
                'nip' => '198805182012012009',
                'username' => 'pj_ruangan_igd',
                'name' => 'Ns. Dewi Lestari, S.Kep (PJ Ruangan IGD)',
                'email' => 'karu.igd@pesupeluh.rs',
                'email_verified_at' => now(),
                'password' => $defaultPassword,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            // 10. Reporter / Staff IGD (STAFF, room_id = 1)
            [
                'role_id' => Role::STAFF,
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
