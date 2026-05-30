<?php

namespace App\Http\Requests\PengingatCgdLog;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengingat-cgd.index');
    }

    public function rules(): array
    {
        return [
            'search'         => 'nullable|string|max:255',
            'status'         => 'nullable|string|in:aktif,nonaktif',
            'kategori_hasil' => 'nullable|string|in:normal,tidak_terkontrol,tinggi,berbahaya',
            'id_cgd'         => 'nullable|uuid',
            'tgl_start'      => 'nullable|date',
            'tgl_end'        => 'nullable|date',
            'pagenum'        => 'nullable|integer|min:0',
            'pagesize'       => 'nullable|integer|min:1|max:100',
        ];
    }
}
