<?php

namespace App\Services;

use App\Jobs\KirimPengingatJob;
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
        $batasAkhir = (int) config('pengingat.batas_akhir_menit');
        $intervalUlang = (int) config('pengingat.interval_ulang_menit');
        $waPasienMnt = (int) config('pengingat.wa_pasien_setelah_menit');
        $waPmoMnt = (int) config('pengingat.wa_pmo_setelah_menit');

        $selisih = intdiv($now->getTimestamp() - $k->waktu_jadwal->getTimestamp(), 60);

        if ($selisih > $batasAkhir) {
            return ['keputusan' => 'terlewat', 'aksi' => []];
        }

        if ($k->terakhir_dikirim_pada) {
            $sejakTerakhir = intdiv($now->getTimestamp() - $k->terakhir_dikirim_pada->getTimestamp(), 60);
            if ($sejakTerakhir < $intervalUlang) {
                return ['keputusan' => 'skip', 'aksi' => []];
            }
        }

        $pasienPunyaPush = PushSubscription::where('user_id', $k->user_pasien_id)->exists();
        $pmoPunyaPush = $k->user_pmo_id && PushSubscription::where('user_id', $k->user_pmo_id)->exists();

        $aksi = [];

        // --- Kanal pasien ---
        if ($selisih < $waPasienMnt) {
            // Sebelum ambang WA: push bila ada, kalau tidak ada push → WA sejak menit-0.
            $aksi[] = $pasienPunyaPush
                ? ['kanal' => 'push', 'target' => 'pasien']
                : ['kanal' => 'whatsapp', 'target' => 'pasien'];
        } else {
            // Sudah lewat ambang WA: kirim WA; push tetap diulang bila ada.
            if ($pasienPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pasien'];
            }
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pasien'];
        }

        // --- Eskalasi PMO ---
        if ($selisih >= $waPmoMnt && ! $k->eskalasi_pmo && $k->user_pmo_id) {
            $aksi[] = ['kanal' => 'whatsapp', 'target' => 'pmo'];
            if ($pmoPunyaPush) {
                $aksi[] = ['kanal' => 'push', 'target' => 'pmo'];
            }
        }

        return ['keputusan' => 'kirim', 'aksi' => $aksi];
    }

    public static function jalankan(): void
    {
        if (config('pengingat.aktif.mo')) {
            self::materialisasiMo();
        }
        self::proses();
    }

    public static function materialisasiMo(): void
    {
        $now = Carbon::now();
        $hariIni = $now->toDateString();
        $batas = (int) config('pengingat.batas_akhir_menit');

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
}
