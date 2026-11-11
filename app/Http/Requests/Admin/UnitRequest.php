<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /** Halaman ubah alat memuat banyak form; galat unit dipisah agar tidak bentrok dengan field alat (mis. status). */
    protected $errorBag = 'unit';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('unit_code')) {
            $this->merge(['unit_code' => strtoupper(trim((string) $this->input('unit_code')))]);
        }
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'unit_code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('item_units', 'unit_code')->ignore($unit?->id)],
            'status' => ['required', 'in:available,maintenance,lost,inactive'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_code.required' => 'Kode unit wajib diisi.',
            'unit_code.unique' => 'Kode unit sudah dipakai (termasuk unit yang sudah dihapus).',
            'unit_code.regex' => 'Kode unit hanya boleh huruf, angka, titik, minus, atau garis bawah.',
            'status.in' => 'Status unit tidak valid.',
            'notes.max' => 'Catatan maksimal :max karakter.',
        ];
    }
}
