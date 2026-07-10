<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class BallotReceipt extends Model
{
    protected $fillable = [
        'user_id',
        'election_id',
        'receipt_number',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function signedPdfDownloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'ballot-receipt.pdf',
            now()->addHour(),
            ['receipt' => $this->id, 'user' => $this->user_id],
        );
    }

    public static function generateReceiptNumber(): string
    {
        $year = now()->year;

        $last = self::query()
            ->where('receipt_number', 'like', "BR-{$year}-%")
            ->orderByDesc('receipt_number')
            ->lockForUpdate()
            ->value('receipt_number');

        $seq = $last ? (int) substr($last, -6) + 1 : 1;

        return 'BR-'.$year.'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
