<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     * Field 'login' bisa berupa nama lengkap atau no WhatsApp.
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Nama lengkap atau No. WhatsApp wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Authenticate dengan auto-detect:
     * - Kalau input adalah angka semua (dengan opsional + di depan): cari di whatsapp_number
     * - Kalau ada huruf: cari di name
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginField = $this->resolveLoginField();
        $loginValue = $this->normalizeLoginValue($loginField);

        $credentials = [
            $loginField => $loginValue,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Cek apakah akun aktif
        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Auto-detect field yang dipakai login.
     * Return 'whatsapp_number' kalau input angka semua (after normalisasi), else 'name'.
     */
    public function resolveLoginField(): string
    {
        $input = trim((string) $this->input('login'));

        // Hilangkan karakter +, -, spasi, () untuk cek apakah ini nomor telepon
        $stripped = preg_replace('/[\s\+\-\(\)]/', '', $input);

        // Kalau setelah strip masih ada karakter dan semua digit, anggap WhatsApp
        if ($stripped !== '' && ctype_digit($stripped)) {
            return 'whatsapp_number';
        }

        return 'name';
    }

    /**
     * Normalisasi value sesuai field.
     * - whatsapp: hilangkan spasi/dash/+, normalisasi awalan 08/62
     * - name: trim aja
     */
    public function normalizeLoginValue(string $field): string
    {
        $input = trim((string) $this->input('login'));

        if ($field === 'whatsapp_number') {
            // Hilangkan spasi, dash, plus, parentheses
            $clean = preg_replace('/[\s\+\-\(\)]/', '', $input);

            // Normalisasi: 628xxx -> 08xxx (atau biarkan sesuai format DB)
            // Kita normalisasi ke format 08xxx (paling umum di Indonesia)
            if (str_starts_with($clean, '62')) {
                $clean = '0'.substr($clean, 2);
            }

            return $clean;
        }

        return $input;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
