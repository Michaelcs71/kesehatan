<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengingatKejadian;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TentukanAksiTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $jadwal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jadwal = Carbon::parse('2026-06-03 08:00:00');
    }

    private function kejadian(array $override = []): PengingatKejadian
    {
        $pasien = User::factory()->create();
        $pmo = User::factory()->create();

        return new PengingatKejadian(array_merge([
            'jenis' => 'mo', 'jadwal_id' => 'j1', 'id_pasien_pmo' => 'pp1',
            'user_pasien_id' => $pasien->id, 'user_pmo_id' => $pmo->id,
            'waktu_jadwal' => $this->jadwal, 'status' => PengingatKejadian::STATUS_MENUNGGU,
            'eskalasi_pmo' => false, 'terakhir_dikirim_pada' => null,
        ], $override));
    }

    private function beriPush(string $userId): void
    {
        PushSubscription::create(['user_id' => $userId, 'endpoint' => 'https://e/' . $userId, 'public_key' => 'p', 'auth_token' => 'a']);
    }

    public function test_punya_push_menit_0_kirim_push_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame('kirim', $hasil['keputusan']);
        $this->assertSame([['kanal' => 'push', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_tanpa_push_menit_0_kirim_wa_pasien(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame([['kanal' => 'whatsapp', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_skip_bila_belum_lewat_interval_ulang(): void
    {
        $k = $this->kejadian(['terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(2)]);
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(5));

        $this->assertSame('skip', $hasil['keputusan']);
        $this->assertSame([], $hasil['aksi']);
    }

    public function test_menit_30_punya_push_kirim_push_dan_wa_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(30));

        $this->assertContains(['kanal' => 'push', 'target' => 'pasien'], $hasil['aksi']);
        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pasien'], $hasil['aksi']);
    }

    public function test_menit_60_eskalasi_ke_pmo(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pmo'], $hasil['aksi']);
    }

    public function test_menit_60_tidak_eskalasi_dua_kali(): void
    {
        $k = $this->kejadian(['eskalasi_pmo' => true, 'terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(45)]);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $targets = array_column($hasil['aksi'], 'target');
        $this->assertNotContains('pmo', $targets);
    }

    public function test_lewat_batas_akhir_terlewat(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(121));

        $this->assertSame('terlewat', $hasil['keputusan']);
    }
}
