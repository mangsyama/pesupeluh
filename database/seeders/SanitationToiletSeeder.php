<?php

namespace Database\Seeders;

use App\Models\SanitationToilet;
use Illuminate\Database\Seeder;

class SanitationToiletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $toilets = [
            // Gedung A (8 Titik)
            [
                'code' => 'QR-TLT-A1-01',
                'name' => 'Toilet Pengunjung Timur (Pria)',
                'building' => 'Gedung A',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A1-02',
                'name' => 'Toilet Pengunjung Timur (Wanita)',
                'building' => 'Gedung A',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A1-03',
                'name' => 'Toilet Poliklinik Eksekutif',
                'building' => 'Gedung A',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A1-04',
                'name' => 'Toilet Umum Disabilitas Barat',
                'building' => 'Gedung A',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A2-01',
                'name' => 'Toilet Poliklinik Rawat Jalan (Pria)',
                'building' => 'Gedung A',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A2-02',
                'name' => 'Toilet Poliklinik Rawat Jalan (Wanita)',
                'building' => 'Gedung A',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A2-03',
                'name' => 'Toilet Kantor Manajemen & Keuangan',
                'building' => 'Gedung A',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-A3-01',
                'name' => 'Toilet Aula Utama & Diklat',
                'building' => 'Gedung A',
                'floor' => 'Lantai 3',
                'status' => 'AKTIF',
            ],

            // Gedung B (7 Titik)
            [
                'code' => 'QR-TLT-B1-01',
                'name' => 'Toilet Kamar Bedah & Recovery',
                'building' => 'Gedung B',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B1-02',
                'name' => 'Toilet Radiologi & Rontgen',
                'building' => 'Gedung B',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B1-03',
                'name' => 'Toilet Farmasi Rawat Jalan',
                'building' => 'Gedung B',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B2-01',
                'name' => 'Toilet Rawat Inap Cendrawasih (Sayap Barat)',
                'building' => 'Gedung B',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B2-02',
                'name' => 'Toilet Rawat Inap Cendrawasih (Sayap Timur)',
                'building' => 'Gedung B',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B3-01',
                'name' => 'Toilet Rawat Inap Merak (VIP)',
                'building' => 'Gedung B',
                'floor' => 'Lantai 3',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-B3-02',
                'name' => 'Toilet Staf Nurse Station Gedung B',
                'building' => 'Gedung B',
                'floor' => 'Lantai 3',
                'status' => 'AKTIF',
            ],

            // Gedung C (10 Titik)
            [
                'code' => 'QR-TLT-C1-01',
                'name' => 'Toilet IGD Resusitasi & Triase',
                'building' => 'Gedung C',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C1-02',
                'name' => 'Toilet Umum Disabilitas IGD',
                'building' => 'Gedung C',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C1-03',
                'name' => 'Toilet Ruang Tunggu Keluarga Pasien IGD',
                'building' => 'Gedung C',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C1-04',
                'name' => 'Toilet Laboratorium PK',
                'building' => 'Gedung C',
                'floor' => 'Lantai 1',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C2-01',
                'name' => 'Toilet ICU & HCU Pengunjung',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C2-02',
                'name' => 'Toilet Umum Wanita Sayap Timur',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C2-03',
                'name' => 'Toilet Umum Pria Sayap Timur',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C2-04',
                'name' => 'Toilet Kamar Bersalin (VK)',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C3-01',
                'name' => 'Toilet Ruang Rawat Bayi & Peristi',
                'building' => 'Gedung C',
                'floor' => 'Lantai 3',
                'status' => 'AKTIF',
            ],
            [
                'code' => 'QR-TLT-C3-02',
                'name' => 'Toilet Hemodialisa (Cuci Darah)',
                'building' => 'Gedung C',
                'floor' => 'Lantai 3',
                'status' => 'AKTIF',
            ],
        ];

        foreach ($toilets as $t) {
            SanitationToilet::updateOrCreate(
                ['code' => $t['code']],
                $t
            );
        }
    }
}

