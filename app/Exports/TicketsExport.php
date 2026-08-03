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
    public function __construct(public mixed $user = null, public array $filters = [])
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
            $userId = (int) $this->user->id;

            if ($this->user->isReportOnly()) {
                $query->where('reporter_id', $userId);
            } elseif ($this->user->canDisposisi() && $this->user->supporting_unit_id) {
                $unitId = $this->user->supporting_unit_id;
                $query->whereHas('category', function ($q) use ($unitId) {
                    $q->where('supporting_unit_id', $unitId);
                });
            } elseif ((int) $this->user->role_id === \App\Models\Role::PJ_RUANGAN && $this->user->room_id) {
                $query->where('room_id', $this->user->room_id);
            }
        }

        // Filter unit penunjang (supporting_unit_id)
        if (!empty($this->filters['unit_id'])) {
            $unitId = $this->filters['unit_id'];
            $query->whereHas('category', function ($q) use ($unitId) {
                $q->where('supporting_unit_id', $unitId);
            });
        }

        // Filter kategori kerusakan (category_id)
        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        // Filter ruangan (room_id)
        if (!empty($this->filters['room_id'])) {
            $query->where('room_id', $this->filters['room_id']);
        }

        // Filter staf/pelapor (reporter_id)
        if (!empty($this->filters['reporter_id'])) {
            $query->where('reporter_id', $this->filters['reporter_id']);
        }

        // Filter range tanggal
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        if (!$startDate && !$endDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
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
