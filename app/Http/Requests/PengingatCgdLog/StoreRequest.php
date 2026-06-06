<?php

namespace App\Http\Requests\PengingatCgdLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-cgd.create');
    }

    public function rules(): array
    {
        return [
            'id_cgd' => [
                'required',
                'uuid',
                Rule::exists('jadwal_cgds', 'id')->where('status', 'aktif'),
            ],
            'tgl_cgd' => 'required|date|before_or_equal:today',
            'jam_cgd' => 'required|date_format:H:i',
            'hasil_mgdl' => 'required|integer|min:20|max:800',  // sanity check
            'foto_layar' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:8192',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cgd.required' => 'Jadwal CGD wajib dipilih.',
            'id_cgd.exists' => 'Jadwal CGD yang dipilih tidak valid atau sudah nonaktif.',
            'tgl_cgd.required' => 'Tanggal CGD wajib diisi.',
            'tgl_cgd.before_or_equal' => 'Tanggal CGD tidak boleh di masa depan.',
            'jam_cgd.required' => 'Jam CGD wajib diisi.',
            'jam_cgd.date_format' => 'Format jam harus HH:MM (contoh: 08:00).',
            'hasil_mgdl.required' => 'Hasil cek gula darah wajib diisi.',
            'hasil_mgdl.min' => 'Hasil minimal 20 mg/dL.',
            'hasil_mgdl.max' => 'Hasil maksimal 800 mg/dL.',
            'foto_layar.required' => 'Foto bukti hasil wajib diupload.',
            'foto_layar.image' => 'File harus berupa gambar.',
            'foto_layar.mimes' => 'Format gambar harus JPG/PNG/WEBP.',
            'foto_layar.max' => 'Ukuran foto maksimal 8MB.',
        ];
    }
}
