<?php

namespace App\Http\Controllers;

use App\Models\SupportingUnit;
use App\Models\Room;
use App\Models\IssueCategory;
use App\Models\SanitationToilet;
use App\Models\SanitationTicket;
use App\Models\SanitationInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PanSinarController extends Controller
{
    /**
     * Shared master data & metrics for all PAN-SINAR views.
     */
    private function getPanSinarData()
    {
        $unit = SupportingUnit::where('slug', 'kesling')
            ->orWhereRaw('LOWER(name) LIKE ?', ['%kesling%'])
            ->first();

        $rooms = Room::orderBy('name', 'asc')->get();
        $categories = $unit ? IssueCategory::where('supporting_unit_id', $unit->id)->get() : collect();

        // 1. Toilets from Database
        $toilets = SanitationToilet::orderBy('building', 'asc')
            ->orderBy('floor', 'asc')
            ->orderBy('code', 'asc')
            ->get();

        // 2. Sanitation & Toilet Tickets from Database
        $tickets = SanitationTicket::with(['toilet', 'resolver'])
            ->latest()
            ->get();

        // 3. Inspections from Database
        $inspections = SanitationInspection::with(['toilet', 'inspectorUser'])
            ->latest()
            ->get();

        // Statistics & Analytics
        $totalTicketsCount = $tickets->count();
        $waitingTicketsCount = $tickets->where('status', 'MENUNGGU')->count();
        $inProgressTicketsCount = $tickets->where('status', 'DIKERJAKAN')->count();
        $openTicketsCount = $waitingTicketsCount + $inProgressTicketsCount;
        $resolvedTicketsCount = $tickets->where('status', 'SELESAI')->count();
        $normalTicketsCount = $tickets->where('urgency', 'NORMAL')->count();
        $emergencyTicketsCount = $tickets->where('urgency', 'DARURAT')->count();
        $panicIncidentCount = $tickets->where('urgency', 'DARURAT')->whereIn('status', ['MENUNGGU', 'DIKERJAKAN'])->count();

        $totalToiletsCount = $toilets->count();
        $activeToiletsCount = $toilets->where('status', 'AKTIF')->count();
        $nonActiveToiletsCount = $totalToiletsCount - $activeToiletsCount;

        $totalInspectionsCount = $inspections->count();
        $excellentInspectionsCount = $inspections->where('status', 'EXCELLENT')->count();
        $attentionInspectionsCount = $inspections->where('status', 'NEED_ATTENTION')->count();

        $todayInspections = $inspections->filter(fn($i) => $i->created_at && $i->created_at->isToday());
        $todayInspectedCount = $todayInspections->pluck('toilet_id')->filter()->unique()->count();
        $dailyChecklistProgress = $totalToiletsCount > 0 
            ? ($todayInspectedCount . ' / ' . $totalToiletsCount . ' Titik')
            : '0 / 0 Titik';
        $dailyChecklistPercent = $totalToiletsCount > 0 
            ? min(100, round(($todayInspectedCount / $totalToiletsCount) * 100)) 
            : 0;

        $slaComplianceRate = $totalTicketsCount > 0 
            ? round(($resolvedTicketsCount / $totalTicketsCount) * 100, 1) 
            : 100.0;

        // 7-Day Trend Analytics from real data
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayTickets = $tickets->filter(fn($t) => $t->created_at && $t->created_at->isSameDay($date));
            $trendData[] = [
                'date' => $date->translatedFormat('d M'),
                'normal' => $dayTickets->where('urgency', 'NORMAL')->count(),
                'darurat' => $dayTickets->where('urgency', 'DARURAT')->count(),
                'selesai' => $dayTickets->where('status', 'SELESAI')->count(),
            ];
        }

        // 4 Pillars Compliance % from inspections
        $pillarStats = [
            'air' => $totalInspectionsCount > 0 ? round(($inspections->where('water_flush_ok', true)->count() / $totalInspectionsCount) * 100) : 100,
            'lantai' => $totalInspectionsCount > 0 ? round(($inspections->where('floor_mirror_clean', true)->count() / $totalInspectionsCount) * 100) : 100,
            'sabun' => $totalInspectionsCount > 0 ? round(($inspections->where('supplies_ready', true)->count() / $totalInspectionsCount) * 100) : 100,
            'aroma' => $totalInspectionsCount > 0 ? round(($inspections->where('air_fragrance_ok', true)->count() / $totalInspectionsCount) * 100) : 100,
        ];

        // Waste Management Data (Placeholder / Standby)
        $wasteData = [
            'tps_b3_capacity_percent' => 0,
            'tps_b3_current_kg' => 0,
            'tps_b3_max_kg' => 800,
            'tps_domestic_capacity_percent' => 0,
            'today_weights' => [
                'medis_infeksius_kg' => 0,
                'medis_tajam_kg' => 0,
                'patologi_kg' => 0,
                'domestik_kg' => 0,
            ],
            'recent_logs' => [],
            'manifests' => []
        ];

        $qrConfig = [
            'locations' => $toilets,
            'emergency_contacts' => [
                [
                    'role' => 'Koordinator Cleaning Service',
                    'name' => 'Bpk. Mulyadi',
                    'phone' => '081234567890',
                    'active' => true
                ],
                [
                    'role' => 'Teknisi Siaga Sarpras / Sanitasi',
                    'name' => 'Tim Sarpras Jaga 24 Jam',
                    'phone' => '081987654321',
                    'active' => true
                ],
                [
                    'role' => 'Security & Pos Komando IGD',
                    'name' => 'Posko Keamanan Utama',
                    'phone' => '081333444555',
                    'active' => true
                ]
            ],
            'panic_settings' => [
                'hold_duration_seconds' => 3,
                'sound_alert' => true,
                'auto_broadcast_wa' => true,
            ]
        ];

        return [
            'unit' => $unit,
            'rooms' => $rooms,
            'categories' => $categories,
            'stats' => [
                'totalTicketsCount' => $totalTicketsCount,
                'openTicketsCount' => $openTicketsCount,
                'waitingTicketsCount' => $waitingTicketsCount,
                'inProgressTicketsCount' => $inProgressTicketsCount,
                'resolvedTicketsCount' => $resolvedTicketsCount,
                'normalTicketsCount' => $normalTicketsCount,
                'emergencyTicketsCount' => $emergencyTicketsCount,
                'panicIncidentCount' => $panicIncidentCount,
                'slaComplianceRate' => $slaComplianceRate,
                'dailyChecklistProgress' => $dailyChecklistProgress,
                'dailyChecklistPercent' => $dailyChecklistPercent,
                'totalToiletsCount' => $totalToiletsCount,
                'activeToiletsCount' => $activeToiletsCount,
                'nonActiveToiletsCount' => $nonActiveToiletsCount,
                'totalInspectionsCount' => $totalInspectionsCount,
                'excellentInspectionsCount' => $excellentInspectionsCount,
                'attentionInspectionsCount' => $attentionInspectionsCount,
                'trendData' => $trendData,
                'pillarStats' => $pillarStats,
            ],
            'tickets' => $tickets,
            'inspections' => $inspections,
            'wasteData' => $wasteData,
            'toilets' => $toilets,
            'qrConfig' => $qrConfig,
            'personInCharge' => [
                'name' => 'Ns. I Nyoman Sukiman Sukawan, S.ST',
                'role' => 'Kasi Penunjang Non Medik',
                'hospital' => 'RSUD Giri Asih',
            ]
        ];
    }

    /**
     * Page 1: Dashboard Ringkasan PAN-SINAR
     */
    public function index(Request $request)
    {
        return Inertia::render('Service/Kesling/Dashboard', $this->getPanSinarData());
    }

    /**
     * Page 2: Aduan Sanitasi (Sistem Tiket)
     */
    public function sanitasi(Request $request)
    {
        return Inertia::render('Service/Kesling/AduanSanitasi', $this->getPanSinarData());
    }

    /**
     * Page 3: Monitoring Ceklis Harian (Inspeksi Patroli)
     */
    public function ceklis(Request $request)
    {
        return Inertia::render('Service/Kesling/CeklisHarian', $this->getPanSinarData());
    }

    /**
     * Page 4: Pengelolaan Limbah B3 & Domestik
     */
    public function limbah(Request $request)
    {
        return Inertia::render('Service/Kesling/PengelolaanLimbah', $this->getPanSinarData());
    }

    /**
     * Page 5: Manajemen Toilet (QR Code & Panic Button)
     */
    public function qr(Request $request)
    {
        return Inertia::render('Service/Kesling/ManajemenToilet', $this->getPanSinarData());
    }

    /**
     * Store new sanitation/toilet ticket from staff or public.
     */
    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'nullable',
            'toilet_id' => 'nullable|exists:sanitation_toilets,id',
            'problem_description' => 'required|string|min:5',
            'urgency' => 'nullable|string',
            'reporter_name' => 'nullable|string|max:100',
            'reporter_phone' => 'nullable|string|max:30',
        ]);

        $toiletId = $validated['toilet_id'] ?? null;
        if (!$toiletId) {
            $toiletId = SanitationToilet::first()?->id;
        }

        $urgency = strtoupper($validated['urgency'] ?? 'NORMAL');
        if ($urgency !== 'DARURAT') {
            $urgency = 'NORMAL';
        }

        $ticket = SanitationTicket::create([
            'toilet_id' => $toiletId,
            'problem_description' => $validated['problem_description'],
            'urgency' => $urgency,
            'status' => 'MENUNGGU',
            'reporter_name' => $validated['reporter_name'] ?: 'Petugas Internal Kesling',
            'reporter_phone' => $validated['reporter_phone'] ?? null,
            'is_anonymous' => false,
        ]);

        return redirect()->back()->with('success', "Tiket aduan sanitasi {$ticket->ticket_number} berhasil didaftarkan dan didisposisikan ke tim Cleaning Service & Kesling.");
    }

    /**
     * Mark a ticket as in progress (Sedang Dikerjakan).
     */
    public function startTicket(Request $request, $id)
    {
        $query = SanitationTicket::query();
        if (is_numeric($id)) {
            $query->where('id', (int) $id);
        } elseif (\Illuminate\Support\Str::isUuid((string) $id)) {
            $query->where('uuid', (string) $id);
        } else {
            $query->where('ticket_number', (string) $id);
        }
        $ticket = $query->firstOrFail();

        $ticket->update([
            'status' => 'DIKERJAKAN',
        ]);

        return redirect()->back()->with('success', "Tiket {$ticket->ticket_number} berhasil dimulai dan status diubah menjadi Sedang Dikerjakan.");
    }

    /**
     * Resolve a ticket with before/after photos and validation notes.
     */
    public function resolveTicket(Request $request, $id)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:5',
            'evidence_after' => 'nullable|string',
            'evidence_after_file' => 'nullable|image|max:10240',
        ]);

        $query = SanitationTicket::query();
        if (is_numeric($id)) {
            $query->where('id', (int) $id);
        } elseif (\Illuminate\Support\Str::isUuid((string) $id)) {
            $query->where('uuid', (string) $id);
        } else {
            $query->where('ticket_number', (string) $id);
        }
        $ticket = $query->firstOrFail();

        $evidenceAfter = $validated['evidence_after'] ?? null;
        if ($request->hasFile('evidence_after_file')) {
            $path = $request->file('evidence_after_file')->store('sanitation/resolutions', 'public');
            $evidenceAfter = '/storage/' . $path;
        } elseif ($request->filled('evidence_after') && str_starts_with($request->input('evidence_after'), 'data:image')) {
            $evidenceAfter = \App\Services\SecureFileUpload::saveBase64($request->input('evidence_after'), 'attachments/sanitasi', 'resolusi_');
        }

        $ticket->update([
            'status' => 'SELESAI',
            'resolution_notes' => $validated['resolution_notes'],
            'evidence_after' => $evidenceAfter ?: ($ticket->urgency === 'DARURAT' ? null : ($ticket->evidence_after ?: null)),
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return redirect()->back()->with('success', "Tiket {$ticket->ticket_number} berhasil diselesaikan dan verifikasi penyelesaian telah tercatat.");
    }

    /**
     * Store routine toilet sanitation checklist.
     */
    public function storeInspection(Request $request)
    {
        $validated = $request->validate([
            'inspection_id' => 'nullable|exists:sanitation_inspections,id',
            'toilet_id' => 'nullable|exists:sanitation_toilets,id',
            'inspection_date' => 'nullable|date',
            'location_point' => 'nullable|string',
            'water_flush_ok' => 'required|boolean',
            'floor_mirror_clean' => 'required|boolean',
            'supplies_ready' => 'required|boolean',
            'air_fragrance_ok' => 'required|boolean',
            'notes' => 'required|string',
            'photo' => 'nullable',
        ]);

        // Find matching toilet by explicit ID first
        $toilet = null;
        if (!empty($validated['toilet_id'])) {
            $toilet = SanitationToilet::find($validated['toilet_id']);
        }

        if (!$toilet && !empty($validated['location_point'])) {
            $loc = (string) $validated['location_point'];
            if (is_numeric($loc)) {
                $toilet = SanitationToilet::find((int) $loc);
            }
            if (!$toilet) {
                $toilet = SanitationToilet::where('code', $loc)
                    ->orWhere('name', $loc)
                    ->first();
            }

            if (!$toilet) {
                $namePart = trim(explode('(', $loc)[0]);
                $toilet = SanitationToilet::where('name', $namePart)
                    ->orWhere('code', $namePart)
                    ->first();
            }

            if (!$toilet && !empty($loc)) {
                $toilet = SanitationToilet::create([
                    'name' => $loc,
                    'code' => 'TLT-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'status' => 'AKTIF',
                ]);
            }
        }

        if (!$toilet) {
            $toilet = SanitationToilet::first();
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('inspections', 'public');
            $photoPath = '/storage/' . $path;
        }

        $isAllOk = (bool)$validated['water_flush_ok'] 
            && (bool)$validated['floor_mirror_clean'] 
            && (bool)$validated['supplies_ready'] 
            && (bool)$validated['air_fragrance_ok'];

        $status = $isAllOk ? 'EXCELLENT' : 'NEED_ATTENTION';

        // Check if updating an existing inspection
        if (!empty($validated['inspection_id'])) {
            $existing = SanitationInspection::find($validated['inspection_id']);
            if ($existing) {
                $payload = [
                    'toilet_id' => $toilet ? $toilet->id : $existing->toilet_id,
                    'water_flush_ok' => $validated['water_flush_ok'],
                    'floor_mirror_clean' => $validated['floor_mirror_clean'],
                    'supplies_ready' => $validated['supplies_ready'],
                    'air_fragrance_ok' => $validated['air_fragrance_ok'],
                    'status' => $status,
                    'notes' => $validated['notes'],
                ];
                if ($photoPath) {
                    $payload['photo'] = $photoPath;
                }
                $existing->update($payload);
                return redirect()->back()->with('success', 'Hasil ceklis inspeksi toilet berhasil diperbarui.');
            }
        }

        $createdAt = now();
        if (!empty($validated['inspection_date'])) {
            $parsedDate = \Carbon\Carbon::parse($validated['inspection_date']);
            if (!$parsedDate->isToday()) {
                $createdAt = $parsedDate->setTime(now()->hour, now()->minute, now()->second);
            }
        }

        SanitationInspection::create([
            'toilet_id' => $toilet ? $toilet->id : null,
            'inspector_id' => auth()->id(),
            'water_flush_ok' => $validated['water_flush_ok'],
            'floor_mirror_clean' => $validated['floor_mirror_clean'],
            'supplies_ready' => $validated['supplies_ready'],
            'air_fragrance_ok' => $validated['air_fragrance_ok'],
            'status' => $status,
            'photo' => $photoPath,
            'notes' => $validated['notes'],
            'created_at' => $createdAt,
        ]);

        return redirect()->back()->with('success', 'Ceklis inspeksi harian toilet berhasil diverifikasi & disimpan.');
    }

    /**
     * Store waste weight log.
     */
    public function storeWaste(Request $request)
    {
        $validated = $request->validate([
            'unit_source' => 'required|string',
            'waste_type' => 'required|string',
            'weight_kg' => 'required|numeric|min:0.1',
        ]);

        return redirect()->back()->with('success', 'Data timbangan limbah B3/domestik berhasil dicatat.');
    }

    /**
     * Store a newly created toilet.
     */
    public function storeToilet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'status' => 'nullable|string|in:AKTIF,NONAKTIF',
        ]);

        $toilet = SanitationToilet::create([
            'name' => $validated['name'],
            'building' => $validated['building'],
            'floor' => $validated['floor'],
            'status' => $validated['status'] ?? 'AKTIF',
        ]);

        return redirect()->back()->with('success', "Titik toilet baru dan QR Code aduan ({$toilet->code}) berhasil ditambahkan!");
    }

    /**
     * Import multiple toilets from CSV or JSON data.
     */
    public function importToilets(Request $request)
    {
        $rows = [];

        // 1. Process uploaded CSV file
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            if (($handle = fopen($path, 'r')) !== false) {
                // Strip UTF-8 BOM if present
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                $firstLine = fgets($handle);
                $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
                rewind($handle);
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                $headers = fgetcsv($handle, 1000, $sep);
                if ($headers) {
                    $headers = array_map(fn($h) => strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $h))), $headers);

                    while (($data = fgetcsv($handle, 1000, $sep)) !== false) {
                        if (empty(array_filter($data))) continue;

                        $row = [];
                        foreach ($headers as $index => $header) {
                            $row[$header] = $data[$index] ?? '';
                        }

                        $name = $row['nama_toilet'] ?? $row['namatoilet'] ?? $row['nama'] ?? $row['name'] ?? ($data[0] ?? '');
                        $building = $row['gedung'] ?? $row['building'] ?? ($data[1] ?? 'Gedung A');
                        $floor = $row['lantai'] ?? $row['floor'] ?? ($data[2] ?? 'Lantai 1');
                        $code = $row['kode_toilet'] ?? $row['kodetoilet'] ?? $row['kode'] ?? $row['code'] ?? null;
                        $status = $row['status'] ?? ($data[3] ?? 'AKTIF');

                        if (!empty(trim($name))) {
                            $rows[] = [
                                'nama_toilet' => trim($name),
                                'gedung' => trim($building),
                                'lantai' => trim($floor),
                                'kode' => $code ? trim($code) : null,
                                'status' => strtoupper(trim($status)),
                            ];
                        }
                    }
                }
                fclose($handle);
            }
        } elseif ($request->has('data') && is_array($request->input('data'))) {
            $rows = $request->input('data');
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Berkas import kosong atau tidak memiliki baris data yang valid.');
        }

        $imported = 0;
        DB::transaction(function () use ($rows, &$imported) {
            foreach ($rows as $item) {
                $name = $item['nama_toilet'] ?? $item['name'] ?? '';
                $building = $item['gedung'] ?? $item['building'] ?? 'Gedung A';
                $floor = $item['lantai'] ?? $item['floor'] ?? 'Lantai 1';
                $code = $item['kode_toilet'] ?? $item['kode'] ?? $item['code'] ?? null;
                $status = $item['status'] ?? 'AKTIF';

                if (!empty(trim($name))) {
                    $payload = [
                        'name' => trim($name),
                        'building' => trim($building),
                        'floor' => trim($floor),
                        'status' => in_array($status, ['AKTIF', 'NONAKTIF']) ? $status : 'AKTIF',
                    ];
                    if (!empty($code) && !SanitationToilet::where('code', trim($code))->exists()) {
                        $payload['code'] = trim($code);
                    }
                    SanitationToilet::create($payload);
                    $imported++;
                }
            }
        });

        return redirect()->back()->with('success', "Berhasil mengimpor {$imported} data titik toilet!");
    }

    /**
     * Update the specified toilet.
     */
    public function updateToilet(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'status' => 'nullable|string|in:AKTIF,NONAKTIF',
        ]);

        $toilet = SanitationToilet::findOrFail($id);
        $toilet->update($validated);

        return redirect()->back()->with('success', 'Data toilet berhasil diperbarui!');
    }

    /**
     * Remove the specified toilet.
     */
    public function destroyToilet($id)
    {
        $toilet = SanitationToilet::findOrFail($id);
        $toilet->delete();

        return redirect()->back()->with('success', 'Data toilet berhasil dihapus!');
    }
}
