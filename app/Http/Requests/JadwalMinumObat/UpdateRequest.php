<?php

namespace App\Http\Requests\JadwalMinumObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-mo.edit');
    }

    public function rules(): array
    {
        return [
            'id_pasien_pmo' => [
                'required',
                'uuid',
                Rule::exists('pasien_pmos', 'id'),
            ],
            'obat_id' => [
                'required',
                'uuid',
                Rule::exists('master_obats', 'id')->whereNull('deleted_at'),
            ],
            'tgl_mulai' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'frekuensi_per_hari' => 'required|integer|min:1|max:12',
            'catatan_dosis' => 'nullable|string|max:500',
            'status' => 'required|string|in:aktif,nonaktif,selesai',
        ];
    }
}
