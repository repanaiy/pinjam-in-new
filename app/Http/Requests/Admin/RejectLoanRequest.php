<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reject_reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function messages(): array
    {
        return [
            'reject_reason.required' => 'Alasan penolakan wajib diisi.',
            'reject_reason.min' => 'Alasan penolakan minimal :min karakter.',
            'reject_reason.max' => 'Alasan penolakan maksimal :max karakter.',
        ];
    }
}
