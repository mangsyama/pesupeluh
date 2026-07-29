<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15)->through(function ($notification) {
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
            'allNotifications' => $notifications,
        ]);
    }

    /**
     * Display Design System Notifications Test Page grouped by roles.
     */
    public function designSystemIndex(Request $request)
    {
        $user = $request->user();

        // Comprehensive notification catalog based on exact system notification classes & statuses
        $notificationCatalog = [
            'STAF_PELAPOR' => [
                'role_name' => 'Staf Pelapor (Reporter)',
                'role_id' => 8,
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
                        'key' => 'REPORTER_EMERGENCY_SUBMITTED',
                        'title' => '🚨 LAPORAN DARURAT (EMERGENCY) DIKIRIM',
                        'message' => 'Laporan Darurat #TK-2026-099 (Tabung Oksigen ICU Bocor) dikirim & OTOMATIS DIDISPOSISI ke Teknisi On-Duty!',
                        'type' => 'ticket',
                        'icon' => 'ShieldAlert',
                        'priority' => 'emergency',
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
                'role_name' => 'Kepala Ruangan (Room Head)',
                'role_id' => 7,
                'items' => [
                    [
                        'key' => 'ROOM_HEAD_STAFF_REPORTED',
                        'title' => 'Laporan Diajukan Staf Ruangan',
                        'message' => 'Staf Budi Santoso di R. Melati Lt. 2 telah mengajukan laporan #TK-2026-090 ke unit IPSRS.',
                        'type' => 'ticket',
                        'icon' => 'FileText',
                        'priority' => 'normal',
                    ],
                    [
                        'key' => 'ROOM_HEAD_EMERGENCY_ALERT',
                        'title' => '🚨 LAPORAN DARURAT DI RUANGAN ANDA',
                        'message' => 'Laporan Darurat #TK-2026-099 di R. ICU telah dibuat dan LANGSUNG DITUGASKAN ke Teknisi Standby.',
                        'type' => 'ticket',
                        'icon' => 'ShieldAlert',
                        'priority' => 'emergency',
                    ],
                ]
            ],
            'TEKNISI' => [
                'role_name' => 'Teknisi (Technician)',
                'role_id' => 6,
                'items' => [
                    [
                        'key' => 'TECH_EMERGENCY_ASSIGNED',
                        'title' => '🚨 PENUGASAN DARURAT (EMERGENCY) INSTAN',
                        'message' => 'Laporan Darurat #TK-2026-099 (Kerusakan Alat Oksigen ICU) OTOMATIS DITUGASKAN KEPADA ANDA! Segera ke lokasi!',
                        'type' => 'ticket',
                        'icon' => 'ShieldAlert',
                        'priority' => 'emergency',
                    ],
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
                'role_name' => 'Kepala Unit (Unit Head)',
                'role_id' => 5,
                'items' => [
                    [
                        'key' => 'UNIT_HEAD_EMERGENCY_AUTO_DISPATCH',
                        'title' => '🚨 INFORMASI PENUGASAN DARURAT (AUTO-DISPATCH)',
                        'message' => 'Laporan Darurat #TK-2026-099 (Alat ICU) telah OTOMATIS DIDISPOSISI ke Teknisi Rizky Pratama.',
                        'type' => 'ticket',
                        'icon' => 'ShieldAlert',
                        'priority' => 'emergency',
                    ],
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
                'role_id' => 1,
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
            'priority' => 'nullable|string|in:normal,high,urgent,emergency',
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
