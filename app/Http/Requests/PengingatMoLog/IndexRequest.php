<?php

namespace App\Http\Requests\PengingatMoLog;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-mo.index');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:aktif,nonaktif',
            'id_jo' => 'nullable|uuid',
            'tgl_start' => 'nullable|date',
            'tgl_end' => 'nullable|date',
            'patuh_kategori' => 'nullable|string|in:tepat_waktu,terlambat,sangat_terlambat',
            'pagenum' => 'nullable|integer|min:0',
            'pagesize' => 'nullable|integer|min:1|max:100',
        ];
    }
}
