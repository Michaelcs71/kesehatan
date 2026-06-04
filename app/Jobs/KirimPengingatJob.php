<?php

namespace App\Jobs;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PengingatKirimLog;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KirimPengingatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        public string $kejadianId,
        public string $kanal,   // 'push' | 'whatsapp'
        public string $target,  // 'pasien' | 'pmo'
    ) {}

    public function handle(WhatsAppSender $wa, WebPushSender $push): void
    {
        $kejadian = PengingatKejadian::find($this->kejadianId);
        if (! $kejadian || $kejadian->status !== PengingatKejadian::STATUS_MENUNGGU) {
            return; // sudah dikonfirmasi / terlewat → jangan kirim
        }

        $jadwal = JadwalMinumObat::with('obat')->find($kejadian->jadwal_id);
        if (! $jadwal) {
            return;
        }

        $userId = $this->target === 'pmo' ? $kejadian->user_pmo_id : $kejadian->user_pasien_id;
        $user = $userId ? User::find($userId) : null;
        if (! $user) {
            return;
        }

        $namaObat = $jadwal->obat?->nama ?? 'obat';
        $jamSlot = $kejadian->waktu_jadwal->format('H:i');
        $url = url("/pengingat/{$kejadian->id}/konfirmasi"); // route dibuat penuh di task berikutnya

        try {
            if ($this->kanal === 'push') {
                $judul = $this->target === 'pmo' ? 'Pasien Anda belum minum obat' : 'Waktunya minum obat';
                $isi = "Obat {$namaObat} jadwal jam {$jamSlot}.";
                $push->kirimKeUser($user->id, ['judul' => $judul, 'isi' => $isi, 'url' => $url]);
            } else {
                $no = $this->normalkanNomor($user->whatsapp_number);
                if (! $no) {
                    $this->catat($kejadian->id, 'gagal', 'nomor WA kosong');

                    return;
                }
                $template = config('pengingat.whatsapp.cloud_api.template_mo', 'pengingat_obat');
                $namaTujuan = $this->target === 'pmo' ? ($jadwal->nama_pmo ?? 'PMO') : ($jadwal->nama_pasien ?? 'Pasien');
                $wa->kirimTemplate($no, $template, [$namaTujuan, $namaObat, $jamSlot, $url]);
            }

            $this->catat($kejadian->id, 'terkirim', null);
        } catch (\Throwable $e) {
            $this->catat($kejadian->id, 'gagal', $e->getMessage());
            throw $e; // biar retry mekanisme antrian jalan
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[pengingat] KirimPengingatJob gagal total', [
            'kejadian' => $this->kejadianId, 'kanal' => $this->kanal, 'error' => $e->getMessage(),
        ]);
        $this->catat($this->kejadianId, 'gagal', $e->getMessage());
    }

    private function catat(string $kejadianId, string $status, ?string $error): void
    {
        PengingatKirimLog::create([
            'kejadian_id' => $kejadianId,
            'kanal' => $this->kanal,
            'target' => $this->target,
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
