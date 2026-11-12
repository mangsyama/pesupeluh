<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\IssueCategory;
use App\Models\SupportingUnit;
use App\Models\SanitationToilet;
use App\Models\SanitationTicket;
use App\Services\SecureFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicSanitasiController extends Controller
{
    /**
     * Show public sanitation complaint form.
     */
    public function create(Request $request)
    {
        $keslingUnit = SupportingUnit::where('slug', 'kesling')
            ->orWhereRaw('LOWER(name) LIKE ?', ['%kesling%'])
            ->first();

        $categories = $keslingUnit 
            ? IssueCategory::where('supporting_unit_id', $keslingUnit->id)->get()
            : IssueCategory::where('name', 'LIKE', '%toilet%')
                ->orWhere('name', 'LIKE', '%air%')
                ->orWhere('name', 'LIKE', '%sampah%')
                ->orWhere('name', 'LIKE', '%limbah%')
                ->get();

        // Ambil seluruh master toilet dari Manajemen Toilet (SanitationToilet)
        $toilets = SanitationToilet::orderBy('building', 'asc')
            ->orderBy('floor', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // 1. Tangkap parameter dari URL
        $toiletParam = $request->query('toilet') ?: $request->query('code') ?: $request->query('id');
        $targetParam = $request->query('target') ?: $request->query('target_object');
        $locationParam = $request->query('location');
        $step = $request->query('step');
        $roomId = $request->query('room_id') ?: $request->query('room') ?: $request->query('unit');

        // 2. Cari data toilet terdaftar di SanitationToilet jika ada parameter
        $foundToilet = null;

        if ($toiletParam) {
            $q = strtolower(trim($toiletParam));
            $foundToilet = SanitationToilet::whereRaw('LOWER(code) = ?', [$q])
                ->orWhere('id', is_numeric($toiletParam) ? (int)$toiletParam : 0)
                ->orWhereRaw('LOWER(name) = ?', [$q])
                ->first();
        }

        if (!$foundToilet && $targetParam) {
            $q = strtolower(trim($targetParam));
            $foundToilet = SanitationToilet::whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                ->orWhereRaw('LOWER(code) = ?', [$q])
                ->first();
        }

        $toiletName = $foundToilet?->name ?? $targetParam ?? '';
        $building = $foundToilet?->building ?? '';
        $floor = $foundToilet?->floor ?? '';

        if (!$building && $locationParam) {
            $parts = explode('-', $locationParam);
            $building = trim($parts[0] ?? '');
            $floor = trim($parts[1] ?? '');
        }

        $resolvedLocationString = ($building && $floor) ? "{$building} - {$floor}" : ($locationParam ?: '');

        return Inertia::render('Service/Kesling/Public/AduanMasyarakat', [
            'toilets' => $toilets,
            'toiletData' => $foundToilet,
            'categories' => $categories,
            'roomId' => $roomId ?: '',
            'initialRoomId' => $roomId ?: '',
            'targetObject' => $toiletName,
            'initialTarget' => $toiletName,
            'targetLocation' => $resolvedLocationString,
            'initialStep' => $step !== null ? (int) $step : null,
        ]);
    }

    /**
     * Store public sanitation complaint.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'nullable',
            'toilet_id' => 'nullable|exists:sanitation_toilets,id',
            'category_id' => 'nullable|exists:issue_categories,id',
            'location_point' => 'nullable|string|max:150',
            'target_object' => 'nullable|string|max:150',
            'problem_description' => 'required|string|min:5',
            'urgency' => 'nullable|string',
            'reporter_name' => 'nullable|string|max:100',
            'reporter_phone' => 'nullable|string|max:30',
            'is_anonymous' => 'nullable|boolean',
            'attachment' => 'nullable',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm|max:10240',
        ], [
            'room_id.required' => 'Silakan pilih lokasi ruangan atau toilet terlebih dahulu.',
            'problem_description.required' => 'Deskripsi kendala wajib diisi minimal 5 karakter.',
            'problem_description.min' => 'Deskripsi kendala terlalu singkat (minimal 5 karakter).',
            'attachments.max' => 'Maksimal 5 file lampiran yang dapat diunggah.',
            'attachments.*.max' => 'Ukuran setiap file maksimal 10MB.',
        ]);

        // Process photo/video attachments (single or multiple)
        $evidencePaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $filename = 'sanitasi_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('attachments/sanitasi', $filename, 'public');
                    $evidencePaths[] = '/storage/' . $path;
                }
            }
        }
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file && $file->isValid()) {
                $filename = 'sanitasi_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('attachments/sanitasi', $filename, 'public');
                $evidencePaths[] = '/storage/' . $path;
            }
        } elseif ($request->filled('attachment') && is_string($request->input('attachment'))) {
            $raw = $request->input('attachment');
            if (str_starts_with($raw, 'data:image')) {
                $saved = SecureFileUpload::saveBase64($raw, 'attachments/sanitasi', 'sanitasi_');
                if ($saved) {
                    $evidencePaths[] = $saved;
                }
            }
        }

        $evidenceBefore = count($evidencePaths) === 0
            ? null
            : (count($evidencePaths) === 1 ? $evidencePaths[0] : json_encode($evidencePaths));

        // Find or associate toilet
        $toilet = null;
        if (!empty($validated['toilet_id'])) {
            $toilet = SanitationToilet::find($validated['toilet_id']);
        }
        if (!$toilet && !empty($validated['target_object'])) {
            $target = trim($validated['target_object']);
            $toilet = SanitationToilet::where('name', $target)
                ->orWhere('code', $target)
                ->orWhere('name', 'LIKE', "%{$target}%")
                ->first();
        }
        if (!$toilet && !empty($validated['location_point'])) {
            $loc = trim($validated['location_point']);
            $toilet = SanitationToilet::where('name', $loc)
                ->orWhere('code', $loc)
                ->orWhere('name', 'LIKE', "%{$loc}%")
                ->first();
        }
        if (!$toilet) {
            $toilet = SanitationToilet::first();
        }

        $urgency = strtoupper($validated['urgency'] ?? 'NORMAL');
        if ($urgency !== 'DARURAT') {
            $urgency = 'NORMAL';
        }

        $isEmergency = ($urgency === 'DARURAT');
        $isAnon = filter_var($validated['is_anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reporterName = $isEmergency
            ? ($validated['reporter_name'] ?: 'Tombol Bantuan Darurat Toilet')
            : ($isAnon ? 'Anonim' : ($validated['reporter_name'] ?: 'Pengunjung / Masyarakat'));

        $ticket = SanitationTicket::create([
            'toilet_id' => $toilet ? $toilet->id : null,
            'problem_description' => $validated['problem_description'],
            'urgency' => $urgency,
            'status' => 'MENUNGGU',
            'reporter_name' => $reporterName,
            'reporter_phone' => $isEmergency ? ($validated['reporter_phone'] ?? '-') : ($validated['reporter_phone'] ?? null),
            'is_anonymous' => $isEmergency ? false : $isAnon,
            'evidence_before' => $evidenceBefore,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ticket_number' => $ticket->ticket_number,
                'redirect_url' => route('public.sanitasi.success', $ticket->ticket_number),
            ]);
        }

        return redirect()->route('public.sanitasi.success', $ticket->ticket_number);
    }

    /**
     * Show success confirmation receipt page.
     */
    public function success(string $ticketNumber)
    {
        $query = SanitationTicket::with('toilet');
        if (\Illuminate\Support\Str::isUuid($ticketNumber)) {
            $query->where('uuid', $ticketNumber);
        } else {
            $query->where('ticket_number', $ticketNumber);
        }
        $ticket = $query->first();

        if (!$ticket) {
            abort(404, 'Tiket tidak ditemukan');
        }

        return Inertia::render('Service/Kesling/Public/Success', [
            'ticket' => [
                'ticket_number' => $ticket->ticket_number,
                'room_name' => $ticket->location_point ?? 'Toilet RS',
                'building_name' => $ticket->location_building ?? 'Gedung RS',
                'location_point' => $ticket->location_point,
                'category_name' => $ticket->category_name,
                'problem_description' => $ticket->problem_description,
                'urgency' => $ticket->urgency,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at_formatted ?? now()->format('d M Y - H:i'),
            ]
        ]);
    }

    /**
     * Track public sanitation complaint progress.
     */
    public function track(Request $request)
    {
        $ticketQuery = trim((string) $request->query('ticket', ''));
        $toiletParam = $request->query('toilet') ?: $request->query('code') ?: $request->query('id');
        $searched = !empty($ticketQuery);
        $ticket = null;

        if ($searched) {
            $query = SanitationTicket::with(['toilet', 'resolver']);
            if (\Illuminate\Support\Str::isUuid($ticketQuery)) {
                $query->where('uuid', $ticketQuery);
            } else {
                $query->where('ticket_number', $ticketQuery);
            }
            $ticket = $query->first();
        }

        $reportData = null;
        if ($ticket) {
            $reportData = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'room_name' => $ticket->location_point ?? 'Toilet RS',
                'building_name' => $ticket->location_building ?? 'Gedung RS',
                'floor' => $ticket->location_floor ?? 'Lantai 1',
                'location_point' => $ticket->location_point,
                'category_name' => $ticket->category_name,
                'problem_description' => $ticket->problem_description,
                'urgency' => $ticket->urgency,
                'status' => $ticket->status,
                'reporter_name' => $ticket->reporter_name,
                'evidence_before' => $ticket->evidence_before,
                'evidence_before_list' => $ticket->evidence_before_list,
                'evidence_after' => $ticket->evidence_after,
                'evidence_after_list' => $ticket->evidence_after_list,
                'resolution_notes' => $ticket->resolution_notes,
                'created_at' => $ticket->created_at_formatted ?? now()->format('d M Y - H:i'),
                'created_at_relative' => $ticket->created_at_relative,
                'started_at' => $ticket->started_at_formatted,
                'resolved_at' => $ticket->resolved_at_formatted,
            ];
        }

        return Inertia::render('Service/Kesling/Public/Track', [
            'initialTicket' => $ticketQuery,
            'report' => $reportData,
            'searched' => $searched,
            'toiletCode' => $toiletParam ?: ($ticket?->toilet?->code ?? null),
        ]);
    }
}
