<?php

namespace App\Http\Requests\JadwalCgd;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-cgd.create');
    }

    public function rules(): array
    {
        return [
            'tgl_jadwal_cgd' => 'required|date|after_or_equal:today',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_berakhir'   => 'required|date_format:H:i|after:jam_mulai',
            'puasa'          => 'required|string|in:Wajib,Tidak',
            'tempat'         => 'required|string|max:255',
            'catatan'        => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_jadwal_cgd.required'       => 'Tanggal pelaksanaan CGD wajib diisi.',
            'tgl_jadwal_cgd.after_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa lalu.',
            'jam_mulai.required'            => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format'         => 'Format jam mulai harus HH:MM (contoh: 07:00).',
            'jam_berakhir.required'         => 'Jam berakhir wajib diisi.',
            'jam_berakhir.date_format'      => 'Format jam berakhir harus HH:MM.',
            'jam_berakhir.after'            => 'Jam berakhir harus setelah jam mulai.',
            'puasa.required'                => 'Status puasa wajib dipilih.',
            'puasa.in'                      => 'Status puasa harus Wajib atau Tidak.',
            'tempat.required'               => 'Tempat pelaksanaan wajib diisi.',
        ];
    }
}
