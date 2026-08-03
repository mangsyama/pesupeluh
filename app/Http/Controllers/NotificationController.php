<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class NotificationController extends Controller
{
    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'success']);
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $notifications = $user->notifications()->paginate(20)->through(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'user',
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'route' => $notification->data['route'] ?? null,
                'user_id' => $notification->data['user_id'] ?? null,
                'priority' => $notification->data['priority'] ?? null,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'time' => $notification->created_at ? $notification->created_at->diffForHumans() : null,
            ];
        });

        return \Inertia\Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'allNotifications' => $notifications,
        ]);
    }

    /**
     * Display Design System Notifications Test Page grouped by roles.
     */
    public function designSystemCatalog(Request $request)
    {
        $user = $request->user();

        // Comprehensive notification catalog based on exact system notification classes & statuses
        $notificationCatalog = [
            'STAF_PELAPOR' => [
                'role_name' => 'Staf Pelapor (Staff)',
                'role_id' => Role::STAFF,
                'items' => [
                    [
                        'key' => 'REPORTER_REGISTRATION_SUBMITTED',
                        'title' => 'Pendaftaran Akun Terkirim',
                        'message' => 'Pendaftaran akun Staf Pelapor Anda telah berhasil dikirim dan menunggu persetujuan Admin.',
                        'type' => 'user',
                        'icon' => 'UserCheck',
                        'priority' => 'normal',
                    ],
                    [
                        'key' => 'REPORTER_TICKET_CREATED',
                        'title' => 'Laporan Berhasil Dibuat',
                        'message' => 'Laporan kerusakan #TK-2026-089 (AC Tidak Dingin) di R. Melati Lt. 2 berhasil diajukan.',
                        'type' => 'ticket',
                        'icon' => 'CheckCircle2',
                        'priority' => 'normal',
                    ],
                    [
                        'key' => 'REPORTER_TICKET_COMPLETED',
                        'title' => 'Tiket Selesai Dikerjakan',
                        'message' => 'Laporan #TK-2026-089 telah selesai dikerjakan oleh teknisi dan menunggu konfirmasi Anda.',
                        'type' => 'ticket',
                        'icon' => 'CheckCircle',
                        'priority' => 'high',
                    ],
                ]
            ],
            'KEPALA_RUANGAN' => [
                'role_name' => 'PJ Ruangan (Room Head)',
                'role_id' => Role::PJ_RUANGAN,
                'items' => [
                    [
                        'key' => 'ROOM_HEAD_STAFF_REPORTED',
                        'title' => 'Laporan Diajukan Staf Ruangan',
                        'message' => 'Staf Budi Santoso di R. Melati Lt. 2 telah mengajukan laporan #TK-2026-090 ke unit IPSRS.',
                        'type' => 'ticket',
                        'icon' => 'FileText',
                        'priority' => 'normal',
                    ],
                ]
            ],
            'TEKNISI' => [
                'role_name' => 'Teknisi (Technician)',
                'role_id' => Role::TEKNISI,
                'items' => [
                    [
                        'key' => 'TECH_TICKET_ASSIGNED',
                        'title' => 'Tugas Baru Diterima',
                        'message' => 'Anda telah ditugaskan untuk menangani laporan #TK-2026-091 (Perbaikan Stopkontak R. ICU).',
                        'type' => 'ticket',
                        'icon' => 'Wrench',
                        'priority' => 'high',
                    ],
                    [
                        'key' => 'TECH_SLA_WARNING',
                        'title' => '⚠️ PERINGATAN SLA URGENT (Sisa 30 Menit)',
                        'message' => 'Laporan #TK-2026-091 mendekati batas target penanganan SLA! Segera selesaikan pengerjaan.',
                        'type' => 'sla',
                        'icon' => 'AlertTriangle',
                        'priority' => 'urgent',
                    ],
                ]
            ],
            'KEPALA_UNIT' => [
                'role_name' => 'Kepala Instalasi (Unit Head)',
                'role_id' => Role::KEPALA_INSTALASI,
                'items' => [
                    [
                        'key' => 'UNIT_HEAD_SLA_VALIDATION_WARNING',
                        'title' => '⚠️ PERINGATAN URGENT: VALIDASI TERTUNDA (>15 MNT)',
                        'message' => 'Laporan #TK-2026-092 belum divalidasi melebihi 15 menit! Segera lakukan penugasan teknisi.',
                        'type' => 'sla',
                        'icon' => 'AlertTriangle',
                        'priority' => 'urgent',
                    ],
                    [
                        'key' => 'UNIT_HEAD_NEW_TICKET',
                        'title' => 'Laporan Baru Masuk',
                        'message' => 'Laporan baru #TK-2026-092 telah dibuat dan membutuhkan validasi Anda.',
                        'type' => 'ticket',
                        'icon' => 'Bell',
                        'priority' => 'high',
                    ],
                ]
            ],
            'ADMINISTRATOR' => [
                'role_name' => 'Administrator & Manajemen',
                'role_id' => Role::ADMINISTRATOR,
                'items' => [
                    [
                        'key' => 'ADMIN_NEW_USER_REGISTERED',
                        'title' => 'Pendaftaran Baru',
                        'message' => 'Pengguna Dedi Kurniawan telah mendaftar dan menunggu verifikasi.',
                        'type' => 'user',
                        'icon' => 'UserPlus',
                        'priority' => 'high',
                    ],
                    [
                        'key' => 'ADMIN_WA_GATEWAY_STATUS',
                        'title' => 'WhatsApp Gateway Terhubung',
                        'message' => 'Layanan WhatsApp Gateway Fonnte (Fonnte API) berhasil terhubung dan aktif.',
                        'type' => 'system',
                        'icon' => 'Activity',
                        'priority' => 'normal',
                    ],
                    [
                        'key' => 'ADMIN_CRITICAL_SYSTEM_ALERT',
                        'title' => 'Peringatan Sistem / Server',
                        'message' => 'Koneksi database/gateway mengalami kendala sementara dan telah dipulihkan.',
                        'type' => 'system',
                        'icon' => 'AlertTriangle',
                        'priority' => 'urgent',
                    ],
                ]
            ],
        ];

        return \Inertia\Inertia::render('DesignSystem/Notifications', [
            'catalog' => $notificationCatalog,
        ]);
    }

    /**
     * Trigger a real database notification entry for testing dropdown and toast.
     */
    public function triggerTestNotification(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:255',
            'type' => 'required|string|in:ticket,user,sla,system',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'route' => 'nullable|string',
        ]);

        $user = $request->user();

        // Create database notification record directly for testing
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\TestSystemNotification',
            'data' => [
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'priority' => $validated['priority'] ?? 'normal',
                'route' => $validated['route'] ?? route('dashboard'),
                'user_id' => $user->id,
            ],
            'read_at' => null,
        ]);

        return back()->with('success', 'Notifikasi berhasil ditambahkan ke database & dropdown!');
    }
}
