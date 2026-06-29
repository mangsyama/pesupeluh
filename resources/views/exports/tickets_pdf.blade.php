<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tiket Layanan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

        .header { background: #4f46e5; color: white; padding: 20px 24px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
        .header .meta { font-size: 10px; opacity: 0.8; margin-top: 4px; }

        .summary { display: flex; gap: 12px; padding: 0 24px 16px; }
        .summary-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; }
        .summary-card .label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 20px; font-weight: 700; color: #1e293b; margin-top: 2px; }
        .summary-card.completed .value { color: #059669; }
        .summary-card.pending .value { color: #d97706; }

        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;
                         letter-spacing: 0.5px; padding: 0 24px 8px; border-bottom: 1px solid #e2e8f0; margin: 0 24px 12px; }

        table { width: calc(100% - 48px); margin: 0 24px; border-collapse: collapse; }
        thead tr { background: #f1f5f9; }
        th { padding: 8px 10px; text-align: left; font-size: 9px; font-weight: 700; text-transform: uppercase;
             color: #64748b; letter-spacing: 0.4px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #fafafa; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-pending   { background: #fef3c7; color: #92400e; }
        .badge-assigned  { background: #dbeafe; color: #1e40af; }
        .badge-progress  { background: #ede9fe; color: #5b21b6; }
        .badge-cancel    { background: #fee2e2; color: #991b1b; }

        .ticket-num { font-weight: 700; color: #4f46e5; }
        .desc { max-width: 180px; overflow: hidden; }

        .footer { margin-top: 16px; padding: 12px 24px; border-top: 1px solid #e2e8f0;
                  font-size: 9px; color: #94a3b8; text-align: center; }

        .empty-state { text-align: center; padding: 32px; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>

<div class="header">
    <h1>Laporan Tiket Layanan &mdash; PESUPELUH</h1>
    <div class="meta">Dicetak pada: {{ $exportedAt }} &nbsp;&bull;&nbsp; Total data: {{ $tickets->count() }} tiket</div>
</div>

<div class="summary">
    <div class="summary-card">
        <div class="label">Total Tiket</div>
        <div class="value">{{ $tickets->count() }}</div>
    </div>
    <div class="summary-card completed">
        <div class="label">Selesai</div>
        <div class="value">{{ $tickets->where('status', 'COMPLETED')->count() }}</div>
    </div>
    <div class="summary-card pending">
        <div class="label">Pending</div>
        <div class="value">{{ $tickets->whereIn('status', ['PENDING_VALIDATION','PENDING'])->count() }}</div>
    </div>
    <div class="summary-card">
        <div class="label">Dalam Proses</div>
        <div class="value">{{ $tickets->whereIn('status', ['ASSIGNED','IN_PROGRESS'])->count() }}</div>
    </div>
</div>

<div class="section-title">Daftar Tiket</div>

@if($tickets->isEmpty())
    <div class="empty-state">Belum ada data tiket yang tersedia.</div>
@else
<table>
    <thead>
        <tr>
            <th style="width:90px">No. Tiket</th>
            <th style="width:80px">Tanggal</th>
            <th style="width:100px">Pelapor</th>
            <th style="width:80px">Ruangan</th>
            <th style="width:90px">Kategori</th>
            <th>Deskripsi</th>
            <th style="width:70px">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $ticket)
        @php
            $statusMap = [
                'PENDING_VALIDATION' => ['label' => 'Menunggu',    'class' => 'badge-pending'],
                'ASSIGNED'           => ['label' => 'Ditugaskan',  'class' => 'badge-assigned'],
                'IN_PROGRESS'        => ['label' => 'Dikerjakan',  'class' => 'badge-progress'],
                'PENDING'            => ['label' => 'Tertunda',    'class' => 'badge-pending'],
                'COMPLETED'          => ['label' => 'Selesai',     'class' => 'badge-completed'],
                'CANCEL'             => ['label' => 'Dibatalkan',  'class' => 'badge-cancel'],
            ];
            $st = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'class' => ''];
        @endphp
        <tr>
            <td><span class="ticket-num">{{ $ticket->ticket_number }}</span></td>
            <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
            <td>{{ $ticket->reporter?->name ?? '-' }}</td>
            <td>{{ $ticket->room?->name ?? '-' }}</td>
            <td>{{ $ticket->category?->name ?? '-' }}</td>
            <td class="desc">{{ Str::limit($ticket->problem_description, 80) }}</td>
            <td><span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Dokumen ini digenerate secara otomatis oleh sistem PESUPELUH &mdash; {{ $exportedAt }}
</div>

</body>
</html>
