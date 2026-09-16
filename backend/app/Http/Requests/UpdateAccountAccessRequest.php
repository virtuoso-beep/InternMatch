<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('updateAccess', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required_without:status', Rule::enum(Role::class)],
            'status' => ['required_without:role', Rule::enum(AccountStatus::class)],
        ];
    }
}
