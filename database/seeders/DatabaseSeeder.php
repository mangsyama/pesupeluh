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
                ['name' => 'Ruang IGD (Instalasi Gawat Darurat)', 'building_name' => 'Gedung Utama A', 'location_floor' => 'Lantai 1'],
                ['name' => 'Ruang ICU (Intensive Care Unit)', 'building_name' => 'Gedung Utama A', 'location_floor' => 'Lantai 2'],
                ['name' => 'Poliklinik Penyakit Dalam', 'building_name' => 'Gedung Rawat Jalan B', 'location_floor' => 'Lantai 1'],
                ['name' => 'Poliklinik Anak', 'building_name' => 'Gedung Rawat Jalan B', 'location_floor' => 'Lantai 1'],
                ['name' => 'Ruang Operasi (OK) Sentral', 'building_name' => 'Gedung Bedah C', 'location_floor' => 'Lantai 3'],
                ['name' => 'Ruang Rawat Inap Melati - Kamar 101', 'building_name' => 'Gedung Rawat Inap D', 'location_floor' => 'Lantai 2'],
                ['name' => 'Ruang Rawat Inap Melati - Kamar 102', 'building_name' => 'Gedung Rawat Inap D', 'location_floor' => 'Lantai 2'],
                ['name' => 'Ruang Rawat Inap Dahlia - Kamar 201', 'building_name' => 'Gedung Rawat Inap D', 'location_floor' => 'Lantai 3'],
                ['name' => 'Laboratorium Patologi Klinik', 'building_name' => 'Gedung Penunjang E', 'location_floor' => 'Lantai 1'],
                ['name' => 'Apotek Rawat Jalan', 'building_name' => 'Gedung Rawat Jalan B', 'location_floor' => 'Lantai 1'],
            ]);
        }

        // 4. Seed Issue Categories under IPSRS (supporting_unit_id = 8)
        if (DB::table('issue_categories')->count() === 0) {
            DB::table('issue_categories')->insert([
                ['supporting_unit_id' => 8, 'name' => 'AC & Pendingin Ruangan', 'description' => 'Suhu ruangan tidak dingin, AC bocor, remote rusak, atau AC mati total.'],
                ['supporting_unit_id' => 8, 'name' => 'Listrik & Pencahayaan', 'description' => 'Lampu padam, stop kontak rusak/konslet, MCB turun/trip.'],
                ['supporting_unit_id' => 8, 'name' => 'Plumbing & Sanitasi', 'description' => 'Kran air patah/bocor, wastafel tersumbat, toilet mampet.'],
                ['supporting_unit_id' => 8, 'name' => 'Alat Medis (Alkes)', 'description' => 'Kerusakan fisik atau fungsi pada alat kesehatan medis.'],
                ['supporting_unit_id' => 8, 'name' => 'Sarana Fisik Gedung', 'description' => 'Pintu rusak, plafon bocor, dinding retak, kunci rusak.'],
            ]);
        }

        // 5. Seed Users (Only ADMINISTRATOR for production readiness)
        if (DB::table('users')->count() === 0) {
            $defaultPassword = Hash::make('password123');

            // 1. ADMINISTRATOR
            User::create([
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
            ]);
        }
    }
}
