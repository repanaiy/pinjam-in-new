<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:2048']];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih berkas CSV atau Excel.',
            'file.mimes' => 'Berkas harus berformat CSV, XLSX, atau XLS.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
        ];
    }
}
