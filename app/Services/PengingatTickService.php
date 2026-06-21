<?php

namespace App\Services;

use App\Jobs\KirimPengingatCgdJob;
use App\Jobs\KirimPengingatJob;
use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PushSubscription;
use App\Repos\PengingatKejadianRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PengingatTickService
{
    /**
     * Keputusan murni untuk satu kejadian (tanpa side-effect).
     *
     * @return array{keputusan:string,aksi:array<int,array{kanal:string,target:string}>}
     */
    public static function tentukanAksi(PengingatKejadian $k, Carbon $now): array
    {
        $s = PengaturanPengingatService::get();
        $jumlah = (int) $s->mo_jumlah;                       // N
        $interval = max(1, (int) $s->mo_interval_menit);     // X
        $pmoMulaiKe = (int) $s->mo_pmo_mulai_ke;             // M

        $selisih = intdiv($now->getTimestamp() - $k->waktu_jadwal->getTimestamp(), 60);

        // Nomor pengingat ke-berapa (1-based)
        $nomor = intdiv($selisih, $interval) + 1;

        if ($nomor > $jumlah) {
            return ['keputusan' => 'terlewat', 'aksi' => []];
        }

        // Throttle: jangan kirim ulang sebelum interval berlalu sejak terakhir
        if ($k->terakhir_dikirim_pada) {
            $sejakTerakhir = intdiv($now->getTimestamp() - $k->terakhir_dikirim_pada->getTimestamp(), 60);
            if ($sejakTerakhir < $interval) {
                return ['keputusan' => 'skip', 'aksi' => []];
            }
        }

        $pasienPunyaPush = PushSubscription::where('user_id', $k->user_pasien_id)->exists();
        $pmoPunyaPush = $k->user_pmo_id && PushSubscription::where('user_id', $k->user_pmo_id)->exists();

        $aksi = [];

        // --- Kanal pasien: tiap pengingat, push bila ada, selain itu WA ---
        $aksi[] = $pasienPunyaPush
            ? ['kanal' => 'push', 'target' => 'pasien']
            : ['kanal' => 'whatsapp', 'target' => 'pasien'];

        // --- PMO: ikut tiap pengingat sejak nomor >= M ---
        if ($k->user_pmo_id && $nomor >= $pmoMulaiKe) {
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pmo'];
            if ($pmoPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pmo'];
            }
        }

        return ['keputusan' => 'kirim', 'aksi' => $aksi];
    }

    public static function jalankan(): void
    {
        $s = PengaturanPengingatService::get();

        if ($s->mo_aktif) {
            self::materialisasiMo();
        }
        self::proses();

        if ($s->cgd_aktif) {
            self::prosesCgd();
        }
    }

    public static function materialisasiMo(): void
    {
        $now = Carbon::now();
        $hariIni = $now->toDateString();
        $s = PengaturanPengingatService::get();
        $batas = (int) $s->mo_jumlah * max(1, (int) $s->mo_interval_menit);

        JadwalMinumObat::query()->active()
            ->with('pasienPmo')
            ->whereDate('tgl_mulai', '<=', $hariIni)
            ->chunk(200, function ($jadwals) use ($now, $hariIni, $batas) {
                foreach ($jadwals as $jadwal) {
                    $pp = $jadwal->pasienPmo;
                    foreach ($jadwal->slot_jam_harian as $slot) {
                        $waktu = Carbon::parse($hariIni.' '.$slot.':00');

                        $selisih = intdiv($now->getTimestamp() - $waktu->getTimestamp(), 60);
                        if ($selisih < 0 || $selisih > $batas) {
                            continue;
                        }

                        PengingatKejadianRepository::firstOrCreateUntukSlot('mo', $jadwal->id, $waktu, [
                            'id_pasien_pmo' => $jadwal->id_pasien_pmo,
                            'user_pasien_id' => $pp?->id_user,
                            'user_pmo_id' => $pp?->pmo_user_id,
                        ]);
                    }
                }
            });
    }

    public static function proses(): void
    {
        $now = Carbon::now();

        foreach (PengingatKejadianRepository::menunggu() as $k) {
            try {
                $hasil = self::tentukanAksi($k, $now);

                if ($hasil['keputusan'] === 'terlewat') {
                    PengingatKejadianRepository::tandaiTerlewat($k);

                    continue;
                }
                if ($hasil['keputusan'] === 'skip' || $hasil['aksi'] === []) {
                    continue;
                }

                foreach ($hasil['aksi'] as $aksi) {
                    KirimPengingatJob::dispatch($k->id, $aksi['kanal'], $aksi['target']);
                    PengingatKejadianRepository::tandaiDikirim($k, $aksi['kanal'], $aksi['target'], $now);
                }
            } catch (\Throwable $e) {
                Log::error('[pengingat] gagal memproses kejadian', ['id' => $k->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Kirim pengingat CGD: sekali saat jadwal aktif (fase 'dibuat') &
     * sekali H-1 (fase 'h1'). Idempoten via penanda waktu di peserta.
     */
    public static function prosesCgd(): void
    {
        $now = Carbon::now();
        $jamH1 = (string) config('pengingat.cgd.jam_h1', '17:00');

        JadwalCgd::query()
            ->where('status', 'aktif')
            ->whereDate('tgl_jadwal_cgd', '>=', $now->toDateString())
            ->with('peserta')
            ->chunk(100, function ($jadwals) use ($now, $jamH1) {
                foreach ($jadwals as $jadwal) {
                    $waktuH1 = Carbon::parse($jadwal->tgl_jadwal_cgd->toDateString().' '.$jamH1)->subDay();

                    foreach ($jadwal->peserta as $peserta) {
                        if ($peserta->dikirim_dibuat_pada === null) {
                            self::dispatchCgd($peserta, 'dibuat', 'dikirim_dibuat_pada', $now);
                        }

                        if (
                            $peserta->dikirim_h1_pada === null
                            && $now->greaterThanOrEqualTo($waktuH1)
                            && $jadwal->tgl_jadwal_cgd->toDateString() > $now->toDateString()
                        ) {
                            self::dispatchCgd($peserta, 'h1', 'dikirim_h1_pada', $now);
                        }
                    }
                }
            });
    }

    private static function dispatchCgd(JadwalCgdPeserta $peserta, string $fase, string $kolom, Carbon $now): void
    {
        KirimPengingatCgdJob::dispatch($peserta->id, $fase);
        $peserta->forceFill([$kolom => $now])->save();
    }
}
