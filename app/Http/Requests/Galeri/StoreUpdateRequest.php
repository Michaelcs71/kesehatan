<?php

namespace App\Http\Requests\Galeri;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return $this->user()->can('konten-galery.'.($isUpdate ? 'edit' : 'create'));
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'judul' => 'required|string|max:200',
            'deskripsi' => 'nullable|string|max:500',
            // Gambar wajib saat create, opsional saat update (boleh ganti)
            'gambar' => ($isUpdate ? 'nullable' : 'required').'|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul/keterangan foto wajib diisi.',
            'gambar.required' => 'Foto wajib diunggah.',
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
