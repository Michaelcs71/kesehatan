<?php

namespace Tests\Feature\Pengingat;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KonfirmasiPengingatTest extends TestCase
{
    use RefreshDatabase;

    private function buatKejadianUntukPasien(User $pasien): PengingatKejadian
    {
        $jadwal = JadwalMinumObat::factory()->create(['jam_mulai' => '08:00:00', 'frekuensi_per_hari' => 1]);
        $pp = $jadwal->pasienPmo;
        $pp->update(['id_user' => $pasien->id]);

        return PengingatKejadian::create([
            'jenis' => 'mo', 'jadwal_id' => $jadwal->id, 'id_pasien_pmo' => $pp->id,
            'user_pasien_id' => $pasien->id, 'user_pmo_id' => $pp->pmo_user_id,
            'waktu_jadwal' => Carbon::parse(now()->toDateString() . ' 08:00:00'),
            'status' => PengingatKejadian::STATUS_MENUNGGU,
        ]);
    }

    public function test_pasien_konfirmasi_membuat_log_dan_menutup_kejadian(): void
    {
        Storage::fake('public');
        $pasien = User::factory()->create(['role' => 'pasien']);
        $k = $this->buatKejadianUntukPasien($pasien);

        $resp = $this->actingAs($pasien)->post(route('pengingat.konfirmasi.store', $k->id), [
            'foto_obat' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $resp->assertRedirect();
        $k->refresh();
        $this->assertSame(PengingatKejadian::STATUS_DIKONFIRMASI, $k->status);
        $this->assertNotNull($k->konfirmasi_log_id);
        $this->assertDatabaseHas('pengingat_mo_logs', ['id' => $k->konfirmasi_log_id, 'id_jo' => $k->jadwal_id]);
    }

    public function test_user_lain_tidak_boleh_konfirmasi(): void
    {
        $pasien = User::factory()->create(['role' => 'pasien']);
        $orangLain = User::factory()->create(['role' => 'pasien']);
        $k = $this->buatKejadianUntukPasien($pasien);

        $this->actingAs($orangLain)
            ->post(route('pengingat.konfirmasi.store', $k->id), ['foto_obat' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();
    }
}
