<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_CLOSED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Draft',
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_ACTIVE    => 'Active',
        self::STATUS_CLOSED    => 'Closed',
    ];

    protected $fillable = [
        'title',
        'description',
        'event_starts_at',
        'event_ends_at',
        'voting_starts_at',
        'voting_ends_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_starts_at'  => 'datetime',
            'event_ends_at'    => 'datetime',
            'voting_starts_at' => 'datetime',
            'voting_ends_at'   => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function votingPhase(): string
    {
        if (!$this->voting_starts_at || !$this->voting_ends_at) {
            return 'closed';
        }

        $now = now();

        if ($now->lt($this->voting_starts_at)) {
            return 'upcoming';
        }

        if ($now->gt($this->voting_ends_at)) {
            return 'closed';
        }

        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CLOSED], true)) {
            return 'closed';
        }

        return 'open';
    }

    public function isVotingOpen(): bool
    {
        return $this->votingPhase() === 'open';
    }

    public function scopeEligibleForVoters($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_SCHEDULED]);
    }

    public function scopeVotingOpen($query)
    {
        return $query
            ->eligibleForVoters()
            ->where('voting_starts_at', '<=', now())
            ->where('voting_ends_at', '>=', now());
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }
}
