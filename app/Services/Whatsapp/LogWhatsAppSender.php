<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Log;

class LogWhatsAppSender implements WhatsAppSender
{
    public function kirimTemplate(string $noHp, string $template, array $params): bool
    {
        Log::info('[WA:log] kirim template', [
            'no' => $noHp,
            'template' => $template,
            'params' => $params,
        ]);

        return true;
    }
}
