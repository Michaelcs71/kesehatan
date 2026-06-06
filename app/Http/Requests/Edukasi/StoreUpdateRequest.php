<?php

namespace App\Http\Requests\Edukasi;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return $this->user()->can('konten-edukasi.'.($isUpdate ? 'edit' : 'create'));
    }

    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:200',
            'kategori' => 'nullable|string|max:100',
            'ringkasan' => 'nullable|string|max:500',
            'konten' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul artikel wajib diisi.',
            'konten.required' => 'Isi artikel wajib diisi.',
            'gambar.image' => 'Berkas harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
