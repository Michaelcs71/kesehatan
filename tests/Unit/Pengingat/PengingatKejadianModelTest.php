<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKejadian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatKejadianModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_status_menunggu_dan_scope_menunggu(): void
    {
        $pasien = User::factory()->create();

        $k = PengingatKejadian::create([
            'jenis' => 'mo',
            'jadwal_id' => $pasien->id, // sembarang uuid untuk uji model
            'id_pasien_pmo' => $pasien->id,
            'user_pasien_id' => $pasien->id,
            'waktu_jadwal' => Carbon::parse('2026-06-03 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);

        $this->assertSame('menunggu', $k->status);
        $this->assertInstanceOf(Carbon::class, $k->waktu_jadwal);
        $this->assertTrue(PengingatKejadian::menunggu()->whereKey($k->id)->exists());
    }
}
