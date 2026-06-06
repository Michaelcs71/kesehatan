<?php

namespace App\Http\Requests\PengingatCgdLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-cgd.edit');
    }

    public function rules(): array
    {
        return [
            'tgl_cgd' => 'required|date|before_or_equal:today',
            'jam_cgd' => 'required|date_format:H:i',
            'hasil_mgdl' => 'required|integer|min:20|max:800',
            'foto_layar' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:8192',
            'status' => 'required|string|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_cgd.required' => 'Tanggal wajib diisi.',
            'tgl_cgd.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'jam_cgd.required' => 'Jam wajib diisi.',
            'hasil_mgdl.required' => 'Hasil wajib diisi.',
            'hasil_mgdl.min' => 'Hasil minimal 20 mg/dL.',
            'hasil_mgdl.max' => 'Hasil maksimal 800 mg/dL.',
            'status.required' => 'Status wajib dipilih.',
        ];
    }
}
