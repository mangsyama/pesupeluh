<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Tiket Layanan — PESU PELUH</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');
        
        /* ── MARGIN FISIK MULTI-PAGE DOMPDF ── */
        @page {
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 6.5px;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* ── HEADER IDENTITAS DOKUMEN (KIRI ATAS TABEL) ── */
        .pdf-header {
            margin-bottom: 8px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 46px;
            padding-right: 10px;
        }

        .logo-cell img {
            width: 38px;
            height: 38px;
            display: block;
            margin-top: 5px;
        }

        .brand-title {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            line-height: 0.95;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0;
            padding: 0;
        }

        .brand-sub {
            font-size: 6.5px;
            color: #059669;
            font-weight: 600;
            margin-top: 0px;
            line-height: 1.0;
            padding: 0;
        }

        .meta-info {
            font-size: 6px;
            color: #64748b;
            margin-top: 0px;
            line-height: 1.0;
            font-weight: 400;
            padding: 0;
        }

        /* ── PENGATURAN REPEAT HEADER DI SETIAP HALAMAN ── */
        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        /* ── DATA TABLE (GARIS ABU-ABU NETRAL TIPIS & RAPI) ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: #059669;
            color: #ffffff;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 0.25pt solid #cbd5e1;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .data-table td {
            font-size: 6.5px;
            padding: 4px 4px;
            border: 0.25pt solid #e2e8f0;
            vertical-align: middle;
            color: #1e293b;
            line-height: 1.3;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .data-table tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Lebar Kolom */
        .col-no { width: 18px; text-align: center; }
        .col-kode { width: 75px; white-space: nowrap; font-weight: 600; color: #059669; text-align: center; }
        .col-tgl { width: 58px; white-space: nowrap; text-align: center; }
        .col-unit { width: 65px; text-align: center; }
        .col-ruangan { width: 65px; text-align: center; }
        .col-pelapor { width: 70px; }
        .col-masalah { width: auto; }
        .col-prioritas { width: 45px; text-align: center; }
        .col-disposisi { width: 75px; }
        .col-respon { width: 58px; white-space: nowrap; }
        .col-hasil { width: 50px; text-align: center; }
        .col-ket { width: 95px; }

        /* Akses Warna Teks Status & Prioritas (Semua Bold) */
        .text-green { color: #059669; font-weight: 700; }
        .text-blue { color: #2563eb; font-weight: 700; }
        .text-amber { color: #d97706; font-weight: 700; }
        .text-purple { color: #7c3aed; font-weight: 700; }
        .text-red { color: #dc2626; font-weight: 700; }
        .text-dash { text-align: center !important; color: #94a3b8; font-weight: 400; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 24px;
            color: #64748b;
            font-style: italic;
            font-size: 8.5px;
            border: 0.25pt solid #cbd5e1;
        }
    </style>
</head>
<body>

{{-- ═══════ HEADER IDENTITAS PESU PELUH (KIRI ATAS TABEL) ═══════ --}}
<div class="pdf-header">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(isset($logoBase64) && $logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @elseif(isset($logoPath) && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="brand-title">Pesu Peluh</div>
                <div class="brand-sub">Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan</div>
                <div class="meta-info">
                    Laporan Tiket Layanan &mdash; @if(isset($unitName) && $unitName){{ $unitName }}@else Semua Data @endif
                    &nbsp;|&nbsp; Generated at: {{ $exportedAt }}
                </div>
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
            <th class="col-no">No</th>
            <th class="col-kode">Kode Tiket</th>
            <th class="col-tgl">Tanggal</th>
            <th class="col-unit">Unit</th>
            <th class="col-ruangan">Ruangan</th>
            <th class="col-pelapor">Pelapor</th>
            <th class="col-masalah">Permasalahan</th>
            <th class="col-prioritas">Prioritas</th>
            <th class="col-disposisi">Disposisi Petugas</th>
            <th class="col-respon">Waktu Respon</th>
            <th class="col-hasil">Status</th>
            <th class="col-ket">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $i => $ticket)
        @php
            $unitLabel = $ticket->category?->supportingUnit?->name;
            $roomLabel = $ticket->room?->name;

            $techNames = $ticket->assignments->map(fn($a) => $a->technician?->name)->filter()->implode(', ');

            // HASIL SELALU HURUF KAPITAL
            $statusMap = [
                'COMPLETED'          => ['label' => 'SELESAI',    'class' => 'text-green'],
                'ASSIGNED'           => ['label' => 'DITUGASKAN', 'class' => 'text-blue'],
                'IN_PROGRESS'        => ['label' => 'PROGRES',    'class' => 'text-purple'],
                'PENDING_VALIDATION' => ['label' => 'MENUNGGU',   'class' => 'text-amber'],
                'PENDING'            => ['label' => 'TERTUNDA',   'class' => 'text-amber'],
                'CANCEL'             => ['label' => 'BATAL',      'class' => 'text-red'],
            ];
            $st = $statusMap[$ticket->status] ?? ['label' => strtoupper($ticket->status), 'class' => ''];

            $keterangan = '';
            if ($ticket->status === 'COMPLETED' && $ticket->completion_notes) {
                $keterangan = $ticket->completion_notes;
            } elseif ($ticket->status === 'PENDING' && $ticket->pending_reason) {
                $keterangan = $ticket->pending_reason;
            }

            $respondedAtStr = $ticket->responded_at ? $ticket->responded_at->format('d/m/Y H:i') : null;
        @endphp
        <tr>
            {{-- 1. No --}}
            <td class="col-no">{{ $i + 1 }}</td>

            {{-- 2. Kode Tiket --}}
            <td class="col-kode">{{ $ticket->ticket_number }}</td>

            {{-- 3. Tanggal --}}
            <td class="col-tgl">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>

            {{-- 4. Unit (Pisah Kolom) --}}
            @if($unitLabel)
                <td class="col-unit">{{ $unitLabel }}</td>
            @else
                <td class="col-unit text-dash">-</td>
            @endif

            {{-- 5. Ruangan (Pisah Kolom) --}}
            @if($roomLabel)
                <td class="col-ruangan">{{ $roomLabel }}</td>
            @else
                <td class="col-ruangan text-dash">-</td>
            @endif

            {{-- 6. Pelapor --}}
            @if($ticket->reporter?->name)
                <td class="col-pelapor">{{ $ticket->reporter->name }}</td>
            @else
                <td class="col-pelapor text-dash">-</td>
            @endif

            {{-- 7. Permasalahan --}}
            @if($ticket->problem_description)
                <td class="col-masalah">{{ $ticket->problem_description }}</td>
            @else
                <td class="col-masalah text-dash">-</td>
            @endif

            {{-- 8. Prioritas (RUTIN hijau / URGENT merah) --}}
            <td class="col-prioritas">
                @if($ticket->priority === 'URGENT')
                    <span class="text-red">URGENT</span>
                @else
                    <span class="text-green">RUTIN</span>
                @endif
            </td>

            {{-- 9. Disposisi Petugas --}}
            @if($techNames)
                <td class="col-disposisi">{{ $techNames }}</td>
            @else
                <td class="col-disposisi text-dash">-</td>
            @endif

            {{-- 10. Waktu Respon --}}
            @if($respondedAtStr)
                <td class="col-respon">{{ $respondedAtStr }}</td>
            @else
                <td class="col-respon text-dash">-</td>
            @endif

            {{-- 11. Status (Huruf Kapital Semua) --}}
            <td class="col-hasil">
                <span class="{{ $st['class'] }}">{{ $st['label'] }}</span>
            </td>

            {{-- 12. Keterangan --}}
            @if($keterangan)
                <td class="col-ket">{{ Str::limit($keterangan, 80) }}</td>
            @else
                <td class="col-ket text-dash">-</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
