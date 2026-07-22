<?php

namespace App\Http\Controllers;

use App\Models\SupportingUnit;
use App\Models\Room;
use App\Models\ServiceTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function showUnit(SupportingUnit $supportingUnit)
    {
        if ($supportingUnit->status !== 'ACTIVE') {
            return redirect()->route('services.index')->with('error', 'Unit penunjang ini sedang dalam tahap pengembangan.');
        }

        return Inertia::render('Service/Show', [
            'unit' => Inertia::defer(fn() => $supportingUnit->load(['division', 'unitFeatures.featureCategories'])),
            'rooms' => Inertia::defer(fn() => Room::orderBy('name', 'asc')->get())
        ]);
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'category_id' => 'required|exists:feature_categories,id',
            'problem_description' => 'required|string|min:5',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|string',
        ]);

        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $ticket = ServiceTicket::create([
            'ticket_number' => $ticketNumber,
            'reporter_id' => $request->user()->id,
            'room_id' => $validated['room_id'],
            'category_id' => $validated['category_id'],
            'problem_description' => $validated['problem_description'],
            'status' => 'PENDING_VALIDATION',
        ]);

        $attachments = $request->input('attachments', []);
        if (is_array($attachments) && count($attachments) > 0) {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('ticket_attachments');

            foreach ($attachments as $dataUrl) {
                if (!is_string($dataUrl) || !str_starts_with($dataUrl, 'data:')) continue;

                $parts = explode(";base64,", $dataUrl);
                if (count($parts) !== 2) continue;

                $fileContent = base64_decode($parts[1]);
                $mimeHeader = $parts[0]; // e.g. "data:image/jpeg" or "data:video/mp4"

                // Determine extension from mime
                $ext = 'bin';
                if (str_contains($mimeHeader, 'image/')) {
                    $ext = str_replace('data:image/', '', $mimeHeader) ?: 'jpeg';
                } elseif (str_contains($mimeHeader, 'video/')) {
                    $ext = str_replace('data:video/', '', $mimeHeader) ?: 'mp4';
                }

                $filename = 'ticket_' . uniqid() . '.' . $ext;
                \Illuminate\Support\Facades\Storage::disk('public')->put('ticket_attachments/' . $filename, $fileContent);
                $filePath = '/storage/ticket_attachments/' . $filename;

                \App\Models\TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_path' => $filePath,
                    'uploaded_by' => $request->user()->id,
                    'uploaded_at' => now(),
                ]);
            }
        }

        // Notify Unit Heads of the supporting unit managing this category and all active Administrators
        $ticket->load(['reporter', 'room', 'category.unitFeature.supportingUnit']);
        $supportingUnitId = $ticket->category?->unitFeature?->supporting_unit_id;

        if ($supportingUnitId) {
            $recipients = \App\Models\User::where('is_active', 1)
                ->where(function ($query) use ($supportingUnitId) {
                    $query->where(function ($q) use ($supportingUnitId) {
                        $q->where('role_id', 5)->where('supporting_unit_id', $supportingUnitId);
                    })->orWhere('role_id', 1); // Administrator
                })
                ->get();

            if ($recipients->isNotEmpty()) {
                try {
                    \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\NewTicketReportedNotification($ticket));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi tiket baru: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('reports.history')->with('success', 'Tiket pelaporan baru berhasil dibuat.');
    }
}
