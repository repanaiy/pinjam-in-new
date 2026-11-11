<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s]{8,25}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, + atau -, minimal 8 digit.',
            'avatar.image' => 'Avatar harus berupa gambar.',
            'avatar.mimes' => 'Avatar harus berformat JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran avatar maksimal 2 MB.',
        ];
    }
}
