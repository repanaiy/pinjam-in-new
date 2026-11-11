<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*' => ['required', 'in:baik,rusak,hilang'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'media' => ['nullable', 'array', 'max:6'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,mp4', 'mimetypes:image/jpeg,image/png,video/mp4', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'conditions.required' => 'Isi kondisi untuk setiap unit yang dipinjam.',
            'conditions.*.in' => 'Kondisi unit harus Baik, Rusak, atau Hilang.',
            'conditions.*.required' => 'Kondisi unit wajib diisi.',
            'notes.max' => 'Catatan maksimal :max karakter.',
            'media.max' => 'Maksimal :max berkas bukti.',
            'media.*.mimes' => 'Bukti harus berformat JPG, PNG, atau MP4.',
            'media.*.mimetypes' => 'Bukti harus berformat JPG, PNG, atau MP4.',
            'media.*.max' => 'Ukuran tiap bukti maksimal 20 MB.',
        ];
    }

    /** Catatan wajib bila ada unit rusak/hilang; bukti wajib bila ada unit rusak. */
    public function after(): array
    {
        return [function (Validator $v) {
            $conditions = array_values((array) $this->input('conditions', []));

            if ($v->errors()->has('conditions.*') || $v->errors()->has('conditions')) {
                return;
            }

            if (array_diff($conditions, ['baik']) !== [] && blank($this->input('notes'))) {
                $v->errors()->add('notes', 'Catatan inspeksi wajib diisi bila ada unit rusak atau hilang.');
            }

            if (in_array('rusak', $conditions, true) && ! $this->hasFile('media')) {
                $v->errors()->add('media', 'Unggah bukti foto/video untuk unit yang rusak.');
            }
        }];
    }
}
