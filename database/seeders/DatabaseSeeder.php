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
            DB::table('roles')->insert([
                ['name' => 'ADMINISTRATOR', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index',
                    'service-management.rooms', 'service-management.categories', 'service-management.supporting-units',
                    'users.approvals', 'users.index', 'settings.index', 'design-system.index',
                ])],
                ['name' => 'DIRECTOR', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'settings.index',
                ])],
                ['name' => 'DIVISION_HEAD', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'settings.index',
                ])],
                ['name' => 'SECTION_HEAD', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'settings.index',
                ])],
                ['name' => 'UNIT_HEAD', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'reports.index', 'settings.index',
                ])],
                ['name' => 'TECHNICIAN', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'settings.index',
                ])],
                ['name' => 'ROOM_HEAD', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'reports-management.index', 'settings.index',
                ])],
                ['name' => 'REPORTER', 'page_permissions' => json_encode([
                    'dashboard', 'services.index', 'reports.history', 'settings.index',
                ])],
            ]);
        }

        // 2. Seed Divisions
        if (DB::table('divisions')->count() === 0) {
            DB::table('divisions')->insert([
                [
                    'name' => 'Penunjang Medik',
                    'description' => 'Mencakup pelaporan operasional unit penunjang pelayanan medis yang terdiri dari unit Farmasi, Radiologi, Laboratorium, dan CSSD.'
                ],
                [
                    'name' => 'Penunjang Non-Medik',
                    'description' => 'Mencakup pelaporan operasional unit penunjang non-medis yang terdiri dari unit Gizi, Laundry, Kesling, dan IPSRS.'
                ],
            ]);
        }

        // 3. Seed Supporting Units
        if (DB::table('supporting_units')->count() === 0) {
            DB::table('supporting_units')->insert([
                // Medik Units
                [
                    'division_id' => 1,
                    'name' => 'FARMASI',
                    'description' => 'Sistem pelaporan stok obat, resep, dan kebutuhan apotek.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 1,
                    'name' => 'RADIOLOGI',
                    'description' => 'Pelaporan pemeriksaan radiologi, hasil Rontgen, CT Scan, dan USG.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 1,
                    'name' => 'LABORATORIUM',
                    'description' => 'Pencatatan pemeriksaan darah, urine, patologi, dan laboratorium klinis.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 1,
                    'name' => 'CSSD',
                    'description' => 'Sistem pemantauan sterilisasi alkes medis dan instrumen operasi.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                // Non-Medik Units
                [
                    'division_id' => 2,
                    'name' => 'GIZI',
                    'description' => 'Pelaporan menu makanan pasien, distribusi gizi, dan operasional dapur RS.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 2,
                    'name' => 'LAUNDRY',
                    'description' => 'Pencatatan sirkulasi linen medis, kapasitas pencucian, dan inventaris laundry.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 2,
                    'name' => 'KESLING',
                    'description' => 'Sistem pelaporan sanitasi lingkungan, pengelolaan limbah B3, dan kualitas air.',
                    'status' => 'IN_DEVELOPMENT'
                ],
                [
                    'division_id' => 2,
                    'name' => 'IPSRS',
                    'description' => 'Sistem informasi pemeliharaan sarana prasarana, alkes, listrik, air, dan fasilitas RS.',
                    'status' => 'ACTIVE'
                ],
            ]);
        }

        // 4. Seed Rooms
        if (DB::table('rooms')->count() === 0) {
            DB::table('rooms')->insert([
                ['name' => 'Ruang IGD (Instalasi Gawat Darurat)', 'location_floor' => 'Lantai 1'],
                ['name' => 'Ruang ICU (Intensive Care Unit)', 'location_floor' => 'Lantai 2'],
                ['name' => 'Poliklinik Penyakit Dalam', 'location_floor' => 'Lantai 1'],
                ['name' => 'Poliklinik Anak', 'location_floor' => 'Lantai 1'],
                ['name' => 'Ruang Operasi (OK) Sentral', 'location_floor' => 'Lantai 3'],
                ['name' => 'Ruang Rawat Inap Melati - Kamar 101', 'location_floor' => 'Lantai 2'],
                ['name' => 'Ruang Rawat Inap Melati - Kamar 102', 'location_floor' => 'Lantai 2'],
                ['name' => 'Ruang Rawat Inap Dahlia - Kamar 201', 'location_floor' => 'Lantai 3'],
                ['name' => 'Laboratorium Patologi Klinik', 'location_floor' => 'Lantai 1'],
                ['name' => 'Apotek Rawat Jalan', 'location_floor' => 'Lantai 1'],
            ]);
        }

        // 5. Seed Unit Features & Feature Categories
        if (DB::table('unit_features')->count() === 0) {
            // IPSRS (id 8) Features
            $ipsrsPelaporanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 8, 'name' => 'Pelaporan']);
            $ipsrsKalibrasiId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 8, 'name' => 'Kalibrasi']);
            $ipsrsUsulanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 8, 'name' => 'Usulan']);

            // RADIOLOGI (id 2) Features
            $radiologiPelaporanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 2, 'name' => 'Pelaporan']);
            $radiologiUsulanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 2, 'name' => 'Usulan']);

            // FARMASI (id 1) Features
            $farmasiPelaporanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 1, 'name' => 'Pelaporan']);
            $farmasiUsulanId = DB::table('unit_features')->insertGetId(['supporting_unit_id' => 1, 'name' => 'Usulan']);

            // Seed Feature Categories under IPSRS - Pelaporan (since it is the active one)
            DB::table('feature_categories')->insert([
                // Pelaporan
                ['feature_id' => $ipsrsPelaporanId, 'name' => 'AC & Pendingin Ruangan', 'description' => 'Suhu ruangan tidak dingin, AC bocor, remote rusak, atau AC mati total.'],
                ['feature_id' => $ipsrsPelaporanId, 'name' => 'Listrik & Pencahayaan', 'description' => 'Lampu padam, stop kontak rusak/konslet, MCB turun/trip.'],
                ['feature_id' => $ipsrsPelaporanId, 'name' => 'Plumbing & Sanitasi', 'description' => 'Kran air patah/bocor, wastafel tersumbat, toilet mampet.'],
                ['feature_id' => $ipsrsPelaporanId, 'name' => 'Alat Medis (Alkes)', 'description' => 'Kerusakan fisik atau fungsi pada alat kesehatan medis.'],
                ['feature_id' => $ipsrsPelaporanId, 'name' => 'Sarana Fisik Gedung', 'description' => 'Pintu rusak, plafon bocor, dinding retak, kunci rusak.'],
            ]);
        }

        // 6. Create default users for testing if none exist
        if (User::count() <= 1) {
            // Delete any existing default to clean up
            User::query()->delete();

            $commonPassword = Hash::make('12345678');

            // 1. Admin User
            User::create([
                'name' => 'Administrator',
                'nip' => '197001011995011001',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => $commonPassword,
                'role_id' => 1, // ADMINISTRATOR
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 2. Director User
            User::create([
                'name' => 'Dr. Hermawan (Direktur)',
                'nip' => '197102021996021002',
                'username' => 'direktur',
                'email' => 'direktur@example.com',
                'password' => $commonPassword,
                'role_id' => 2, // DIRECTOR
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 3. Division Head
            User::create([
                'name' => 'Budi Santoso (Kabid Penunjang)',
                'nip' => '197203031997031003',
                'username' => 'kabid',
                'email' => 'kabid@example.com',
                'password' => $commonPassword,
                'role_id' => 3, // DIVISION_HEAD
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 4. Section Head
            User::create([
                'name' => 'Rina Amelia (Kasi Fasilitas)',
                'nip' => '197304041998042004',
                'username' => 'kasi',
                'email' => 'kasi@example.com',
                'password' => $commonPassword,
                'role_id' => 4, // SECTION_HEAD
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 5. Unit Head (IPRS)
            User::create([
                'name' => 'Hendra Wijaya (Ka. Unit IPSRS)',
                'nip' => '197405051999051005',
                'username' => 'kanit_ipsrs',
                'email' => 'kanit_ipsrs@example.com',
                'password' => $commonPassword,
                'role_id' => 5, // UNIT_HEAD
                'supporting_unit_id' => 8, // IPSRS
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 6. Technician 1
            User::create([
                'name' => 'Joko Prasetyo (Teknisi IPSRS)',
                'nip' => '197506062000061006',
                'username' => 'teknisi1',
                'email' => 'teknisi1@example.com',
                'password' => $commonPassword,
                'role_id' => 6, // TECHNICIAN
                'supporting_unit_id' => 8, // IPSRS
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 7. Technician 2
            User::create([
                'name' => 'Agus Setiawan (Teknisi IPSRS)',
                'nip' => '197607072001071007',
                'username' => 'teknisi2',
                'email' => 'teknisi2@example.com',
                'password' => $commonPassword,
                'role_id' => 6, // TECHNICIAN
                'supporting_unit_id' => 8, // IPSRS
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 8. Room Head (IGD)
            User::create([
                'name' => 'Siti Rahmah (Karu IGD)',
                'nip' => '197708082002082008',
                'username' => 'karu_igd',
                'email' => 'karu_igd@example.com',
                'password' => $commonPassword,
                'role_id' => 7, // ROOM_HEAD
                'room_id' => 1, // Ruang IGD
                'phone_number' => '081234567890',
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);

            // 9. Reporter / Staff
            User::create([
                'name' => 'Dian Lestari (Staff Perawat)',
                'nip' => '197809092003092009',
                'username' => 'staf_dian',
                'email' => 'staf_dian@example.com',
                'password' => $commonPassword,
                'role_id' => 8, // REPORTER
                'room_id' => 1, // Ruang IGD
                'phone_number' => '089876543210',
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);
        }
    }
}
