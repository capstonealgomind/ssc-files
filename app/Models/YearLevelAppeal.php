<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearLevelAppeal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'school_year_start',
        'reason',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'school_year_start' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'submitted_at' => $this->created_at?->format('M d, Y g:i A'),
            'processed_at' => $this->processed_at?->format('M d, Y g:i A'),
            'processed_by' => $this->processor?->name,
        ];
    }
}