<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        if (DB::table('roles')->count() === 0) {
            $disposisiPermissions = json_encode([
                'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'technicians.position', 'settings.index'
            ]);

            $reportOnlyPermissions = json_encode([
                'dashboard', 'services.index', 'reports.history', 'technicians.position', 'settings.index'
            ]);

            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'name' => 'ADMINISTRATOR',
                    'page_permissions' => json_encode([
                        'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports-audit.index', 'reports.index',
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
            ]);
        }

        // 2. Seed Supporting Units
        if (DB::table('supporting_units')->count() === 0) {
            DB::table('supporting_units')->insert([
                // Medik Units
                [
                    'type' => 'MEDIK',
                    'name' => 'FARMASI',
                    'slug' => 'farmasi',
                    'description' => 'Sistem pelaporan stok obat, resep, dan kebutuhan apotek.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'MEDIK',
                    'name' => 'RADIOLOGI',
                    'slug' => 'radiologi',
                    'description' => 'Pelaporan pemeriksaan radiologi, hasil Rontgen, CT Scan, dan USG.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'MEDIK',
                    'name' => 'LABORATORIUM',
                    'slug' => 'laboratorium',
                    'description' => 'Pencatatan pemeriksaan darah, urine, patologi, dan laboratorium klinis.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'MEDIK',
                    'name' => 'CSSD',
                    'slug' => 'cssd',
                    'description' => 'Sistem pemantauan sterilisasi alkes medis dan instrumen operasi.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                // Non-Medik Units
                [
                    'type' => 'NON_MEDIK',
                    'name' => 'GIZI',
                    'slug' => 'gizi',
                    'description' => 'Pelaporan menu makanan pasien, distribusi gizi, dan operasional dapur RS.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'NON_MEDIK',
                    'name' => 'LAUNDRY',
                    'slug' => 'laundry',
                    'description' => 'Pencatatan sirkulasi linen medis, kapasitas pencucian, dan inventaris laundry.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'NON_MEDIK',
                    'name' => 'KESLING',
                    'slug' => 'kesling',
                    'description' => 'Sistem pelaporan sanitasi lingkungan, pengelolaan limbah B3, dan kualitas air.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'type' => 'NON_MEDIK',
                    'name' => 'IPSRS',
                    'slug' => 'ipsrs',
                    'description' => 'Sistem informasi pemeliharaan sarana prasarana, alkes, listrik, air, dan fasilitas RS.',
                    'status' => 'ACTIVE'
                ],
            ]);
        }

        // 3. Seed Rooms
        if (DB::table('rooms')->count() === 0) {
            DB::table('rooms')->insert([
                ['name' => 'UGD', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'VK/PERINA', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'FARMASI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'LABORATORIUM', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'RADIOLOGI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'CLEANING SERVICE', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'POLI KLINIK', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 2'],
                ['name' => 'FISIOTERAPI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 2'],
                ['name' => 'RAWAT INAP KASWUARI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 3'],
                ['name' => 'KAMAR BEDAH', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 1'],
                ['name' => 'RAWAT INAP CENDRAWASIH', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 2'],
                ['name' => 'RAWAT INAP MERPATI', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'IPSRS', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'KESLING', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'RUANG KABID PENUNJANG', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'LAUNDRY', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'GIZI', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'HCU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'CSSD', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'ICU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'KEPEGAWAIAN/KEUANGAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG ADMINISTRASI PELAYANAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG DIREKTUR', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG KABAG TU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'DALOP', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG SUB BAGIAN KEUANGAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'SECURITY', 'building_name' => 'Halaman Depan', 'location_floor' => '-'],
            ]);
        }

        // 4. Seed Issue Categories under IPSRS (supporting_unit_id = 8)
        if (DB::table('issue_categories')->count() === 0) {
            DB::table('issue_categories')->insert([
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Saniter',
                    'description' => 'Permasalahan pada fasilitas toilet, kamar mandi, serta sarana kebersihan/tempat sampah.'
                ],
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Peralatan Besar Kecil',
                    'description' => 'Permasalahan pada sarana utilitas utama (pompa, lift, genset, gas medis, kompor, kipas angin).'
                ],
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Perlengkapan Kantor',
                    'description' => 'Permasalahan pada fasilitas pendukung kantor (AC, furnitur/mebel, TV, telepon).'
                ],
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Alat Medis',
                    'description' => 'Gangguan atau kerusakan pada alat-alat kesehatan dan medis.'
                ],
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Mekanikal Elektrikal',
                    'description' => 'Gangguan sistem kelistrikan, instalasi listrik, dan pencahayaan/lampu.'
                ],
                [
                    'supporting_unit_id' => 8,
                    'name' => 'Fisik Gedung',
                    'description' => 'Permasalahan struktur dan area bangunan (atap, plafon, dinding, lantai) serta area luar gedung.'
                ],
            ]);
        }

        // 5. Seed Users (Complete 11 Dummy Roles for development & testing)
        if (DB::table('users')->count() === 0) {
            $defaultPassword = Hash::make('12345678');

            $users = [
                // 1. ADMINISTRATOR
                [
                    'role_id' => 1,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '198501012010011001',
                    'username' => 'admin',
                    'name' => 'Administrator Utama',
                    'email' => 'admin@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 2. DIREKTUR
                [
                    'role_id' => 2,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '197003151998031002',
                    'username' => 'direktur',
                    'name' => 'dr. H. Hendra Wijaya, Sp.B (Direktur)',
                    'email' => 'direktur@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 3. KEPALA BIDANG
                [
                    'role_id' => 3,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '197508202003121003',
                    'username' => 'kabid',
                    'name' => 'Drs. Bambang Sugipto, M.Kes (Kabid Penunjang)',
                    'email' => 'kabid@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 4. KEPALA BAGIAN
                [
                    'role_id' => 4,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '197811052005011004',
                    'username' => 'kabag',
                    'name' => 'Hj. Siti Rahmah, S.SE (Kabag TU)',
                    'email' => 'kabag@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 5. KEPALA SEKSI
                [
                    'role_id' => 5,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '198204122008041005',
                    'username' => 'kasi',
                    'name' => 'Ahmad Fauzi, S.T. (Kasi Penunjang Non-Medis)',
                    'email' => 'kasi@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 6. KEPALA SUB BAGIAN
                [
                    'role_id' => 6,
                    'room_id' => null,
                    'supporting_unit_id' => null,
                    'nip' => '198406152009022006',
                    'username' => 'kasubag',
                    'name' => 'Rina Maryana, S.Sos (Kasubag Keuangan)',
                    'email' => 'kasubag@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 7. KEPALA INSTALASI
                [
                    'role_id' => 7,
                    'room_id' => null,
                    'supporting_unit_id' => 8,
                    'nip' => '198609252011011007',
                    'username' => 'kainstal',
                    'name' => 'Ir. Joko Susilo, M.T. (Ka. Instalasi IPSRS)',
                    'email' => 'kainstal@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 8. SEKRETARIS INSTALASI
                [
                    'role_id' => 8,
                    'room_id' => null,
                    'supporting_unit_id' => 8,
                    'nip' => '198901122013022008',
                    'username' => 'sekrinstal',
                    'name' => 'Dewi Lestari, A.Md (Sekretaris IPSRS)',
                    'email' => 'sekrinstal@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 9. PJ RUANGAN
                [
                    'role_id' => 9,
                    'room_id' => 1,
                    'supporting_unit_id' => null,
                    'nip' => '198805182012012009',
                    'username' => 'pjruangan',
                    'name' => 'Ns. Budi Santoso, S.Kep (PJ Ruangan UGD)',
                    'email' => 'pjruangan@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 10. TEKNISI
                [
                    'role_id' => 10,
                    'room_id' => null,
                    'supporting_unit_id' => 8,
                    'nip' => '199002142014021010',
                    'username' => 'teknisi',
                    'name' => 'Agus Pratama (Teknisi Senior IPSRS)',
                    'email' => 'teknisi@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
                // 11. STAFF
                [
                    'role_id' => 11,
                    'room_id' => 1,
                    'supporting_unit_id' => null,
                    'nip' => '199512102018012011',
                    'username' => 'staff',
                    'name' => 'Eko Kurniawan (Staf UGD)',
                    'email' => 'staff@pesupeluh.rs',
                    'email_verified_at' => now(),
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => 1,
                ],
            ];

            foreach ($users as $userData) {
                User::create($userData);
            }
        }
    }
}
