<?php

namespace App\Http\Requests\PengaturanPengingat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengaturan-pengingat.update');
    }

    public function rules(): array
    {
        return [
            'mo_aktif' => 'required|boolean',
            'mo_jumlah' => 'required|integer|min:1|max:20',
            'mo_interval_menit' => 'required|integer|min:1|max:180',
            'mo_pmo_mulai_ke' => 'required|integer|min:1|lte:mo_jumlah',
            'cgd_aktif' => 'required|boolean',
            'cgd_dibuat_aktif' => 'required|boolean',
            'cgd_jam_h1' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'mo_jumlah.required' => 'Jumlah pengingat MO wajib diisi.',
            'mo_jumlah.min' => 'Jumlah pengingat MO minimal 1.',
            'mo_jumlah.max' => 'Jumlah pengingat MO maksimal 20.',
            'mo_interval_menit.required' => 'Interval pengingat MO wajib diisi.',
            'mo_interval_menit.min' => 'Interval minimal 1 menit.',
            'mo_interval_menit.max' => 'Interval maksimal 180 menit.',
            'mo_pmo_mulai_ke.required' => 'Pengingat ke-berapa PMO mulai dilibatkan wajib diisi.',
            'mo_pmo_mulai_ke.lte' => 'PMO mulai dilibatkan tidak boleh melebihi jumlah pengingat.',
            'cgd_jam_h1.required' => 'Jam pengingat H-1 wajib diisi.',
            'cgd_jam_h1.date_format' => 'Format jam H-1 harus HH:MM (contoh: 17:00).',
        ];
    }
}
