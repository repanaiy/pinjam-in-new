<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower(trim((string) $this->input('username'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $creating = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'nis' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'nis')->ignore($id)],
            'kelas' => ['required', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s]{8,25}$/'],
            'is_active' => ['boolean'],
            'password' => [$creating ? 'required' : 'nullable', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah dipakai.',
            'username.regex' => 'Username hanya huruf kecil, angka, titik, minus, atau garis bawah.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.regex' => 'NIS hanya huruf, angka, titik, minus, atau garis bawah.',
            'kelas.required' => 'Kelas wajib diisi.',
            'phone.regex' => 'Nomor HP hanya angka, spasi, + atau -, minimal 8 digit.',
            'password.required' => 'Password awal wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
        ];
    }
}
