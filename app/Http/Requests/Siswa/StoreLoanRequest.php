<?php

namespace App\Http\Requests\Siswa;

use App\Models\Item;
use App\Models\Loan;
use App\Services\ServiceCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    /** Pengajuan lama (ditolak, milik siswa ini) yang sedang diajukan ulang, bila ada. */
    public function resubmitSource(): ?Loan
    {
        $id = $this->input('resubmitted_from_id');

        if (! $id) {
            return null;
        }

        return Loan::query()
            ->whereKey($id)
            ->where('user_id', $this->user()->id)
            ->where('status', 'rejected')
            ->first();
    }

    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');
        $cardRule = $this->resubmitSource() ? 'nullable' : 'required';

        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$item->max_qty_per_loan],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            'student_card' => [$cardRule, 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'agreement_accepted' => ['accepted'],
            'resubmitted_from_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Jumlah unit wajib diisi.',
            'quantity.max' => 'Jumlah melebihi batas :max unit per peminjaman.',
            'quantity.min' => 'Jumlah minimal 1 unit.',
            'start_date.required' => 'Pilih tanggal pinjam.',
            'start_date.date_format' => 'Format tanggal pinjam tidak valid.',
            'end_date.required' => 'Pilih tanggal kembali.',
            'end_date.date_format' => 'Format tanggal kembali tidak valid.',
            'end_date.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'purpose.required' => 'Keperluan peminjaman wajib diisi.',
            'purpose.min' => 'Jelaskan keperluan minimal :min karakter.',
            'purpose.max' => 'Keperluan maksimal :max karakter.',
            'student_card.required' => 'Kartu pelajar wajib diunggah.',
            'student_card.mimes' => 'Kartu pelajar harus berformat JPG, PNG, atau PDF.',
            'student_card.mimetypes' => 'Kartu pelajar harus berformat JPG, PNG, atau PDF.',
            'student_card.max' => 'Ukuran kartu pelajar maksimal 5 MB.',
            'attachment.mimes' => 'Dokumen pendukung harus berformat JPG, PNG, atau PDF.',
            'attachment.mimetypes' => 'Dokumen pendukung harus berformat JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran dokumen pendukung maksimal 5 MB.',
            'agreement_accepted.accepted' => 'Anda harus menyetujui pernyataan tanggung jawab.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->hasAny(['start_date', 'end_date'])) {
                return;
            }

            /** @var Item $item */
            $item = $this->route('item');
            $start = Carbon::parse($this->input('start_date'))->startOfDay();
            $end = Carbon::parse($this->input('end_date'))->startOfDay();
            $calendar = app(ServiceCalendar::class);

            if ($start->lt(today())) {
                $v->errors()->add('start_date', 'Tanggal pinjam tidak boleh di masa lalu.');
            }

            if ($start->diffInDays($end) + 1 > $item->max_loan_days) {
                $v->errors()->add('end_date', "Durasi peminjaman maksimal {$item->max_loan_days} hari.");
            }

            if (! $calendar->isWorkday($start)) {
                $v->errors()->add('start_date', 'Tanggal pengambilan tidak boleh akhir pekan atau hari libur.');
            }

            if (! $calendar->isWorkday($end)) {
                $v->errors()->add('end_date', 'Tanggal pengembalian tidak boleh akhir pekan atau hari libur.');
            }

            if ($this->filled('resubmitted_from_id') && ! $this->resubmitSource()) {
                $v->errors()->add('resubmitted_from_id', 'Pengajuan lama tidak dapat diajukan ulang.');
            }
        }];
    }
}
