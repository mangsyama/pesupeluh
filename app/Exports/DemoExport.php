<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DemoExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect([
            ['Fitur' => 'PWA (Progressive Web App)', 'Status' => 'Terinstal & Aktif'],
            ['Fitur' => 'SweetAlert2 & Lucide Icons', 'Status' => 'Terinstal Lokal'],
            ['Fitur' => 'Face Login API (Opsi B)', 'Status' => 'Siap Konfigurasi'],
        ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Fitur Utama',
            'Status Pemasangan',
        ];
    }
}
