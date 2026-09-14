<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IssueCategory;
use App\Models\Role;
use App\Models\Room;
use App\Models\ServiceTicket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketHistory;
use App\Models\User;
use App\Notifications\NewTicketReportedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Services\SecureFileUpload;
use App\Services\UnitWorkingHourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class IntegrationController extends Controller
{
    /**
     * Check authorization token for integration requests.
     */
    private function validateIntegrationToken(Request $request): bool
    {
        $expected = config('services.integration.token') 
            ?: env('PESUPELUH_INTEGRATION_TOKEN', 'sipuas-pesupeluh-secret-token');

        $token = $request->header('X-Integration-Token')
            ?: $request->bearerToken()
            ?: $request->input('api_token');

        return !empty($token) && hash_equals($expected, (string) $token);
    }

    /**
     * List active issue categories for external systems (e.g. SIPUAS).
     */
    public function categories(Request $request): JsonResponse
    {
        if (!$this->validateIntegrationToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $categories = IssueCategory::with('supportingUnit:id,name,type')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'unit_id' => $cat->supporting_unit_id,
                    'unit_name' => $cat->supportingUnit ? $cat->supportingUnit->name : 'Penunjang Umum',
                    'unit_type' => $cat->supportingUnit ? $cat->supportingUnit->type : null,
                    'description' => $cat->description,
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Create a service ticket forwarded from SIPUAS.
     */
    public function createTicketFromSipuas(Request $request): JsonResponse
    {
        if (!$this->validateIntegrationToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'room_id' => 'nullable|integer',
            'room_name' => 'nullable|string',
            'category_id' => 'required|exists:issue_categories,id',
            'priority' => 'nullable|in:ROUTINE,URGENT',
            'problem_description' => 'required|string',
            'reporter_name' => 'nullable|string',
            'reporter_phone' => 'nullable|string',
            'is_anonymous' => 'nullable|boolean',
            'sipuas_ticket_number' => 'nullable|string',
            'kasi_name' => 'nullable|string',
            'kasi_nip' => 'nullable|string',
            'attachments' => 'nullable|array',
            'supervisor_notes' => 'nullable|string',
        ]);

        // 1. Resolve room_id
        $roomId = null;
        if (!empty($validated['room_id'])) {
            $roomId = Room::where('id', $validated['room_id'])->value('id');
        }
        if (!$roomId && !empty($validated['room_name'])) {
            $matchedRoom = Room::where('name', $validated['room_name'])
                ->orWhere('name', 'like', '%' . $validated['room_name'] . '%')
                ->first();
            $roomId = $matchedRoom?->id;
        }
        if (!$roomId) {
            $roomId = Room::first()?->id ?? 1;
        }

        // 2. Resolve reporter_id in Pesu Peluh (Single Generic User, No User Duplication)
        $targetUsername = env('PESUPELUH_REPORTER_USERNAME', 'sipuas_masyarakat');
        $reporterUser = User::where('username', $targetUsername)->first();

        if (!$reporterUser) {
            $reporterUser = User::whereIn('username', ['sipuas_masyarakat', 'masyarakat_sipuas', 'sipuas'])
                ->orWhere('name', 'like', '%Masyarakat%')
                ->first();
        }

        if (!$reporterUser) {
            $reporterUser = User::create([
                'name' => 'Masyarakat (via SIPUAS)',
                'username' => $targetUsername ?: 'sipuas_masyarakat',
                'nip' => '-',
                'email' => 'sipuas_masyarakat@rsud.local',
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                'phone_number' => '-',
                'role_id' => Role::STAFF,
                'is_active' => true,
                'is_on_duty' => false,
                'approved_by' => 1,
                'approved_at' => now(),
            ]);
        }
        $reporterId = $reporterUser->id;

        // 3. Category and Supporting Unit
        $category = IssueCategory::find($validated['category_id']);
        $supportingUnitId = $category?->supporting_unit_id;

        // 4. Working hours & initial status
        $isOffHours = UnitWorkingHourService::isOffHours($supportingUnitId);
        $initialStatus = $isOffHours ? 'ASSIGNED' : 'PENDING_VALIDATION';
        $ticketPriority = $validated['priority'] ?? 'ROUTINE';

        // 5. Generate ticket number
        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        while (ServiceTicket::where('ticket_number', $ticketNumber)->exists()) {
            $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        // 6. Format Problem Description with SIPUAS provenance
        $reporterLabel = ($validated['is_anonymous'] ?? false)
            ? 'Masyarakat / Publik (Anonim)'
            : (!empty($validated['reporter_name']) ? $validated['reporter_name'] : 'Masyarakat');

        if (!empty($validated['reporter_phone']) && !($validated['is_anonymous'] ?? false)) {
            $reporterLabel .= ' (HP: ' . $validated['reporter_phone'] . ')';
        }

        $formattedDescription = "📌 [DISPOSISI ADUAN PUBLIK SIPUAS]\n";
        if (!empty($validated['sipuas_ticket_number'])) {
            $formattedDescription .= "No. Tiket SIPUAS: {$validated['sipuas_ticket_number']}\n";
        }
        $formattedDescription .= "Pelapor: {$reporterLabel}\n";
        if (!empty($validated['kasi_name'])) {
            $formattedDescription .= "Diteruskan oleh: {$validated['kasi_name']}" . (!empty($validated['kasi_nip']) ? " (NIP: {$validated['kasi_nip']})" : "") . "\n";
        }
        $formattedDescription .= "\n--- URAIAN KELUHAN FASILITAS ---\n" . trim($validated['problem_description']);
        if (!empty($validated['supervisor_notes'])) {
            $formattedDescription .= "\n\n--- CATATAN VERIFIKATOR (KASI) ---\n" . trim($validated['supervisor_notes']);
        }

        // 7. Create Ticket
        $ticket = ServiceTicket::create([
            'ticket_number' => $ticketNumber,
            'reporter_id' => $reporterId,
            'room_id' => $roomId,
            'category_id' => $validated['category_id'],
            'problem_description' => $formattedDescription,
            'priority' => $ticketPriority,
            'status' => $initialStatus,
            'validated_at' => $isOffHours ? now() : null,
            'validated_by' => null,
        ]);

        // 8. Create History
        $sipuasRef = $validated['sipuas_ticket_number'] ?? 'SIPUAS';
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $reporterId,
            'status' => $initialStatus,
            'action' => 'CREATED_VIA_SIPUAS',
            'notes' => "Tiket otomatis dibuat via disposisi aduan publik SIPUAS [{$sipuasRef}] oleh " . ($validated['kasi_name'] ?? 'KASI') . ($isOffHours ? ' (🌙 Mode Luar Jam Operasional).' : '.'),
        ]);

        // 9. Process Attachments (Base64 data URIs)
        $attachments = $validated['attachments'] ?? [];
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $att) {
                if (empty($att)) continue;
                // $att can be a base64 string or an array with data_url
                $dataUrl = is_array($att) ? ($att['data'] ?? ($att['url'] ?? '')) : $att;
                if (!empty($dataUrl) && str_contains($dataUrl, ';base64,')) {
                    $filePath = SecureFileUpload::saveBase64($dataUrl, 'ticket_attachments', 'ticket_sipuas_');
                    if ($filePath) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'file_path' => $filePath,
                            'uploaded_by' => $reporterId,
                            'uploaded_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 10. Auto-assign technicians if Off-Hours
        if ($isOffHours) {
            $techniciansQuery = User::where('role_id', Role::TEKNISI)->where('is_active', 1);

            if ($supportingUnitId) {
                $unitTechs = (clone $techniciansQuery)->where('supporting_unit_id', $supportingUnitId)->get();
                $technicians = $unitTechs->isNotEmpty() ? $unitTechs : $techniciansQuery->get();
            } else {
                $technicians = $techniciansQuery->get();
            }

            $onDutyTechs = $technicians->where('is_on_duty', 1);
            $candidatePool = $onDutyTechs->isNotEmpty() ? $onDutyTechs : $technicians;

            $assignedTechnicians = collect();
            foreach ($candidatePool as $tech) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'technician_id' => $tech->id,
                    'assigned_by' => null,
                    'assigned_at' => now(),
                ]);
                $assignedTechnicians->push($tech);
            }

            $techNames = $assignedTechnicians->pluck('name')->join(', ');

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => $reporterId,
                'status' => 'ASSIGNED',
                'action' => 'AUTO_DISPATCH',
                'notes' => 'Disposisi otomatis di luar jam kerja operasional unit penunjang ke seluruh teknisi (' . ($techNames ?: 'Tanpa teknisi') . ').',
            ]);

            if ($assignedTechnicians->isNotEmpty()) {
                try {
                    $ticket->load(['room', 'category', 'reporter']);
                    Notification::send($assignedTechnicians, new TicketAssignedNotification($ticket));
                } catch (\Throwable $e) {
                    Log::error('Pesupeluh Integration: Gagal kirim notifikasi teknisi off-hours: ' . $e->getMessage());
                }
            }
        } else {
            // Normal hours notification to Unit Heads, Technicians, Admins
            $ticket->load(['reporter', 'room', 'category.supportingUnit']);
            if ($supportingUnitId || $ticket->room_id) {
                $recipients = User::where('is_active', 1)
                    ->where('id', '!=', $ticket->reporter_id)
                    ->where(function ($query) use ($supportingUnitId, $ticket) {
                        $query->where('role_id', Role::ADMINISTRATOR)
                            ->orWhere('role_id', Role::KEPALA_BIDANG);

                        if ($supportingUnitId) {
                            $query->orWhere(function ($q) use ($supportingUnitId) {
                                $q->whereIn('role_id', [
                                    Role::KEPALA_SEKSI,
                                    Role::KEPALA_INSTALASI,
                                    Role::SEKRETARIS_INSTALASI,
                                ])->where(function ($q2) use ($supportingUnitId) {
                                    $q2->where('supporting_unit_id', $supportingUnitId)
                                       ->orWhereNull('supporting_unit_id');
                                });
                            });
                        }

                        if ($ticket->room_id) {
                            $query->orWhere(function ($q) use ($ticket) {
                                $q->where('role_id', Role::PJ_RUANGAN)->where('room_id', $ticket->room_id);
                            });
                        }
                    })
                    ->get();

                if ($recipients->isNotEmpty()) {
                    try {
                        Notification::send($recipients, new NewTicketReportedNotification($ticket));
                    } catch (\Throwable $e) {
                        Log::error('Pesupeluh Integration: Gagal kirim notifikasi tiket baru: ' . $e->getMessage());
                    }
                }
            }
        }

        // Broadcast realtime update
        try {
            \App\Events\TicketRealtimeUpdated::dispatch($ticket, 'created');
        } catch (\Throwable $e) {
            Log::error('Pesupeluh Integration: Gagal broadcast TicketRealtimeUpdated: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil didisposisikan ke PESU PELUH.',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status,
        ]);
    }
}
