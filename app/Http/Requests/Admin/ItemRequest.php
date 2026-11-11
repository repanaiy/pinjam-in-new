<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        $item = $this->route('item');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            // Unik termasuk alat yang sudah dihapus (soft delete) agar riwayat tidak ambigu.
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('items', 'code')->ignore($item?->id)],
            'name' => ['required', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*.key' => ['nullable', 'string', 'max:100'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
            'package_contents' => ['nullable', 'string', 'max:2000'],
            'checklist_text' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:150'],
            'max_loan_days' => ['required', 'integer', 'between:1,30'],
            'max_qty_per_loan' => ['required', 'integer', 'between:1,20'],
            'min_stock' => ['required', 'integer', 'between:0,100'],
            'status' => ['required', 'in:active,inactive'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'video_title' => ['nullable', 'string', 'max:150'],
            'video_duration' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'initial_units' => ['nullable', 'integer', 'between:0,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori.',
            'category_id.exists' => 'Kategori tidak valid.',
            'code.required' => 'Kode alat wajib diisi.',
            'code.unique' => 'Kode alat sudah dipakai (termasuk alat yang sudah dihapus).',
            'code.regex' => 'Kode hanya boleh huruf, angka, titik, minus, atau garis bawah.',
            'code.max' => 'Kode maksimal :max karakter.',
            'name.required' => 'Nama alat wajib diisi.',
            'max_loan_days.between' => 'Maksimal hari pinjam antara :min dan :max.',
            'max_qty_per_loan.between' => 'Maksimal unit per peminjaman antara :min dan :max.',
            'min_stock.between' => 'Stok minimum antara :min dan :max.',
            'video_url.url' => 'Tautan video tidak valid.',
            'video_duration.regex' => 'Durasi video berformat mm:ss, mis. 04:30.',
            'photos.max' => 'Maksimal :max foto per unggahan.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 3 MB.',
            'initial_units.between' => 'Jumlah unit awal antara :min dan :max.',
        ];
    }

    /** Data siap simpan ke tabel items (spesifikasi & checklist diubah menjadi JSON). */
    public function itemData(): array
    {
        $v = $this->validated();

        $specs = collect($v['specs'] ?? [])
            ->filter(fn ($row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn ($row) => [trim($row['key']) => trim((string) ($row['value'] ?? ''))])
            ->all();

        $checklist = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($v['checklist_text'] ?? ''))),
            fn ($line) => $line !== ''
        ));

        return array_merge(Arr::only($v, [
            'category_id', 'code', 'name', 'summary', 'description', 'package_contents', 'location',
            'max_loan_days', 'max_qty_per_loan', 'min_stock', 'status', 'video_url', 'video_title', 'video_duration',
        ]), [
            'specs' => $specs ?: null,
            'checklist_template' => $checklist ?: null,
        ]);
    }
}
