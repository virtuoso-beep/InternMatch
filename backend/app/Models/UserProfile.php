<?php

namespace App\Models;

use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'contact_number', 'address', 'latitude', 'longitude', 'bio', 'avatar_disk', 'avatar_path', 'notify_email', 'notify_digest'])]
class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'notify_email' => 'boolean',
            'notify_digest' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
