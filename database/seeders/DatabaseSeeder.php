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
                ['name' => 'ADMINISTRATOR'],
                ['name' => 'DIRECTOR'],
                ['name' => 'DIVISION_HEAD'],
                ['name' => 'SECTION_HEAD'],
                ['name' => 'UNIT_HEAD'],
                ['name' => 'TECHNICIAN'],
                ['name' => 'ROOM_HEAD'],
                ['name' => 'REPORTER'],
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
                // Kalibrasi
                ['feature_id' => $ipsrsKalibrasiId, 'name' => 'Kalibrasi Tensimeter', 'description' => 'Pengujian akurasi tekanan pada tensimeter raksa/digital.'],
                ['feature_id' => $ipsrsKalibrasiId, 'name' => 'Kalibrasi ECG / EKG', 'description' => 'Sertifikasi akurasi modul rekam jantung.'],
                // Usulan
                ['feature_id' => $ipsrsUsulanId, 'name' => 'Usulan Renovasi Ruangan', 'description' => 'Usulan perbaikan atau tata ruang unit.'],
            ]);
        }

        // 6. Create default users for testing if none exist
        if (User::count() === 0) {
            // Admin User
            User::create([
                'name' => 'Administrator',
                'nip' => '197001011995011001',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => 1, // ADMINISTRATOR
                'is_active' => true,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);
        }
    }
}
