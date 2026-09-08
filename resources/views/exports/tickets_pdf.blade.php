<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Tiket Layanan — PESU PELUH</title>
    <style>
        /* ── LOCAL POPPINS FONTS ── */
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 300;
            src: url("{{ public_path('fonts/poppins/Poppins-Light.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Regular.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: italic;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Italic.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url("{{ public_path('fonts/poppins/Poppins-Medium.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url("{{ public_path('fonts/poppins/Poppins-SemiBold.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('fonts/poppins/Poppins-Bold.ttf') }}") format("truetype");
        }

        /* ── MARGIN FISIK MULTI-PAGE DOMPDF ── */
        @page {
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 6px;
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
            width: 44px;
            padding-right: 8px;
        }

        .logo-cell img {
            width: 36px;
            height: 36px;
            display: block;
            margin-top: 4px;
        }

        .brand-title {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            line-height: 0.95;
            letter-spacing: 0.8px;
            margin: 0;
            padding: 0;
        }

        .brand-sub {
            font-size: 6.5px;
            color: #059669;
            font-weight: 600;
            margin-top: 1px;
            line-height: 1.0;
            padding: 0;
        }

        .meta-info {
            font-size: 6px;
            color: #64748b;
            margin-top: 1px;
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
            table-layout: auto;
        }

        .data-table th {
            background-color: #059669;
            color: #ffffff;
            font-size: 5.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 2.5px;
            border: 0.25pt solid #cbd5e1;
            text-align: center;
            vertical-align: middle;
            line-height: 1;
            white-space: nowrap !important;
        }

        .data-table td {
            font-size: 5.5px;
            padding: 3.5px 2.5px;
            border: 0.25pt solid #e2e8f0;
            vertical-align: middle;
            color: #1e293b;
            line-height: 1.25;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .data-table tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Lebar Kolom & Aturan Wrap */
        .col-no { width: 14px; text-align: center; white-space: nowrap !important; }
        .col-kode { width: 62px; font-weight: 600; color: #059669; text-align: center; white-space: nowrap !important; }
        .col-tgl { width: 50px; text-align: center; white-space: nowrap !important; }
        .col-unit { width: 46px; text-align: center; white-space: nowrap !important; }
        .col-ruangan { width: 54px; text-align: center; white-space: nowrap !important; }
        .col-pelapor { width: 58px; white-space: normal; word-wrap: break-word; }
        .col-masalah { width: auto; white-space: normal; word-wrap: break-word; }
        .col-kategori { width: 52px; text-align: center; white-space: normal; word-wrap: break-word; }
        .col-prioritas { width: 36px; text-align: center; white-space: nowrap !important; }
        .col-disposisi { width: 62px; white-space: normal; word-wrap: break-word; }
        .col-respon { width: 50px; text-align: center; white-space: nowrap !important; }
        .col-status { width: 44px; text-align: center; white-space: nowrap !important; }
        .col-ket { width: auto; white-space: normal; word-wrap: break-word; }
        .col-lampiran { width: 36px; text-align: center; vertical-align: middle; white-space: nowrap !important; padding: 2px 1px !important; }

        /* Akses Warna Teks Status & Prioritas (Semua Bold) */
        .text-green { color: #059669; font-weight: 700; }
        .text-blue { color: #2563eb; font-weight: 700; }
        .text-amber { color: #d97706; font-weight: 700; }
        .text-purple { color: #7c3aed; font-weight: 700; }
        .text-red { color: #dc2626; font-weight: 700; }
        .text-dash { text-align: center !important; color: #94a3b8; font-weight: 400; }

        /* Photos Layout Stacked (Atas - Bawah) */
        .photo-stack { text-align: center; margin: 0 auto; }
        .photo-box { margin-bottom: 2px; }
        .photo-box:last-child { margin-bottom: 0; }
        .photo-label { font-size: 4.5px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 1px; line-height: 1; text-align: center; }
        .photo-thumb {
            width: 17px;
            height: 17px;
            object-fit: cover;
            border-radius: 2px;
            border: 0.25pt solid #cbd5e1;
            margin: 0 auto;
            display: block;
        }
        .photo-dash { font-size: 5.5px; color: #94a3b8; font-weight: 400; line-height: 1; text-align: center; display: block; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 24px;
            color: #64748b;
            font-style: italic;
            font-size: 8px;
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
            <th class="col-no">NO</th>
            <th class="col-kode">KODE TIKET</th>
            <th class="col-tgl">TANGGAL</th>
            <th class="col-unit">UNIT</th>
            <th class="col-ruangan">RUANGAN</th>
            <th class="col-pelapor">PELAPOR</th>
            <th class="col-masalah">PERMASALAHAN</th>
            <th class="col-kategori">KATEGORI</th>
            <th class="col-prioritas">PRIORITAS</th>
            <th class="col-disposisi">DISPOSISI PETUGAS</th>
            <th class="col-respon">WAKTU RESPON</th>
            <th class="col-status">STATUS</th>
            <th class="col-ket">KETERANGAN</th>
            <th class="col-lampiran">LAMPIRAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $i => $ticket)
        @php
            $unitLabel = $ticket->category?->supportingUnit?->name;
            $roomLabel = $ticket->room?->name;
            $categoryLabel = $ticket->category?->name;

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
            } elseif ($ticket->completion_notes) {
                $keterangan = $ticket->completion_notes;
            } elseif ($ticket->pending_reason) {
                $keterangan = $ticket->pending_reason;
            }

            $respondedAtStr = $ticket->responded_at ? $ticket->responded_at->format('d/m/Y H:i') : null;
        @endphp
        <tr>
            {{-- 1. NO --}}
            <td class="col-no">{{ $i + 1 }}</td>

            {{-- 2. KODE TIKET --}}
            <td class="col-kode">{{ $ticket->ticket_number }}</td>

            {{-- 3. TANGGAL --}}
            <td class="col-tgl">{{ $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-' }}</td>

            {{-- 4. UNIT --}}
            @if($unitLabel)
                <td class="col-unit">{{ $unitLabel }}</td>
            @else
                <td class="col-unit text-dash">-</td>
            @endif

            {{-- 5. RUANGAN --}}
            @if($roomLabel)
                <td class="col-ruangan">{{ $roomLabel }}</td>
            @else
                <td class="col-ruangan text-dash">-</td>
            @endif

            {{-- 6. PELAPOR --}}
            @if($ticket->reporter?->name)
                <td class="col-pelapor">{{ $ticket->reporter->name }}</td>
            @else
                <td class="col-pelapor text-dash">-</td>
            @endif

            {{-- 7. PERMASALAHAN --}}
            @if($ticket->problem_description)
                <td class="col-masalah">{{ $ticket->problem_description }}</td>
            @else
                <td class="col-masalah text-dash">-</td>
            @endif

            {{-- 8. KATEGORI --}}
            @if($categoryLabel)
                <td class="col-kategori">{{ $categoryLabel }}</td>
            @else
                <td class="col-kategori text-dash">-</td>
            @endif

            {{-- 9. PRIORITAS --}}
            <td class="col-prioritas">
                @if(strtoupper($ticket->priority ?? '') === 'URGENT')
                    <span class="text-red">URGENT</span>
                @else
                    <span class="text-green">RUTIN</span>
                @endif
            </td>

            {{-- 10. DISPOSISI PETUGAS --}}
            @if($techNames)
                <td class="col-disposisi">{{ $techNames }}</td>
            @else
                <td class="col-disposisi text-dash">-</td>
            @endif

            {{-- 11. WAKTU RESPON --}}
            @if($respondedAtStr)
                <td class="col-respon">{{ $respondedAtStr }}</td>
            @else
                <td class="col-respon text-dash">-</td>
            @endif

            {{-- 12. STATUS --}}
            <td class="col-status">
                <span class="{{ $st['class'] }}">{{ $st['label'] }}</span>
            </td>

            {{-- 13. KETERANGAN --}}
            @if($keterangan)
                <td class="col-ket">{{ $keterangan }}</td>
            @else
                <td class="col-ket text-dash">-</td>
            @endif

            {{-- 14. LAMPIRAN --}}
            <td class="col-lampiran">
                @php
                    $resolveImg = function($attsList) {
                        if (!$attsList || $attsList->count() === 0) return null;
                        foreach ($attsList as $att) {
                            $rawPath = $att->file_path;
                            if (!$rawPath) continue;

                            if (str_starts_with($rawPath, 'data:image')) {
                                return $rawPath;
                            }

                            $normalized = str_replace('\\', '/', $rawPath);
                            $cleanPath = ltrim(str_replace(['/storage/', 'storage/', 'public/'], '', $normalized), '/');

                            $candidates = [
                                storage_path('app/public/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                                storage_path('app/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                                public_path('storage/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                                public_path(str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                                $rawPath,
                            ];

                            foreach ($candidates as $cand) {
                                if ($cand && file_exists($cand) && is_file($cand)) {
                                    $ext = strtolower(pathinfo($cand, PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'])) {
                                        return $cand;
                                    }
                                }
                            }
                        }
                        return null;
                    };

                    $allAtts = $ticket->attachments ?? collect();
                    $reporterAtts = $allAtts->filter(fn($a) => $a->uploaded_by == $ticket->reporter_id);
                    $completionAtts = $allAtts->filter(fn($a) => $a->uploaded_by != $ticket->reporter_id && !str_contains($a->file_path ?? '', 'ticket_arr_'));

                    $reporterImg = $resolveImg($reporterAtts);
                    $completionImg = $resolveImg($completionAtts);
                @endphp
                <div class="photo-stack">
                    <div class="photo-box">
                        <div class="photo-label">LAPORAN</div>
                        @if($reporterImg)
                            <img src="{{ $reporterImg }}" class="photo-thumb" alt="Laporan">
                        @else
                            <span class="photo-dash">-</span>
                        @endif
                    </div>
                    <div class="photo-box">
                        <div class="photo-label">SELESAI</div>
                        @if($completionImg)
                            <img src="{{ $completionImg }}" class="photo-thumb" alt="Selesai">
                        @else
                            <span class="photo-dash">-</span>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
