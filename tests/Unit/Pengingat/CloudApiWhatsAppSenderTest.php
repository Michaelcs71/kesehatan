<?php

namespace Tests\Unit\Pengingat;

use App\Services\Whatsapp\CloudApiWhatsAppSender;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudApiWhatsAppSenderTest extends TestCase
{
    public function test_mengirim_payload_template_ke_graph_api(): void
    {
        config()->set('pengingat.whatsapp.cloud_api', [
            'token' => 'TOKEN', 'phone_id' => '123', 'lang' => 'id',
            'base_url' => 'https://graph.facebook.com/v21.0',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
        ]);

        $ok = (new CloudApiWhatsAppSender)->kirimTemplate('628123', 'pengingat_obat', ['Budi', 'Metformin', '08:00', 'https://x']);

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/123/messages')
                && $request->hasHeader('Authorization', 'Bearer TOKEN')
                && $body['type'] === 'template'
                && $body['template']['name'] === 'pengingat_obat'
                && $body['template']['components'][0]['parameters'][0]['text'] === 'Budi';
        });
    }

    public function test_return_false_saat_api_gagal(): void
    {
        config()->set('pengingat.whatsapp.cloud_api', [
            'token' => 'T', 'phone_id' => '1', 'lang' => 'id',
            'base_url' => 'https://graph.facebook.com/v21.0',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'x'], 400)]);

        $ok = (new CloudApiWhatsAppSender)->kirimTemplate('628', 'pengingat_obat', ['a']);

        $this->assertFalse($ok);
    }
}
