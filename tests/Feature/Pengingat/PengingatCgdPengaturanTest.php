<?php

namespace Tests\Feature\Pengingat;

use App\Jobs\KirimPengingatCgdJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use App\Models\PengaturanPengingat;
use App\Services\PengingatTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PengingatCgdPengaturanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pesertaBaru(string $tglJadwal): JadwalCgdPeserta
    {
        $pp = PasienPmo::factory()->create();
        $jadwal = JadwalCgd::factory()->create(['tgl_jadwal_cgd' => $tglJadwal, 'status' => 'aktif']);

        return JadwalCgdPeserta::factory()->create([
            'jadwal_cgd_id' => $jadwal->id,
            'id_pasien_pmo' => $pp->id,
        ]);
    }

    public function test_fase_dibuat_dilewati_saat_cgd_dibuat_aktif_false(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-06-21 09:00:00'));
        PengaturanPengingat::create(PengaturanPengingat::defaults() + []);
        PengaturanPengingat::query()->update(['cgd_dibuat_aktif' => false]);

        $peserta = $this->pesertaBaru('2026-06-30'); // jauh hari → hanya fase 'dibuat' yang relevan

        PengingatTickService::prosesCgd();

        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'dibuat');
        $this->assertNull($peserta->refresh()->dikirim_dibuat_pada);
    }

    public function test_jam_h1_dari_pengaturan_dipakai(): void
    {
        Queue::fake();
        // Set jam H-1 = 20:00. Event 2026-06-22 → H-1 gate = 2026-06-21 20:00.
        PengaturanPengingat::create(PengaturanPengingat::defaults());
        PengaturanPengingat::query()->update(['cgd_jam_h1' => '20:00']);

        $peserta = $this->pesertaBaru('2026-06-22');
        $peserta->forceFill(['dikirim_dibuat_pada' => now()->subDay()])->save();

        // Jam 19:00 → belum lewat gate 20:00 → tidak kirim h1
        Carbon::setTestNow(Carbon::parse('2026-06-21 19:00:00'));
        PengingatTickService::prosesCgd();
        Queue::assertNotPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1');

        // Jam 20:30 → sudah lewat gate → kirim h1
        Carbon::setTestNow(Carbon::parse('2026-06-21 20:30:00'));
        PengingatTickService::prosesCgd();
        Queue::assertPushed(fn (KirimPengingatCgdJob $j) => $j->fase === 'h1' && $j->pesertaId === $peserta->id);
    }
}
