<?php

namespace App\Http\Requests\PengaturanPengingat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pengaturan-pengingat.update');
    }

    public function rules(): array
    {
        return [
            'mo_aktif' => 'required|boolean',
            'mo_jumlah' => 'required|integer|min:1|max:20',
            'mo_interval_menit' => 'required|integer|min:1|max:180',
            'mo_pmo_mulai_ke' => 'required|integer|min:1|lte:mo_jumlah',
            'cgd_aktif' => 'required|boolean',
            'cgd_dibuat_aktif' => 'required|boolean',
            'cgd_jam_h1' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'mo_aktif.required' => 'Status aktif pengingat MO wajib diisi.',
            'mo_aktif.boolean' => 'Status aktif pengingat MO tidak valid.',
            'mo_jumlah.required' => 'Jumlah pengingat MO wajib diisi.',
            'mo_jumlah.integer' => 'Jumlah pengingat MO harus berupa angka.',
            'mo_jumlah.min' => 'Jumlah pengingat MO minimal 1.',
            'mo_jumlah.max' => 'Jumlah pengingat MO maksimal 20.',
            'mo_interval_menit.required' => 'Interval pengingat MO wajib diisi.',
            'mo_interval_menit.integer' => 'Interval pengingat MO harus berupa angka.',
            'mo_interval_menit.min' => 'Interval minimal 1 menit.',
            'mo_interval_menit.max' => 'Interval maksimal 180 menit.',
            'mo_pmo_mulai_ke.required' => 'Pengingat ke-berapa PMO mulai dilibatkan wajib diisi.',
            'mo_pmo_mulai_ke.integer' => 'Nilai PMO mulai dilibatkan harus berupa angka.',
            'mo_pmo_mulai_ke.min' => 'PMO mulai dilibatkan minimal pada pengingat ke-1.',
            'mo_pmo_mulai_ke.lte' => 'PMO mulai dilibatkan tidak boleh melebihi jumlah pengingat.',
            'cgd_aktif.required' => 'Status aktif pengingat CGD wajib diisi.',
            'cgd_aktif.boolean' => 'Status aktif pengingat CGD tidak valid.',
            'cgd_dibuat_aktif.required' => 'Status notifikasi saat dibuat wajib diisi.',
            'cgd_dibuat_aktif.boolean' => 'Status notifikasi saat dibuat tidak valid.',
            'cgd_jam_h1.required' => 'Jam pengingat H-1 wajib diisi.',
            'cgd_jam_h1.date_format' => 'Format jam H-1 harus HH:MM (contoh: 17:00).',
        ];
    }
}
