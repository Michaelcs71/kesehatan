<?php

namespace App\Http\Requests\PengingatMoLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-mo.edit');
    }

    public function rules(): array
    {
        return [
            'tgl_minum_obat'  => 'required|date|before_or_equal:today',
            'jam_minum_obat'  => 'required|date_format:H:i',
            'jam_slot_target' => 'nullable|date_format:H:i',
            'foto_obat'       => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:8192',  // nullable saat update
            'status'          => 'required|string|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_minum_obat.required'       => 'Tanggal wajib diisi.',
            'tgl_minum_obat.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'jam_minum_obat.required'       => 'Jam wajib diisi.',
            'jam_minum_obat.date_format'    => 'Format jam harus HH:MM.',
            'foto_obat.image'               => 'File harus berupa gambar.',
            'foto_obat.mimes'               => 'Format gambar harus JPG/PNG/WEBP.',
            'foto_obat.max'                 => 'Ukuran foto maksimal 8MB.',
            'status.required'               => 'Status wajib dipilih.',
        ];
    }
}
