<?php

namespace App\Http\Requests\JadwalCgd;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jadwal-cgd.edit');
    }

    public function rules(): array
    {
        return [
            'tgl_jadwal_cgd' => 'required|date',  // tidak after_or_equal:today (boleh edit past event)
            'jam_mulai' => 'required|date_format:H:i',
            'jam_berakhir' => 'required|date_format:H:i|after:jam_mulai',
            'puasa' => 'required|string|in:Wajib,Tidak',
            'tempat' => 'required|string|max:255',
            'catatan' => 'nullable|string|max:1000',
            'status' => 'required|string|in:aktif,nonaktif,selesai',
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_jadwal_cgd.required' => 'Tanggal pelaksanaan CGD wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jam_berakhir.required' => 'Jam berakhir wajib diisi.',
            'jam_berakhir.date_format' => 'Format jam berakhir harus HH:MM.',
            'jam_berakhir.after' => 'Jam berakhir harus setelah jam mulai.',
            'puasa.required' => 'Status puasa wajib dipilih.',
            'tempat.required' => 'Tempat pelaksanaan wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
        ];
    }
}
