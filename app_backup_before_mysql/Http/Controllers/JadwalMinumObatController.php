<?php

namespace App\Http\Controllers;

use App\Http\Requests\JadwalMinumObat\{
    IndexRequest,
    StoreRequest,
    UpdateRequest,
    QuickCreateObatRequest
};
use App\Services\JadwalMinumObatService;
use Illuminate\Http\JsonResponse;

class JadwalMinumObatController extends Controller
{
    protected function getViewPath(): string
    {
        return 'jadwal-mo';
    }

    public function index()
    {
        return view($this->getViewPath() . '.index');
    }

    public function getData(IndexRequest $request): JsonResponse
    {
        $data = JadwalMinumObatService::getAllJadwal($request->validated());
        return response()->json($data);
    }

    public function create()
    {
        return view($this->getViewPath() . '.form');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $result = JadwalMinumObatService::bulkCreate($request->validated());
            return response()->json([
                'success' => true,
                'message' => "Berhasil membuat {$result['count']} jadwal minum obat.",
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(string $id)
    {
        $jadwal = JadwalMinumObatService::findJadwalById($id);

        if (!$jadwal) {
            return redirect()->route('jadwal-mo.index')
                ->with('error', 'Jadwal tidak ditemukan.');
        }

        return view($this->getViewPath() . '.show', compact('id'));
    }

    public function showData(string $id): JsonResponse
    {
        $jadwal = JadwalMinumObatService::findJadwalById($id);

        if (!$jadwal) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($jadwal);
    }

    public function edit(string $id)
    {
        $jadwal = JadwalMinumObatService::findJadwalById($id);

        if (!$jadwal) {
            return redirect()->route('jadwal-mo.index')
                ->with('error', 'Jadwal tidak ditemukan.');
        }

        return view($this->getViewPath() . '.form', compact('id'));
    }

    public function update(UpdateRequest $request, string $id): JsonResponse
    {
        try {
            JadwalMinumObatService::updateJadwal($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diupdate.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            JadwalMinumObatService::deleteJadwal($id);
            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deactivate(string $id): JsonResponse
    {
        try {
            JadwalMinumObatService::deactivate($id);
            return response()->json(['success' => true, 'message' => 'Jadwal dinonaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            JadwalMinumObatService::activate($id);
            return response()->json(['success' => true, 'message' => 'Jadwal diaktifkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function markSelesai(string $id): JsonResponse
    {
        try {
            JadwalMinumObatService::markSelesai($id);
            return response()->json(['success' => true, 'message' => 'Jadwal ditandai selesai.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ===== Options endpoints =====

    public function pasienPmoOptions(): JsonResponse
    {
        return response()->json(['data' => JadwalMinumObatService::getPasienPmoOptions()]);
    }

    public function obatOptions(): JsonResponse
    {
        $search = request('search');
        return response()->json(['data' => JadwalMinumObatService::getObatOptions($search)]);
    }

    // ===== Quick-add obat =====

    public function quickCreateObat(QuickCreateObatRequest $request): JsonResponse
    {
        try {
            $obat = JadwalMinumObatService::quickCreateObat($request->validated());

            // Load satuan untuk response
            $obat->load('satuan:id,nama,singkatan');
            $satuan = $obat->satuan?->singkatan ?? $obat->satuan?->nama;
            $dosis  = $obat->dosis_default ? " {$obat->dosis_default}" : '';
            $satuanStr = $satuan ? " ({$satuan})" : '';

            return response()->json([
                'success' => true,
                'message' => 'Obat baru berhasil ditambahkan ke master.',
                'data' => [
                    'id'            => $obat->id,
                    'nama'          => $obat->nama,
                    'dosis_default' => $obat->dosis_default,
                    'satuan'        => $satuan,
                    'aturan_minum'  => $obat->aturan_minum,
                    'label'         => $obat->nama . $dosis . $satuanStr,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get satuan obat options (untuk quick-add modal)
     */
    public function satuanOptions(): JsonResponse
    {
        $satuans = \App\Models\MasterSatuanObat::where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'singkatan'])
            ->map(fn($s) => [
                'id'        => $s->id,
                'nama'      => $s->nama,
                'singkatan' => $s->singkatan,
                'label'     => $s->nama . ($s->singkatan ? " ({$s->singkatan})" : ''),
            ])
            ->toArray();

        return response()->json(['data' => $satuans]);
    }
}
