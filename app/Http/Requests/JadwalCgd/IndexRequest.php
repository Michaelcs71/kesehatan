<?php

namespace App\Http\Requests\JadwalCgd;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-cgd.index');
    }

    public function rules(): array
    {
        return [
            'search'      => 'nullable|string|max:255',
            'status'      => 'nullable|string|in:aktif,nonaktif,selesai',
            'time_filter' => 'nullable|string|in:upcoming,past,today',
            'pagenum'     => 'nullable|integer|min:0',
            'pagesize'    => 'nullable|integer|min:1|max:100',
        ];
    }
}
