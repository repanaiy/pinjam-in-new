<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $loan = $this->route('loan');

        return [
            'card_verified' => ['accepted'],
            'unit_ids' => ['required', 'array', 'size:'.$loan->quantity],
            'unit_ids.*' => ['integer', 'distinct'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'media' => ['required', 'array', 'min:1', 'max:6'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,mp4', 'mimetypes:image/jpeg,image/png,video/mp4', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        $qty = $this->route('loan')->quantity;

        return [
            'card_verified.accepted' => 'Konfirmasi bahwa kartu pelajar asli sudah diverifikasi.',
            'unit_ids.required' => "Pilih {$qty} unit fisik.",
            'unit_ids.size' => "Pilih tepat {$qty} unit fisik.",
            'media.required' => 'Unggah minimal satu foto/video kondisi awal.',
            'media.min' => 'Unggah minimal satu foto/video kondisi awal.',
            'media.max' => 'Maksimal :max berkas bukti.',
            'media.*.mimes' => 'Bukti harus berformat JPG, PNG, atau MP4.',
            'media.*.mimetypes' => 'Bukti harus berformat JPG, PNG, atau MP4.',
            'media.*.max' => 'Ukuran tiap bukti maksimal 20 MB.',
            'notes.max' => 'Catatan maksimal :max karakter.',
        ];
    }
}
