<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedRoles = collect(UserRole::selfRegisterable())->map->value->toArray();

        return [
            // Akun
            'name'            => ['required', 'string', 'max:255', 'unique:users,name'],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
            'role'            => ['required', 'string', 'in:' . implode(',', $allowedRoles)],
            'whatsapp_number' => ['required', 'string', 'max:20', 'unique:users,whatsapp_number'],

            // Biodata (wajib untuk pasien & PMO - keduanya tetap wajib karena selfRegisterable cuma pasien/pmo)
            'nik'             => ['required', 'string', 'size:16', 'unique:user_biodatas,nik'],
            'jenis_kelamin'   => ['required', 'string', 'in:L,P'],
            'tempat_lahir'    => ['required', 'string', 'max:50'],
            'tanggal_lahir'   => ['required', 'date', 'before_or_equal:today'],

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
            'name.required'            => 'Nama lengkap wajib diisi.',
            'name.unique'              => 'Nama ini sudah terdaftar. Gunakan nama yang berbeda atau hubungi admin.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.unique'   => 'Nomor WhatsApp ini sudah terdaftar.',
            'password.required'        => 'Password wajib diisi.',
            'role.in'                  => 'Role yang dipilih tidak valid.',
            'nik.required'             => 'NIK wajib diisi.',
            'nik.size'                 => 'NIK harus 16 digit.',
            'nik.unique'               => 'NIK ini sudah terdaftar.',
            'jenis_kelamin.required'   => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in'         => 'Jenis kelamin tidak valid (L/P).',
            'tempat_lahir.required'    => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required'   => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'no_kk.size'               => 'No KK harus 16 digit.',
        ];
    }

    public function prepareForValidation(): void
    {
        // Normalisasi WA: hilangkan spasi/dash/+, convert 62 ke 0
        if ($this->has('whatsapp_number')) {
            $wa = preg_replace('/[\s\+\-\(\)]/', '', $this->whatsapp_number);
            if (str_starts_with($wa, '62')) {
                $wa = '0' . substr($wa, 2);
            }
            $this->merge(['whatsapp_number' => $wa]);
        }
    }
}
