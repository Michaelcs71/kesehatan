<?php

namespace App\Jobs;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PengingatKirimLog;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KirimPengingatCgdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        public string $pesertaId,
        public string $fase,   // 'dibuat' | 'h1'
    ) {}

    public function handle(WhatsAppSender $wa, WebPushSender $push): void
    {
        $peserta = JadwalCgdPeserta::with(['jadwalCgd', 'pasienPmo'])->find($this->pesertaId);
        if (! $peserta || ! $peserta->jadwalCgd || $peserta->jadwalCgd->status !== 'aktif') {
            return;
        }

        $jadwal = $peserta->jadwalCgd;
        $pp = $peserta->pasienPmo;

        if ($pp?->id_user) {
            $this->kirimKe($pp->id_user, 'pasien', $peserta->nama_pasien, $peserta, $jadwal, $wa, $push);
        }
        if ($pp?->pmo_user_id) {
            $this->kirimKe($pp->pmo_user_id, 'pmo', $peserta->nama_pmo ?? 'PMO', $peserta, $jadwal, $wa, $push);
        }
    }

    private function kirimKe(
        string $userId,
        string $target,
        string $namaTujuan,
        JadwalCgdPeserta $peserta,
        JadwalCgd $jadwal,
        WhatsAppSender $wa,
        WebPushSender $push,
    ): void {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $tanggal = $jadwal->tgl_jadwal_cgd->format('d M Y');
        $jam = substr((string) $jadwal->jam_mulai, 0, 5);
        $statusPuasa = $jadwal->puasa === 'Wajib' ? 'Wajib puasa' : 'Tidak perlu puasa';
        $prefix = $this->fase === 'h1' ? 'Besok' : 'Info jadwal';
        $url = url("/jadwal-cgd/{$jadwal->id}");

        $punyaPush = PushSubscription::where('user_id', $userId)->exists();

        try {
            if ($punyaPush) {
                $judul = $target === 'pmo' ? 'Pengingat CGD pasien Anda' : 'Pengingat Cek Gula Darah';
                $isi = "{$prefix}: cek gula darah {$tanggal} jam {$jam} di {$jadwal->tempat}. {$statusPuasa}.";
                $push->kirimKeUser($userId, ['judul' => $judul, 'isi' => $isi, 'url' => $url]);
                $this->catat($peserta->id, 'push', $target, 'terkirim', null);
            } else {
                $no = $this->normalkanNomor($user->whatsapp_number);
                if (! $no) {
                    $this->catat($peserta->id, 'whatsapp', $target, 'gagal', 'nomor WA kosong');

                    return;
                }
                $template = config('pengingat.whatsapp.cloud_api.template_cgd', 'pengingat_cgd');
                $wa->kirimTemplate($no, $template, [$namaTujuan, $tanggal, $jam, $jadwal->tempat, $statusPuasa]);
                $this->catat($peserta->id, 'whatsapp', $target, 'terkirim', null);
            }
        } catch (\Throwable $e) {
            $this->catat($peserta->id, $punyaPush ? 'push' : 'whatsapp', $target, 'gagal', $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[pengingat] KirimPengingatCgdJob gagal total', [
            'peserta' => $this->pesertaId, 'fase' => $this->fase, 'error' => $e->getMessage(),
        ]);
    }

    private function catat(string $pesertaId, string $kanal, string $target, string $status, ?string $error): void
    {
        PengingatKirimLog::create([
            'kejadian_id' => null,
            'peserta_id' => $pesertaId,
            'kanal' => $kanal,
            'target' => $target,
            'fase' => $this->fase,
            'status' => $status,
            'error' => $error,
        ]);
    }

    private function normalkanNomor(?string $no): ?string
    {
        if (blank($no)) {
            return null;
        }
        $no = preg_replace('/\D+/', '', $no);
        if (str_starts_with($no, '0')) {
            $no = '62'.substr($no, 1);
        } elseif (! str_starts_with($no, '62')) {
            $no = '62'.$no;
        }

        return $no;
    }
}
