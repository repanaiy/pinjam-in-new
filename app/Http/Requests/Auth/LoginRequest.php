<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Pesan generik: tidak membocorkan apakah username, password, peran, atau status akun yang salah. */
    public const FAIL_TITLE = 'Password Salah';

    public const FAIL_MESSAGE = 'Periksa kembali password lalu coba lagi.';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_column(Role::cases(), 'value'))],
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'role.required' => 'Pilih peran terlebih dahulu.',
            'role.in' => 'Peran tidak valid.',
        ];
    }

    /**
     * Pemilih peran hanya UX: akun hanya lolos bila users.role sama dengan pilihan,
     * akun aktif, dan password benar. Kegagalan apa pun menghasilkan pesan yang sama.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ok = Auth::attempt([
            'username' => $this->string('username')->toString(),
            'password' => $this->string('password')->toString(),
            'role' => $this->string('role')->toString(),
            'is_active' => 1,
        ], $this->boolean('remember'));

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['auth' => self::FAIL_MESSAGE]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'throttle' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')->toString()).'|'.$this->ip());
    }
}
