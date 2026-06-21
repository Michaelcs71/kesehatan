<?php

namespace Tests\Feature\JadwalCgd;

use App\Models\JadwalCgd;
use App\Models\PasienPmo;
use App\Models\User;
use App\Services\JadwalCgdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalCgdPesertaSyncTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function dataJadwal(array $pesertaIds): array
    {
        return [
            'tgl_jadwal_cgd' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Wajib',
            'tempat' => 'Posyandu Uji',
            'catatan' => null,
            'peserta' => $pesertaIds,
        ];
    }

    public function test_create_menyimpan_peserta_dengan_snapshot_nama(): void
    {
        $this->actingAs($this->admin());
        $pp = PasienPmo::factory()->create(['nama_pasien' => 'Budi', 'nama_pmo' => 'Siti']);

        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$pp->id]));

        $this->assertCount(1, $jadwal->refresh()->peserta);
        $this->assertSame('Budi', $jadwal->peserta->first()->nama_pasien);
        $this->assertSame('Siti', $jadwal->peserta->first()->nama_pmo);
    }

    public function test_update_menambah_dan_menghapus_peserta(): void
    {
        $this->actingAs($this->admin());
        $a = PasienPmo::factory()->create();
        $b = PasienPmo::factory()->create();

        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$a->id]));
        $pesertaLamaId = $jadwal->refresh()->peserta->first()->id;

        // Tandai notif "dibuat" peserta lama sudah terkirim → tak boleh ter-reset.
        $jadwal->peserta()->where('id', $pesertaLamaId)->update(['dikirim_dibuat_pada' => now()]);

        JadwalCgdService::updateJadwal($jadwal->id, [
            'tgl_jadwal_cgd' => $jadwal->tgl_jadwal_cgd->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Wajib',
            'tempat' => 'Posyandu Uji',
            'status' => 'aktif',
            'peserta' => [$b->id], // a dihapus, b ditambah
        ]);

        $peserta = $jadwal->refresh()->peserta;
        $this->assertCount(1, $peserta);
        $this->assertSame($b->id, $peserta->first()->id_pasien_pmo);
        $this->assertNull($peserta->first()->dikirim_dibuat_pada); // peserta baru fresh
    }

    public function test_update_tanpa_key_peserta_tidak_mengubah_peserta(): void
    {
        $this->actingAs($this->admin());
        $a = PasienPmo::factory()->create();
        $jadwal = JadwalCgdService::createJadwal($this->dataJadwal([$a->id]));

        JadwalCgdService::updateJadwal($jadwal->id, [
            'tgl_jadwal_cgd' => $jadwal->tgl_jadwal_cgd->toDateString(),
            'jam_mulai' => '07:00',
            'jam_berakhir' => '10:00',
            'puasa' => 'Tidak',
            'tempat' => 'Posyandu Uji',
            'status' => 'aktif',
            // tanpa 'peserta'
        ]);

        $this->assertCount(1, $jadwal->refresh()->peserta);
    }
}
