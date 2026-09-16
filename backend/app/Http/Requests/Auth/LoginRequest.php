<?php

namespace App\Http\Requests\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
        ];
    }

    public function authenticate(): void
    {
        $key = hash('sha256', Str::lower($this->string('email')->trim()->toString()).'|'.$this->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($this));

            abort(429, 'Too many login attempts. Please try again shortly.', [
                'Retry-After' => (string) RateLimiter::availableIn($key),
            ]);
        }

        if (! Auth::guard('web')->attemptWhen(
            $this->safe()->only(['email', 'password']),
            fn (User $user): bool => $user->status !== AccountStatus::Disabled,
        )) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials could not be authenticated.'],
            ]);
        }

        RateLimiter::clear($key);
    }
}
