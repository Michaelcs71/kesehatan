<?php

namespace App\Http\Requests\PasienPmo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pasien-pmo.edit');
    }

    public function rules(): array
    {
        return [
            'id_user' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('role', 'pasien'),
            ],
            'pmo_user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('role', 'pmo'),
            ],
            'jenis_pmo' => 'required|string|in:Keluarga,Kader',
            'tanggal_regis' => 'required|date|before_or_equal:today',
            'status_diabetes' => 'required|string|in:Rendah,Sedang,Tinggi',
            'is_active' => 'nullable|boolean',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_user.required' => 'Pasien wajib dipilih.',
            'id_user.exists' => 'Pasien yang dipilih tidak valid.',
            'pmo_user_id.required' => 'PMO wajib dipilih.',
            'pmo_user_id.exists' => 'PMO yang dipilih tidak valid.',
            'jenis_pmo.required' => 'Jenis PMO wajib dipilih.',
            'tanggal_regis.required' => 'Tanggal registrasi wajib diisi.',
            'tanggal_regis.before_or_equal' => 'Tanggal registrasi tidak boleh di masa depan.',
            'status_diabetes.required' => 'Status diabetes wajib dipilih.',
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
