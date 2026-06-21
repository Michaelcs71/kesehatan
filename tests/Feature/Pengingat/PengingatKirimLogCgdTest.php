<?php

namespace Tests\Feature\Pengingat;

use App\Models\PengingatKirimLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatKirimLogCgdTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_cgd_boleh_tanpa_kejadian_id(): void
    {
        $log = PengingatKirimLog::create([
            'kejadian_id' => null,
            'peserta_id' => 'a1b2c3d4-0000-0000-0000-000000000000',
            'fase' => 'dibuat',
            'kanal' => 'whatsapp',
            'target' => 'pasien',
            'status' => 'terkirim',
        ]);

        $this->assertNull($log->kejadian_id);
        $this->assertSame('dibuat', $log->fresh()->fase);
    }
}
