<?php

namespace App\Http\Requests\PasienPmo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pasien-pmo.create');
    }

    public function rules(): array
    {
        return [
            'pmo_user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('role', 'pmo')->where('is_active', true),
            ],

            // Array berisi { pasien_id, status_diabetes } per row
            'pasiens' => 'required|array|min:1',
            'pasiens.*.pasien_id' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('users', 'id')->where('role', 'pasien')->where('is_active', true),
            ],
            'pasiens.*.status_diabetes' => 'required|string|in:Rendah,Sedang,Tinggi',

            // Common fields untuk semua mapping
            'jenis_pmo' => 'required|string|in:Keluarga,Kader',
            'tanggal_regis' => 'required|date|before_or_equal:today',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'pmo_user_id.required' => 'PMO wajib dipilih.',
            'pmo_user_id.exists' => 'PMO yang dipilih tidak valid atau tidak aktif.',
            'pasiens.required' => 'Minimal pilih 1 pasien.',
            'pasiens.min' => 'Minimal pilih 1 pasien.',
            'pasiens.*.pasien_id.required' => 'Pasien wajib dipilih.',
            'pasiens.*.pasien_id.exists' => 'Salah satu pasien yang dipilih tidak valid atau tidak aktif.',
            'pasiens.*.pasien_id.distinct' => 'Pasien tidak boleh duplikat.',
            'pasiens.*.status_diabetes.required' => 'Status diabetes wajib dipilih untuk setiap pasien.',
            'pasiens.*.status_diabetes.in' => 'Status diabetes harus Rendah, Sedang, atau Tinggi.',
            'jenis_pmo.required' => 'Jenis PMO wajib dipilih.',
            'jenis_pmo.in' => 'Jenis PMO harus Keluarga atau Kader.',
            'tanggal_regis.required' => 'Tanggal registrasi wajib diisi.',
            'tanggal_regis.before_or_equal' => 'Tanggal registrasi tidak boleh di masa depan.',
        ];
    }
}
