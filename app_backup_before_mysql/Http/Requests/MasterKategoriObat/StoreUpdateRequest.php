<?php

namespace App\Http\Requests\MasterKategoriObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return $this->user()->can('master-kategori-obat.' . ($isUpdate ? 'edit' : 'create'));
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nama'      => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_kategori_obats', 'nama')
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ],
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique'   => 'Nama kategori sudah digunakan.',
        ];
    }

    public function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}