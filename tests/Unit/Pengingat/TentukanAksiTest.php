<?php

namespace Tests\Unit\Pengingat;

use App\Models\PengaturanPengingat;
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
        // Default: N=4, X=15, M=3
        PengaturanPengingat::create(PengaturanPengingat::defaults());
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
        PushSubscription::create(['user_id' => $userId, 'endpoint' => 'https://e/'.$userId, 'public_key' => 'p', 'auth_token' => 'a']);
    }

    public function test_pengingat_pertama_punya_push_kirim_push_pasien(): void
    {
        $k = $this->kejadian();
        $this->beriPush($k->user_pasien_id);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame('kirim', $hasil['keputusan']);
        $this->assertSame([['kanal' => 'push', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_pengingat_pertama_tanpa_push_kirim_wa_pasien(): void
    {
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(0));

        $this->assertSame([['kanal' => 'whatsapp', 'target' => 'pasien']], $hasil['aksi']);
    }

    public function test_skip_bila_belum_lewat_interval(): void
    {
        // terakhir kirim 2 menit lalu, interval 15 → skip
        $k = $this->kejadian(['terakhir_dikirim_pada' => $this->jadwal->copy()->addMinutes(2)]);

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(5));

        $this->assertSame('skip', $hasil['keputusan']);
        $this->assertSame([], $hasil['aksi']);
    }

    public function test_belum_libatkan_pmo_sebelum_nomor_m(): void
    {
        // menit 15 → nomor 2 (< M=3): belum ada PMO
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(15));

        $targets = array_column($hasil['aksi'], 'target');
        $this->assertNotContains('pmo', $targets);
    }

    public function test_libatkan_pmo_sejak_nomor_m(): void
    {
        // menit 30 → nomor 3 (>= M=3): PMO ikut
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(30));

        $this->assertContains(['kanal' => 'whatsapp', 'target' => 'pmo'], $hasil['aksi']);
    }

    public function test_lewat_jumlah_maksimum_terlewat(): void
    {
        // N=4, X=15 → nomor>4 saat selisih>=60
        $k = $this->kejadian();

        $hasil = PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(60));

        $this->assertSame('terlewat', $hasil['keputusan']);
    }

    public function test_pengaturan_kustom_mengubah_ambang_terlewat(): void
    {
        // Ubah N=2, X=10 → nomor>2 saat selisih>=20
        PengaturanPengingat::query()->update(['mo_jumlah' => 2, 'mo_interval_menit' => 10]);
        $k = $this->kejadian();

        $this->assertSame('kirim', PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(10))['keputusan']);
        $this->assertSame('terlewat', PengingatTickService::tentukanAksi($k, $this->jadwal->copy()->addMinutes(20))['keputusan']);
    }
}
