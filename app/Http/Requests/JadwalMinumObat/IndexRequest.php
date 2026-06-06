<?php

namespace App\Http\Requests\JadwalMinumObat;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-mo.index');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:aktif,nonaktif,selesai',
            'id_pasien_pmo' => 'nullable|uuid',
            'obat_id' => 'nullable|uuid',
            'pagenum' => 'nullable|integer|min:0',
            'pagesize' => 'nullable|integer|min:1|max:100',
        ];
    }
}
