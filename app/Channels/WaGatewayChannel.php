<?php

namespace App\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaGatewayChannel
{
    /**
     * Send the given notification via native WA Gateway microservice.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (config('services.wa_gateway.enabled') === false) {
            return;
        }

        // Extract phone number from notifiable target
        $phone = null;
        if ($notifiable instanceof User) {
            $phone = $notifiable->phone_number;
        } elseif (is_object($notifiable)) {
            $phone = $notifiable->phone_number ?? $notifiable->phone ?? null;
        } elseif (is_string($notifiable)) {
            $phone = $notifiable;
        }

        if (empty($phone)) {
            return;
        }

        // Get notification message content
        $message = null;
        if (method_exists($notification, 'toWaGateway')) {
            $message = $notification->toWaGateway($notifiable);
        }

        if (empty($message)) {
            return;
        }

        // Dynamic URL resolution:
        // 1. Explicitly configured URL from .env (WA_LOCAL_URL or WA_GATEWAY_URL)
        // 2. If running inside Docker container -> http://wa-gateway:3000/send
        // 3. Otherwise (standalone local dev) -> http://127.0.0.1:3000/send
        $url = config('services.wa_gateway.local_url') 
            ?: (file_exists('/.dockerenv') ? 'http://wa-gateway:3000/send' : 'http://127.0.0.1:3000/send');

        $secretKey = config('services.wa_gateway.secret_key');

        try {
            $client = Http::timeout(3)->withoutVerifying();
            if (!empty($secretKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $secretKey]);
            }

            $response = $client->post($url, [
                'target'  => $phone,
                'message' => $message,
            ]);

            if ($response->failed()) {
                Log::error('WA Gateway failed (' . $phone . '): ' . $response->body());
            } else {
                Log::info('WA Gateway sent successfully to ' . $phone . ' Result: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('WA Gateway exception (' . $phone . '): ' . $e->getMessage());
        }
    }
}
