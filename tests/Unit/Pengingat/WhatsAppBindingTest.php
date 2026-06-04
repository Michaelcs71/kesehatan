<?php

namespace Tests\Unit\Pengingat;

use App\Services\Whatsapp\CloudApiWhatsAppSender;
use App\Services\Whatsapp\LogWhatsAppSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppBindingTest extends TestCase
{
    public function test_driver_log_dipakai_secara_default(): void
    {
        config()->set('pengingat.whatsapp.driver', 'log');
        $this->assertInstanceOf(LogWhatsAppSender::class, app(WhatsAppSender::class));
    }

    public function test_driver_cloud_api_saat_dikonfigurasi(): void
    {
        config()->set('pengingat.whatsapp.driver', 'cloud_api');
        $this->assertInstanceOf(CloudApiWhatsAppSender::class, app(WhatsAppSender::class));
    }

    public function test_log_sender_menulis_log_dan_return_true(): void
    {
        Log::shouldReceive('info')->once();
        $ok = (new LogWhatsAppSender())->kirimTemplate('628123', 'pengingat_obat', ['a', 'b']);
        $this->assertTrue($ok);
    }
}
