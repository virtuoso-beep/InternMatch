<?php

namespace App\Models;

use Database\Factories\RequirementTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'is_active'])]
class RequirementType extends Model
{
    /** @use HasFactory<RequirementTypeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ProgramTermRequirement, $this> */
    public function programTermRequirements(): HasMany
    {
        return $this->hasMany(ProgramTermRequirement::class);
    }
}
