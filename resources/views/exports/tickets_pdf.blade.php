<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Tiket Layanan — PESU PELUH</title>
    <style>
        @page { margin: 16px 20px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 8px; color: #1e293b; background: #fff; }

        /* ── HEADER ── */
        .header { padding: 12px 0 10px; border-bottom: 2px solid #059669; margin-bottom: 12px; }
        .header table { width: 100%; border: none; }
        .header td { border: none; padding: 0; vertical-align: middle; }
        .logo-cell { width: 42px; padding-right: 8px; }
        .logo-cell img { width: 38px; height: 38px; }
        .brand-title { font-size: 14px; font-weight: 700; color: #059669; letter-spacing: 2px; text-transform: uppercase; line-height: 1; }
        .brand-sub { font-size: 7px; color: #64748b; line-height: 1.35; margin-top: 2px; font-weight: 500; letter-spacing: 0.2px; }
        .header-right { text-align: right; font-size: 7px; color: #94a3b8; line-height: 1.5; }

        /* ── DATA TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data-table thead tr { background: #059669; }
        .data-table th {
            padding: 5px 4px;
            text-align: left;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #ffffff;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #047857;
            white-space: nowrap;
        }
        .data-table th.center { text-align: center; }
        .data-table td {
            padding: 4px 4px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 7px;
            vertical-align: top;
            line-height: 1.35;
        }
        .data-table tr:nth-child(even) { background: #f8fafc; }
        .data-table tr:last-child td { border-bottom: none; }

        .ticket-num { font-weight: 700; color: #059669; }
        .col-no { width: 20px; text-align: center; }
        .col-kode { width: 68px; }
        .col-tgl { width: 58px; white-space: nowrap; }
        .col-unit { width: 105px; }
        .col-pelapor { width: 68px; }
        .col-masalah { width: auto; }
        .col-prioritas { width: 44px; text-align: center; }
        .col-disposisi { width: 68px; }
        .col-respon { width: 55px; white-space: nowrap; }
        .col-hasil { width: 50px; text-align: center; }
        .col-ket { width: 95px; }
        .col-lampiran { width: 70px; }

        /* ── BADGES ── */
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2px; }
        .badge-selesai { background: #d1fae5; color: #065f46; }
        .badge-menunggu { background: #fef3c7; color: #92400e; }
        .badge-tertunda { background: #fef3c7; color: #92400e; }
        .badge-progres { background: #ede9fe; color: #5b21b6; }
        .badge-ditugaskan { background: #dbeafe; color: #1e40af; }
        .badge-batal { background: #fee2e2; color: #991b1b; }
        .badge-normal { background: #dbeafe; color: #1e40af; }
        .badge-urgent { background: #fee2e2; color: #991b1b; }

        /* ── PHOTOS ── */
        .photo-thumb { width: 30px; height: 30px; object-fit: cover; border-radius: 3px; border: 1px solid #e2e8f0; margin: 1px; }
        .photo-count { font-size: 6.5px; color: #64748b; font-style: italic; }

        /* ── FOOTER ── */
        .footer { padding: 6px 0; border-top: 1px solid #e2e8f0; font-size: 7px; color: #94a3b8; text-align: center; margin-top: 6px; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 24px; color: #94a3b8; font-style: italic; font-size: 9px; }
    </style>
</head>
<body>

{{-- ═══════ HEADER ═══════ --}}
<div class="header">
    <table>
        <tr>
            <td class="logo-cell">
                @if(isset($logoPath) && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="brand-title">Pesu Peluh</div>
                <div class="brand-sub">
                    Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan
                </div>
            </td>
            <td class="header-right">
                Dicetak pada: {{ $exportedAt }}<br>
                Total data: {{ $tickets->count() }} tiket
            </td>
        </tr>
    </table>
</div>

{{-- ═══════ DATA TABLE ═══════ --}}
@if($tickets->isEmpty())
    <div class="empty-state">Belum ada data tiket yang tersedia.</div>
@else
<table class="data-table">
    <thead>
        <tr>
            <th class="col-no center">No</th>
            <th class="col-kode">Kode Tiket</th>
            <th class="col-tgl">Tanggal</th>
            <th class="col-unit">Unit — Ruangan</th>
            <th class="col-pelapor">Pelapor</th>
            <th class="col-masalah">Permasalahan</th>
            <th class="col-prioritas center">Prioritas</th>
            <th class="col-disposisi">Disposisi Petugas</th>
            <th class="col-respon">Waktu Respon</th>
            <th class="col-hasil center">Hasil</th>
            <th class="col-ket">Keterangan</th>
            <th class="col-lampiran">Lampiran</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $i => $ticket)
        @php
            $unitLabel = $ticket->category?->unitFeature?->supportingUnit?->name ?? '-';
            $roomLabel = $ticket->room?->name ?? '-';

            $techNames = $ticket->assignments->map(fn($a) => $a->technician?->name)->filter()->implode(', ');

            $statusMap = [
                'COMPLETED'          => ['label' => 'Selesai',    'class' => 'badge-selesai'],
                'ASSIGNED'           => ['label' => 'Ditugaskan', 'class' => 'badge-ditugaskan'],
                'IN_PROGRESS'        => ['label' => 'Progres',    'class' => 'badge-progres'],
                'PENDING_VALIDATION' => ['label' => 'Menunggu',   'class' => 'badge-menunggu'],
                'PENDING'            => ['label' => 'Tertunda',   'class' => 'badge-tertunda'],
                'CANCEL'             => ['label' => 'Batal',      'class' => 'badge-batal'],
            ];
            $st = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'class' => ''];

            $keterangan = '';
            if ($ticket->status === 'COMPLETED' && $ticket->completion_notes) {
                $keterangan = $ticket->completion_notes;
            } elseif ($ticket->status === 'PENDING' && $ticket->pending_reason) {
                $keterangan = $ticket->pending_reason;
            }
        @endphp
        <tr>
            <td class="col-no" style="text-align:center;">{{ $i + 1 }}</td>
            <td class="col-kode"><span class="ticket-num">{{ $ticket->ticket_number }}</span></td>
            <td class="col-tgl">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
            <td class="col-unit">{{ $unitLabel }} — {{ $roomLabel }}</td>
            <td class="col-pelapor">{{ $ticket->reporter?->name ?? '-' }}</td>
            <td class="col-masalah">{{ $ticket->problem_description }}</td>
            <td class="col-prioritas" style="text-align:center;">
                @if($ticket->priority === 'URGENT')
                    <span class="badge badge-urgent">URGENT</span>
                @else
                    <span class="badge badge-normal">NORMAL</span>
                @endif
            </td>
            <td class="col-disposisi">{{ $techNames ?: '-' }}</td>
            <td class="col-respon">{{ $ticket->responded_at ? $ticket->responded_at->format('d/m/Y H:i') : '-' }}</td>
            <td class="col-hasil" style="text-align:center;">
                <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
            </td>
            <td class="col-ket">{{ Str::limit($keterangan, 80) ?: '-' }}</td>
            <td class="col-lampiran">
                @if($ticket->attachments->count() > 0)
                    @foreach($ticket->attachments->take(3) as $att)
                        @php
                            $filePath = storage_path('app/public/' . str_replace('storage/', '', $att->file_path));
                        @endphp
                        @if(file_exists($filePath))
                            <img src="{{ $filePath }}" class="photo-thumb" alt="foto">
                        @endif
                    @endforeach
                    @if($ticket->attachments->count() > 3)
                        <span class="photo-count">+{{ $ticket->attachments->count() - 3 }} lainnya</span>
                    @endif
                @else
                    <span style="color:#94a3b8;">-</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ═══════ FOOTER ═══════ --}}
<div class="footer">
    Dokumen ini digenerate secara otomatis oleh sistem PESU PELUH &mdash; {{ $exportedAt }}
</div>

</body>
</html>
