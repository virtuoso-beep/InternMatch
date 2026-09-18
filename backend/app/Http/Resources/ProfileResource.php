<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value,
            'contact_number' => $this->profile?->contact_number,
            'address' => $this->profile?->address,
            'bio' => $this->profile?->bio,
            'latitude' => $this->profile?->latitude,
            'longitude' => $this->profile?->longitude,
            'notify_email' => $this->profile?->notify_email ?? true,
            'notify_digest' => $this->profile?->notify_digest ?? false,
            'student_number' => $this->student?->student_number,
            'enrollments' => $this->student?->enrollments->map(fn ($enrollment): array => [
                'id' => $enrollment->id,
                'program' => $enrollment->programTerm->program->name,
                'term' => $enrollment->programTerm->academicTerm->name,
                'year_level' => $enrollment->year_level,
                'status' => $enrollment->status->value,
            ])->values()->all() ?? [],
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Cache-Control', 'no-store');
    }
}
