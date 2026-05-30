<?php

namespace App\Http\Requests\MasterObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $perm = $isUpdate ? 'master-obat.edit' : 'master-obat.create';
        return $this->user()->can($perm);
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nama'           => ['required', 'string', 'max:200'],
            'kategori_id'    => [
                'required',
                'uuid',
                Rule::exists('master_kategori_obats', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'dosis_default'  => ['required', 'string', 'max:50'],
            'satuan_id' => [
                'required',
                'uuid',
                Rule::exists('master_satuan_obats', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'deskripsi'      => ['nullable', 'string', 'max:2000'],
            'aturan_minum'   => ['nullable', 'string', 'max:1000'],
            'efek_samping'   => ['nullable', 'string', 'max:2000'],
            'kontraindikasi' => ['nullable', 'string', 'max:2000'],
            'foto'           => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'           => 'Nama obat wajib diisi.',
            'kategori_id.required'    => 'Kategori obat wajib dipilih.',
            'kategori_id.uuid'        => 'Kategori obat tidak valid.',
            'kategori_id.exists'      => 'Kategori yang dipilih tidak tersedia atau tidak aktif.',
            'dosis_default.required'  => 'Dosis default wajib diisi (e.g. 500mg).',
            'satuan_id.required' => 'Satuan obat wajib dipilih.',
            'satuan_id.uuid'     => 'Satuan obat tidak valid.',
            'satuan_id.exists'   => 'Satuan yang dipilih tidak tersedia atau tidak aktif.',
            'foto.required'           => 'Foto obat wajib di-upload sebagai bukti.',
            'foto.image'              => 'File harus berupa gambar.',
            'foto.mimes'              => 'Format foto: jpg, jpeg, png, atau webp.',
            'foto.max'                => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
