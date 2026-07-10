<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReactivationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'voter_id_number',
        'full_name',
        'year_stopped',
        'reason',
        'reactivation_number',
        'status',
        'duration_years_added',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_years_added' => 'integer',
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

    public static function generateNumber(): string
    {
        $year = now()->year;
        $last = self::query()
            ->where('reactivation_number', 'like', "RACT-{$year}-%")
            ->orderByDesc('reactivation_number')
            ->value('reactivation_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return 'RACT-'.$year.'-'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
