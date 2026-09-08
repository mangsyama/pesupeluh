<?php

namespace App\Exports;

use App\Models\Role;
use App\Models\ServiceTicket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(public mixed $ticketsOrUser = null, public array $filters = [])
    {
    }

    public function collection()
    {
        if ($this->ticketsOrUser instanceof Collection) {
            return $this->ticketsOrUser;
        }

        $user = $this->ticketsOrUser;
        $query = ServiceTicket::with([
            'reporter:id,name',
            'room:id,name,building_name,location_floor',
            'category:id,name,supporting_unit_id',
            'category.supportingUnit:id,name',
            'assignments.technician:id,name',
        ])
        ->whereNull('deleted_at');

        if ($user) {
            $userId = (int) $user->id;

            if ($user->isReportOnly()) {
                $query->where('reporter_id', $userId);
            } elseif ($user->isTechnician()) {
                $query->whereHas('assignments', function ($q) use ($userId) {
                    $q->where('technician_id', $userId);
                });
            } elseif ($user->canDisposisi() && $user->supporting_unit_id) {
                $unitId = $user->supporting_unit_id;
                $query->whereHas('category', function ($q) use ($unitId) {
                    $q->where('supporting_unit_id', $unitId);
                });
            } elseif ((int) $user->role_id === Role::PJ_RUANGAN && $user->room_id) {
                $query->where('room_id', $user->room_id);
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
            'NO',
            'KODE TIKET',
            'TANGGAL',
            'UNIT',
            'RUANGAN',
            'PELAPOR',
            'PERMASALAHAN',
            'KATEGORI',
            'PRIORITAS',
            'DISPOSISI PETUGAS',
            'WAKTU RESPON',
            'STATUS',
            'KETERANGAN',
        ];
    }

    public function map($ticket): array
    {
        $this->rowNumber++;

        $statusMap = [
            'COMPLETED'          => 'SELESAI',
            'ASSIGNED'           => 'DITUGASKAN',
            'IN_PROGRESS'        => 'PROGRES',
            'PENDING_VALIDATION' => 'MENUNGGU',
            'PENDING'            => 'TERTUNDA',
            'CANCEL'             => 'BATAL',
        ];

        $techNames = $ticket->assignments
            ->map(fn($a) => $a->technician?->name)
            ->filter()
            ->implode(', ');

        $keterangan = '';
        if ($ticket->status === 'COMPLETED' && $ticket->completion_notes) {
            $keterangan = $ticket->completion_notes;
        } elseif ($ticket->status === 'PENDING' && $ticket->pending_reason) {
            $keterangan = $ticket->pending_reason;
        } elseif ($ticket->completion_notes) {
            $keterangan = $ticket->completion_notes;
        } elseif ($ticket->pending_reason) {
            $keterangan = $ticket->pending_reason;
        }

        return [
            $this->rowNumber,
            $ticket->ticket_number,
            $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-',
            $ticket->category?->supportingUnit?->name ?? '-',
            $ticket->room?->name ?? '-',
            $ticket->reporter?->name ?? '-',
            $ticket->problem_description ?? '-',
            $ticket->category?->name ?? '-',
            strtoupper($ticket->priority ?? '') === 'URGENT' ? 'URGENT' : 'RUTIN',
            $techNames ?: '-',
            $ticket->responded_at ? $ticket->responded_at->format('d/m/Y H:i') : '-',
            $statusMap[$ticket->status] ?? strtoupper($ticket->status),
            $keterangan ?: '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF059669']],
            ],
        ];
    }
}
