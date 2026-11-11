<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_days' => ['required', 'array', 'min:1'],
            'service_days.*' => ['integer', 'between:1,7'],
            'service_hour_start' => ['required', 'date_format:H:i'],
            'service_hour_end' => ['required', 'date_format:H:i', 'after:service_hour_start'],
            'max_loan_days' => ['required', 'integer', 'between:1,30'],
            'review_sla_workdays' => ['required', 'integer', 'between:1,10'],
            'pickup_grace_workdays' => ['required', 'integer', 'between:0,10'],
            'late_warning_threshold' => ['required', 'integer', 'between:0,20'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_label' => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_days.required' => 'Pilih minimal satu hari layanan.',
            'service_days.min' => 'Pilih minimal satu hari layanan.',
            'service_hour_start.required' => 'Jam buka wajib diisi.',
            'service_hour_end.required' => 'Jam tutup wajib diisi.',
            'service_hour_end.after' => 'Jam tutup harus setelah jam buka.',
            'max_loan_days.between' => 'Maksimal hari pinjam antara :min dan :max.',
            'review_sla_workdays.between' => 'Batas peninjauan antara :min dan :max hari kerja.',
            'pickup_grace_workdays.between' => 'Toleransi pengambilan antara :min dan :max hari kerja.',
            'late_warning_threshold.between' => 'Ambang peringatan antara :min dan :max (0 = nonaktif).',
            'academic_year.regex' => 'Tahun ajaran berformat 2026/2027.',
            'semester_label.required' => 'Label semester wajib diisi.',
        ];
    }
}
