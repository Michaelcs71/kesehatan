<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatJob;
use App\Models\JadwalMinumObat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatTickCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_materialisasi_dan_dispatch(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse(now()->toDateString().' 08:01:00'));
        JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1, 'tgl_mulai' => now()->subDay()->toDateString()]);

        $this->artisan('pengingat:tick')->assertExitCode(0);

        Queue::assertPushed(KirimPengingatJob::class);
    }
}
