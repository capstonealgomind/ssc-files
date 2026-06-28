<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'election_id',
        'name',
        'position_id',
        'department_id',
        'partylist_id',
        'platform',
        'photo_path',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function partylist(): BelongsTo
    {
        return $this->belongsTo(Partylist::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
