<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudApiWhatsAppSender implements WhatsAppSender
{
    public function kirimTemplate(string $noHp, string $template, array $params): bool
    {
        $cfg = config('pengingat.whatsapp.cloud_api');

        $parameters = array_map(
            fn ($text) => ['type' => 'text', 'text' => (string) $text],
            array_values($params),
        );

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $noHp,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $cfg['lang'] ?? 'id'],
                'components' => [
                    ['type' => 'body', 'parameters' => $parameters],
                ],
            ],
        ];

        $resp = Http::withToken($cfg['token'])
            ->acceptJson()
            ->post(rtrim($cfg['base_url'], '/').'/'.$cfg['phone_id'].'/messages', $payload);

        if (! $resp->successful()) {
            Log::warning('[WA:cloud_api] gagal', ['status' => $resp->status(), 'body' => $resp->body()]);

            return false;
        }

        return true;
    }
}
