<?php

namespace App\Http\Requests\PasienPmo;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pasien-pmo.index');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'is_active' => 'nullable|string',
            'jenis_pmo' => 'nullable|string|in:Keluarga,Kader',
            'status_diabetes' => 'nullable|string|in:Rendah,Sedang,Tinggi',
            'pmo_user_id' => 'nullable|uuid',
            'pagenum' => 'nullable|integer|min:0',
            'pagesize' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|max:50',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
