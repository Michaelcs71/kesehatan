<?php

namespace App\Services\Whatsapp;

interface WhatsAppSender
{
    /**
     * Kirim WhatsApp berbasis template.
     * @param string $noHp Nomor format internasional tanpa '+' (mis. 628123...)
     * @param string $template Nama template terdaftar
     * @param array<int,string> $params Parameter berurutan untuk {{1}}, {{2}}, ...
     */
    public function kirimTemplate(string $noHp, string $template, array $params): bool;
}
