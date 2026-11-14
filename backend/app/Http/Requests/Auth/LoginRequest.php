<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->hasSession();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['bail', 'required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! preg_match('/^[0-9]{6}$/', $value) && ! filter_var($value, FILTER_VALIDATE_EMAIL)) $fail('Enter a six-digit Student ID or a valid email address.');
            }],
            'password' => ['required', 'string', 'max:4096'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(bool $requireActiveRole = false): void
    {
        $key = hash('sha256', Str::lower($this->string('email')->trim()->toString()).'|'.$this->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($this));

            abort(429, 'Too many login attempts. Please try again shortly.', [
                'Retry-After' => (string) RateLimiter::availableIn($key),
            ]);
        }

         $identifier = trim((string) $this->input('email'));
        $credentials = ['password' => $this->input('password')];
        if (preg_match('/^[0-9]{6}$/', $identifier)) {
            $credentials['id'] = \App\Models\Student::where('student_number', $identifier)->value('user_id') ?? 0;
            $credentials['role'] = 'student';
        } else {
            $credentials['email'] = $identifier;
        }
        if (! Auth::guard('web')->attemptWhen(
            $credentials,
            fn (User $user): bool => $requireActiveRole
                ? $user->status === AccountStatus::Active && $user->role !== null
                : $user->status !== AccountStatus::Disabled,
            $this->boolean('remember'),
        )) {
            RateLimiter::hit($key, 60);
            Log::notice('auth.login_failed', ['ip' => $this->ip()]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials could not be authenticated.'],
            ]);
        }

        RateLimiter::clear($key);
        Log::info('auth.login', ['user_id' => Auth::guard('web')->id()]);
    }
}
