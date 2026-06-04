<?php

namespace App\Http\Controllers;

use App\Models\JadwalMinumObat;
use App\Models\PengingatKejadian;
use App\Models\PengingatMoLog;
use App\Repos\PengingatKejadianRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KonfirmasiPengingatController extends Controller
{
    public function show(PengingatKejadian $kejadian)
    {
        $this->pastikanBerhak($kejadian);
        $jadwal = JadwalMinumObat::with('obat')->findOrFail($kejadian->jadwal_id);

        return view('pengingat.konfirmasi', [
            'kejadian' => $kejadian,
            'jadwal'   => $jadwal,
            'namaObat' => $jadwal->obat?->nama ?? 'Obat',
            'jamSlot'  => $kejadian->waktu_jadwal->format('H:i'),
        ]);
    }

    public function store(Request $request, PengingatKejadian $kejadian)
    {
        $this->pastikanBerhak($kejadian);

        $request->validate([
            'foto_obat' => ['required', 'image', 'max:5120'],
        ]);

        if ($kejadian->status !== PengingatKejadian::STATUS_MENUNGGU) {
            return redirect()->route($request->user()->homeRoute())
                ->with('info', 'Pengingat ini sudah ditindaklanjuti.');
        }

        $jadwal = JadwalMinumObat::with('obat')->findOrFail($kejadian->jadwal_id);
        $now    = now();
        $jamSlot = $kejadian->waktu_jadwal->format('H:i');
        $jamKini = $now->format('H:i');

        $path = $request->file('foto_obat')->store('pengingat-mo', 'public');

        $log = PengingatMoLog::create([
            'id_jo'           => $jadwal->id,
            'id_user'         => $kejadian->user_pasien_id,
            'nama_pasien'     => $jadwal->nama_pasien,
            'nama_obat'       => $jadwal->obat?->nama,
            'tgl_minum_obat'  => $now->toDateString(),
            'jam_minum_obat'  => $now->format('H:i:s'),
            'jam_slot_target' => $kejadian->waktu_jadwal->format('H:i:s'),
            'patuh_menit'     => PengingatMoLog::calculatePatuhMenit($jamSlot, $jamKini),
            'foto_obat'       => $path,
            'status'          => 'aktif',
            'created_by'      => Auth::id(),
        ]);

        PengingatKejadianRepository::tandaiDikonfirmasi($kejadian, $log->id, $now);

        return redirect()->route($request->user()->homeRoute())
            ->with('success', 'Terima kasih, konfirmasi minum obat tersimpan.');
    }

    private function pastikanBerhak(PengingatKejadian $kejadian): void
    {
        $uid = Auth::id();
        if ($uid !== $kejadian->user_pasien_id && $uid !== $kejadian->user_pmo_id) {
            abort(403);
        }
    }
}
