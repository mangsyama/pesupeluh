<?php

namespace App\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaGatewayChannel
{
    /**
     * Resolve the appropriate WA Gateway base URL.
     * Handles Docker network routing where 127.0.0.1 must resolve to 'wa-gateway' service inside Docker.
     */
    public static function getGatewayUrl(string $endpoint = '/send'): string
    {
        $baseUrl = config('services.wa_gateway.local_url');

        if (file_exists('/.dockerenv') || env('DOCKER_ENV', false)) {
            if (empty($baseUrl) || str_contains($baseUrl, '127.0.0.1') || str_contains($baseUrl, 'localhost')) {
                $baseUrl = 'http://wa-gateway:3000/send';
            }
        }

        if (empty($baseUrl)) {
            $baseUrl = 'http://127.0.0.1:3000/send';
        }

        if ($endpoint !== '/send') {
            $baseUrl = str_replace('/send', $endpoint, $baseUrl);
        }

        return $baseUrl;
    }

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

        $url = self::getGatewayUrl('/send');
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
