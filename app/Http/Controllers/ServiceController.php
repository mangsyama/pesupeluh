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

        // Load division and features with categories
        $unit = $supportingUnit->load(['division', 'unitFeatures.featureCategories']);
        $rooms = Room::orderBy('name', 'asc')->get();

        return Inertia::render('Service/Show', [
            'unit' => $unit,
            'rooms' => $rooms
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

        return redirect()->route('reports.history')->with('success', 'Tiket pelaporan baru berhasil dibuat.');
    }
}
