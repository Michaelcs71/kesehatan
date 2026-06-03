<?php

namespace Tests\Unit\Pengingat;

use Tests\TestCase;

class ConfigPengingatTest extends TestCase
{
    public function test_config_pengingat_punya_default_yang_benar(): void
    {
        $this->assertSame(10, config('pengingat.interval_ulang_menit'));
        $this->assertSame(30, config('pengingat.wa_pasien_setelah_menit'));
        $this->assertSame(60, config('pengingat.wa_pmo_setelah_menit'));
        $this->assertSame(120, config('pengingat.batas_akhir_menit'));
        $this->assertTrue(config('pengingat.kanal.web_push'));
        $this->assertTrue(config('pengingat.kanal.whatsapp'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }
}
