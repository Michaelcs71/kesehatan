<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return $this->user()->can('master-user.' . ($isUpdate ? 'edit' : 'create'));
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $userId   = $this->route('id');

        // Tentukan apakah field biodata wajib: hanya kalau role = pasien/pmo
        $role = $this->input('role');
        $requiresBiodata = in_array($role, ['pasien', 'pmo']);

        // Biodata id untuk ignore unique pada update
        $biodataId = null;
        if ($isUpdate && $userId) {
            $biodataId = \App\Models\UserBiodata::where('user_id', $userId)->value('id');
        }

        return [
            'name'            => [
                'required',
                'string',
                'max:200',
                Rule::unique('users', 'name')->ignore($userId),
            ],
            'whatsapp_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'whatsapp_number')->ignore($userId),
            ],
            'role'            => 'required|string|in:pengunjung,pasien,pmo,admin,superadmin',
            'password'        => $isUpdate
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'is_active'       => 'nullable|boolean',

            // Biodata - wajib hanya kalau role pasien/pmo
            'nik' => [
                $requiresBiodata ? 'required' : 'nullable',
                'string',
                'size:16',
                Rule::unique('user_biodatas', 'nik')->ignore($biodataId)->whereNull('deleted_at'),
            ],
            'jenis_kelamin'   => [$requiresBiodata ? 'required' : 'nullable', 'string', 'in:L,P'],
            'tempat_lahir'    => [$requiresBiodata ? 'required' : 'nullable', 'string', 'max:50'],
            'tanggal_lahir'   => [$requiresBiodata ? 'required' : 'nullable', 'date', 'before_or_equal:today'],

            // Biodata opsional
            'no_kk'             => ['nullable', 'string', 'size:16'],
            'alamat_jalan'      => ['nullable', 'string', 'max:255'],
            'alamat_rt'         => ['nullable', 'string', 'max:5'],
            'alamat_rw'         => ['nullable', 'string', 'max:5'],
            'alamat_dusun'      => ['nullable', 'string', 'max:100'],
            'alamat_desa'       => ['nullable', 'string', 'max:100'],
            'alamat_kecamatan'  => ['nullable', 'string', 'max:100'],
            'alamat_kabupaten'  => ['nullable', 'string', 'max:100'],
            'alamat_provinsi'   => ['nullable', 'string', 'max:100'],
            'alamat_kodepos'    => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'Nama wajib diisi.',
            'name.unique'              => 'Nama ini sudah digunakan oleh user lain.',
            'whatsapp_number.required' => 'No. WhatsApp wajib diisi.',
            'whatsapp_number.unique'   => 'No. WhatsApp ini sudah digunakan oleh user lain.',
            'role.required'            => 'Role wajib dipilih.',
            'password.required'        => 'Password wajib diisi.',
            'password.min'             => 'Password minimal 8 karakter.',
            'password.confirmed'       => 'Konfirmasi password tidak cocok.',
            'nik.required'             => 'NIK wajib diisi untuk pasien/PMO.',
            'nik.size'                 => 'NIK harus 16 digit.',
            'nik.unique'               => 'NIK ini sudah terdaftar.',
            'jenis_kelamin.required'   => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in'         => 'Jenis kelamin tidak valid.',
            'tempat_lahir.required'    => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required'   => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'no_kk.size'               => 'No KK harus 16 digit.',
        ];
    }

    /**
     * Normalisasi WhatsApp & default is_active
     */
    public function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if ($this->has('whatsapp_number')) {
            $wa = preg_replace('/[\s\+\-\(\)]/', '', $this->whatsapp_number);
            if (str_starts_with($wa, '62')) {
                $wa = '0' . substr($wa, 2);
            }
            $this->merge(['whatsapp_number' => $wa]);
        }
    }

    /**
     * Pisahkan data jadi userData dan biodataData (untuk Service/Repository)
     */
    public function userData(): array
    {
        return $this->only(['name', 'role', 'whatsapp_number', 'password', 'is_active']);
    }

    public function biodataData(): array
    {
        return $this->only([
            'nik',
            'no_kk',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'alamat_jalan',
            'alamat_rt',
            'alamat_rw',
            'alamat_dusun',
            'alamat_desa',
            'alamat_kecamatan',
            'alamat_kabupaten',
            'alamat_provinsi',
            'alamat_kodepos',
        ]);
    }
}
