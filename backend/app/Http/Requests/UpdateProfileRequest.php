<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageOwnProfile) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'nullable', 'string', 'max:40'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'notify_email' => ['sometimes', 'required', 'boolean'],
            'notify_digest' => ['sometimes', 'required', 'boolean'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
            'email' => ['prohibited'],
            'student_number' => ['prohibited'],
            'program_term_id' => ['prohibited'],
            'required_minutes' => ['prohibited'],
            'avatar_disk' => ['prohibited'],
            'avatar_path' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->exists('latitude') || $this->exists('longitude')) {
                if (! $this->exists('latitude') || ! $this->exists('longitude')
                    || (($this->input('latitude') === null) !== ($this->input('longitude') === null))) {
                    $validator->errors()->add('latitude', 'Provide both coordinates, or clear both together.');
                }
            }
        }];
    }
}
