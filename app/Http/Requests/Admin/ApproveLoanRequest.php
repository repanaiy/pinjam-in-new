<?php

namespace App\Http\Requests\Admin;

use App\Models\Loan;
use App\Models\Setting;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ApproveLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_date' => ['required', 'date_format:Y-m-d'],
            'pickup_time_start' => ['required', 'date_format:H:i'],
            'pickup_time_end' => ['required', 'date_format:H:i', 'after:pickup_time_start'],
            'pickup_location' => ['required', 'string', 'max:150'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_date.required' => 'Tanggal pengambilan wajib diisi.',
            'pickup_date.date_format' => 'Format tanggal pengambilan tidak valid.',
            'pickup_time_start.required' => 'Jam mulai wajib diisi.',
            'pickup_time_start.date_format' => 'Format jam mulai tidak valid.',
            'pickup_time_end.required' => 'Jam selesai wajib diisi.',
            'pickup_time_end.date_format' => 'Format jam selesai tidak valid.',
            'pickup_time_end.after' => 'Jam selesai harus setelah jam mulai.',
            'pickup_location.required' => 'Lokasi pengambilan wajib diisi.',
            'admin_note.max' => 'Catatan maksimal :max karakter.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            /** @var Loan $loan */
            $loan = $this->route('loan');

            if (! $v->errors()->has('pickup_date')) {
                $date = Carbon::parse($this->input('pickup_date'))->startOfDay();

                if ($date->lt(today())) {
                    $v->errors()->add('pickup_date', 'Tanggal pengambilan tidak boleh di masa lalu.');
                } elseif ($date->gt($loan->end_date)) {
                    $v->errors()->add('pickup_date', 'Tanggal pengambilan tidak boleh melewati tanggal kembali ('.$loan->end_date->translatedFormat('j M Y').').');
                } elseif (! app(ServiceCalendar::class)->isWorkday($date)) {
                    $v->errors()->add('pickup_date', 'Tanggal pengambilan tidak boleh akhir pekan atau hari libur.');
                }
            }

            $open = (string) Setting::get('service_hour_start', '07:00');
            $close = (string) Setting::get('service_hour_end', '15:00');

            foreach (['pickup_time_start', 'pickup_time_end'] as $field) {
                if (! $v->errors()->has($field)) {
                    $t = $this->input($field);
                    if ($t < $open || $t > $close) {
                        $v->errors()->add($field, "Jam harus dalam jam layanan ({$open}–{$close} WIB).");
                    }
                }
            }
        }];
    }
}
