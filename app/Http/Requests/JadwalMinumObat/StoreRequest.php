<?php

namespace App\Http\Requests\JadwalMinumObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-mo.create');
    }

    public function rules(): array
    {
        return [
            'id_pasien_pmo' => [
                'required',
                'uuid',
                Rule::exists('pasien_pmos', 'id')->where('is_active', true),
            ],

            'obats' => 'required|array|min:1',
            'obats.*.obat_id' => [
                'required',
                'uuid',
                Rule::exists('master_obats', 'id')
                    ->whereNull('deleted_at'),
            ],
            'obats.*.tgl_mulai' => 'required|date',
            'obats.*.jam_mulai' => 'required|date_format:H:i',
            'obats.*.frekuensi_per_hari' => 'required|integer|min:1|max:12',
            'obats.*.catatan_dosis' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'id_pasien_pmo.required' => 'Pasien wajib dipilih.',
            'id_pasien_pmo.exists' => 'Mapping pasien-PMO tidak valid atau tidak aktif.',
            'obats.required' => 'Minimal pilih 1 obat.',
            'obats.min' => 'Minimal pilih 1 obat.',
            'obats.*.obat_id.required' => 'Obat wajib dipilih.',
            'obats.*.obat_id.exists' => 'Obat yang dipilih tidak valid.',
            'obats.*.tgl_mulai.required' => 'Tanggal mulai wajib diisi.',
            'obats.*.jam_mulai.required' => 'Jam mulai wajib diisi.',
            'obats.*.jam_mulai.date_format' => 'Format jam harus HH:MM (contoh: 08:00).',
            'obats.*.frekuensi_per_hari.required' => 'Frekuensi minum per hari wajib diisi.',
            'obats.*.frekuensi_per_hari.min' => 'Frekuensi minimal 1 kali sehari.',
            'obats.*.frekuensi_per_hari.max' => 'Frekuensi maksimal 12 kali sehari.',
        ];
    }
}
