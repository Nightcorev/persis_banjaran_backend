<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class WebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {

        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');

        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response($challenge, 200);
            } else {
                return response('Forbidden', 200);
            }
        }
        return response('Bad Request', 200);
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Request headers: ', $request->headers->all());

        Log::info('Request body: ', $request->all());

        $entry = $request->input('entry', []);
        $messages = [];

        foreach ($entry as $entryData) {
            foreach ($entryData['changes'] as $change) {
                if (isset($change['value']['messages'])) {
                    $messages = $change['value']['messages'];
                }
            }
        }

        if (!empty($messages)) {
            $message = $messages[0]['text']['body'] ?? 'No text message';
            $from = $messages[0]['from'] ?? 'Unknown sender';

            Log::info("Pesan diterima dari $from: $message");

            $accessToken = env('WHATSAPP_ACCESS_TOKEN');
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $from,
                'text' => [
                    'body' => 'Hello, this is a custom message from my app!'
                ]
            ];

            Log::info('Data yang dikirim ke WhatsApp API: ', $data);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post(env('WHATSAPP_API_URL'), $data);

            if ($response->successful()) {
                Log::info("Pesan berhasil dikirim: " . $response->body());
                return response()->json(['status' => 'Message sent successfully']);
            } else {
                Log::error("Gagal mengirim pesan: " . $response->body());
                return response()->json(['status' => 'Failed to send message'], 200);
            }
        } else {
            Log::warning('Tidak ada pesan yang ditemukan di request webhook.');
            return response()->json(['status' => 'No messages found'], 200);
        }
    }
}
