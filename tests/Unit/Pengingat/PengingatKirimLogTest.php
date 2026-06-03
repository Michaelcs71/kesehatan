<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKirimLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengingatKirimLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_mencatat_kiriman(): void
    {
        $log = PengingatKirimLog::create([
            'kejadian_id' => 'a-uuid',
            'kanal'       => 'whatsapp',
            'target'      => 'pasien',
            'status'      => 'terkirim',
            'error'       => null,
        ]);

        $this->assertDatabaseHas('pengingat_kirim_log', [
            'kanal' => 'whatsapp', 'target' => 'pasien', 'status' => 'terkirim',
        ]);
        $this->assertNotNull($log->id);
    }
}
