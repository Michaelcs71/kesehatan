<?php

namespace App\Http\Requests\Galeri;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('konten-galery.index');
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'is_published' => 'nullable|string',
            'pagenum' => 'nullable|integer|min:0',
            'pagesize' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|max:50',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
