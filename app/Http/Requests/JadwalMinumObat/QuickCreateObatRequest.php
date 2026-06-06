<?php

namespace App\Http\Requests\JadwalMinumObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickCreateObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-mo.create');
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_obats', 'nama')->whereNull('deleted_at'),
            ],
            'satuan_id' => 'required|uuid|exists:master_satuan_obats,id',
            'kategori_id' => 'nullable|uuid|exists:master_kategori_obats,id',
            'dosis_default' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama obat wajib diisi.',
            'nama.unique' => 'Obat dengan nama ini sudah ada di master.',
            'satuan_id.required' => 'Satuan obat wajib dipilih.',
            'satuan_id.exists' => 'Satuan obat tidak valid.',
        ];
    }
}
