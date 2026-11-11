<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code_prefix')) {
            $this->merge(['code_prefix' => strtoupper(trim((string) $this->input('code_prefix')))]);
        }
        $this->merge(['sort_order' => $this->input('sort_order', 0) === '' ? 0 : $this->input('sort_order', 0)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($this->route('category')?->id)],
            'code_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'sort_order' => ['required', 'integer', 'between:0,999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah dipakai.',
            'code_prefix.regex' => 'Awalan kode hanya huruf dan angka.',
            'sort_order.between' => 'Urutan antara :min dan :max.',
        ];
    }
}
