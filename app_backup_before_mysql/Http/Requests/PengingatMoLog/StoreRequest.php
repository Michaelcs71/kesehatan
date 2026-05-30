<?php

namespace App\Http\Requests\PengingatMoLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-mo.create');
    }

    public function rules(): array
    {
        return [
            'id_jo' => [
                'required',
                'uuid',
                Rule::exists('jadwal_minum_obats', 'id')->where('status', 'aktif'),
            ],
            'tgl_minum_obat'  => 'required|date|before_or_equal:today',
            'jam_minum_obat'  => 'required|date_format:H:i',
            'jam_slot_target' => 'nullable|date_format:H:i',
            'foto_obat'       => 'required|file|image|mimes:jpeg,jpg,png,webp|max:8192',  // max 8MB
        ];
    }

    public function messages(): array
    {
        return [
            'id_jo.required'                => 'Jadwal minum obat wajib dipilih.',
            'id_jo.exists'                  => 'Jadwal yang dipilih tidak valid atau sudah nonaktif.',
            'tgl_minum_obat.required'       => 'Tanggal minum obat wajib diisi.',
            'tgl_minum_obat.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'jam_minum_obat.required'       => 'Jam minum obat wajib diisi.',
            'jam_minum_obat.date_format'    => 'Format jam harus HH:MM (contoh: 08:30).',
            'jam_slot_target.date_format'   => 'Format jam slot harus HH:MM.',
            'foto_obat.required'            => 'Foto bukti minum obat wajib diupload.',
            'foto_obat.image'               => 'File harus berupa gambar.',
            'foto_obat.mimes'               => 'Format gambar harus JPG/PNG/WEBP.',
            'foto_obat.max'                 => 'Ukuran foto maksimal 8MB.',
        ];
    }
}
