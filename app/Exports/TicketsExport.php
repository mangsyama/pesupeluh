<?php

namespace App\Exports;

use App\Models\ServiceTicket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(public mixed $user = null)
    {
    }

    public function collection()
    {
        $query = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name',
            'category:id,name',
        ])
        ->whereNull('deleted_at');

        if ($this->user) {
            $roleId = (int) ($this->user->role_id ?? 8);
            $userId = (int) $this->user->id;

            if ($roleId === 8 || ($this->user->role && $this->user->role->name === 'REPORTER')) {
                $query->where('reporter_id', $userId);
            } elseif (in_array($roleId, [5, 6]) && $this->user->supporting_unit_id) {
                $unitId = $this->user->supporting_unit_id;
                $query->whereHas('category.unitFeature', function ($q) use ($unitId) {
                    $q->where('supporting_unit_id', $unitId);
                });
            } elseif ($roleId === 7 && $this->user->room_id) {
                $query->where('room_id', $this->user->room_id);
            }
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'No. Tiket',
            'Tanggal Dibuat',
            'Pelapor',
            'Ruangan',
            'Kategori',
            'Deskripsi Masalah',
            'Prioritas',
            'Status',
            'Tanggal Selesai',
        ];
    }

    public function map($ticket): array
    {
        $statusMap = [
            'PENDING_VALIDATION' => 'Menunggu Validasi',
            'ASSIGNED'           => 'Ditugaskan',
            'IN_PROGRESS'        => 'Sedang Dikerjakan',
            'PENDING'            => 'Tertunda',
            'COMPLETED'          => 'Selesai',
            'CANCEL'             => 'Dibatalkan',
        ];

        return [
            $ticket->ticket_number,
            $ticket->created_at?->format('d/m/Y H:i'),
            $ticket->reporter?->name ?? '-',
            $ticket->room?->name ?? '-',
            $ticket->category?->name ?? '-',
            $ticket->problem_description,
            $ticket->priority ?? '-',
            $statusMap[$ticket->status] ?? $ticket->status,
            $ticket->resolved_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF4F46E5']],
            ],
        ];
    }
}
