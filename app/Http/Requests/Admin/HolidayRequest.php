<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal libur wajib diisi.',
            'date.date_format' => 'Format tanggal tidak valid.',
            'date.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
            'name.required' => 'Nama hari libur wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
        ];
    }
}
