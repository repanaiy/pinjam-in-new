<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KerusakanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:in_repair,resolved,written_off'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
            'fine_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status baru.',
            'status.in' => 'Status tidak valid.',
            'resolution_note.max' => 'Catatan maksimal :max karakter.',
        ];
    }
}
