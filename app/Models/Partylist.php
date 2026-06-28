<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partylist extends Model
{
    protected $fillable = [
        'name',
        'acronym',
        'description',
    ];

    protected function acronym(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null && $value !== '' ? strtoupper(trim($value)) : null,
        );
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
