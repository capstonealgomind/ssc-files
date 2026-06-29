<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED   = 'closed';

    public const CATEGORIES = [
        'registration' => 'Registration',
        'voting'       => 'Voting',
        'account'      => 'Account',
        'technical'    => 'Technical',
        'other'        => 'Other',
    ];

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'approved_by',
        'subject',
        'category',
        'status',
        'approved_at',
        'closed_at',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at'     => 'datetime',
            'closed_at'       => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function isChatEnabled(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public static function generateTicketNumber(): string
    {
        $year = now()->format('Y');
        $count = static::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('TKT-%s-%05d', $year, $count);
    }
}
