<?php

namespace App\Http\Requests\MasterSatuanObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return $this->user()->can('master-satuan-obat.' . ($isUpdate ? 'edit' : 'create'));
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nama'      => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_satuan_obats', 'nama')
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ],
            'singkatan' => ['nullable', 'string', 'max:20'],
            'deskripsi' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama satuan wajib diisi.',
            'nama.unique'   => 'Nama satuan sudah digunakan.',
        ];
    }

    public function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
