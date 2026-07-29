<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use App\Channels\WaGatewayChannel;

class WaGatewayController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPageAccess('admin.wa-gateway.index') && (int) $user->role_id !== 1) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return Inertia::render('WaGateway/Index', [
            'driver' => config('services.wa_gateway.driver', 'local'),
            'localUrl' => config('services.wa_gateway.local_url', 'http://127.0.0.1:3000/send'),
        ]);
    }

    public function status(Request $request)
    {
        $baseUrl = config('services.wa_gateway.local_url', 'http://127.0.0.1:3000/send');
        $statusUrl = str_replace('/send', '/status', $baseUrl);

        $data = [
            'status' => 'offline',
            'qr' => null,
            'user' => null,
            'message' => 'Server WA Gateway belum berjalan di ' . $statusUrl
        ];

        $secretKey = config('services.wa_gateway.secret_key');

        try {
            $client = Http::timeout(4)->withoutVerifying();
            if (!empty($secretKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $secretKey]);
            }
            $response = $client->get($statusUrl);
            if ($response->successful()) {
                $data = $response->json();
            }
        } catch (\Throwable $e) {
            // Gateway server not running
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('admin.wa-gateway.index');
        }

        return response()->json($data);
    }

    public function logout(Request $request)
    {
        $baseUrl = config('services.wa_gateway.local_url', 'http://127.0.0.1:3000/send');
        $logoutUrl = str_replace('/send', '/logout', $baseUrl);

        $secretKey = config('services.wa_gateway.secret_key');

        try {
            $client = Http::timeout(5)->withoutVerifying();
            if (!empty($secretKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $secretKey]);
            }
            $response = $client->post($logoutUrl);
            
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json($response->json());
            }

            return back()->with('success', 'Perangkat WhatsApp berhasil diputuskan.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal terhubung ke server WA Gateway: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal terhubung ke server WA Gateway: ' . $e->getMessage());
        }
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|min:2',
        ]);

        try {
            $channel = new WaGatewayChannel();
            $channel->send($request->input('phone'), new class($request->input('message')) extends \Illuminate\Notifications\Notification {
                public function __construct(public string $msg) {}
                public function toWaGateway($notifiable) { return $this->msg; }
            });

            return back()->with('success', 'Pesan Uji Coba WhatsApp telah dikirimkan!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim pesan uji coba: ' . $e->getMessage());
        }
    }
}
