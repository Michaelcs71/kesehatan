<?php

namespace App\Http\Requests\MasterObat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifikasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master-obat.verify');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'catatan_verifikasi' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn () => $this->input('status') === 'rejected'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status verifikasi wajib dipilih.',
            'catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
