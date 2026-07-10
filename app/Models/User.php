<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN_EMAIL_DOMAIN = 'sscevs.admin.com';

    public const COMMITTEE_EMAIL_DOMAIN = 'sscevs.committee.com';

    public const STATUS_ACTIVE          = 'active';
    public const STATUS_PENDING_ID_SCAN = 'pending_id_scan';
    public const STATUS_PENDING_OTP     = 'pending_otp';
    public const STATUS_EXPIRED         = 'expired';
    public const STATUS_PENDING_REACTIVATION = 'pending_reactivation';

    protected $fillable = [
        'name',
        'email',
        'contact_email',
        'password',
        'email_verified_at',
        'role',
        'student_id_number',
        'department_id',
        'course_id',
        'year_level_id',
        'voter_id_number',
        'id_image_path',
        'profile_photo_path',
        'image_quality',
        'ocr_name',
        'ocr_student_id',
        'ocr_course',
        'fraud_score',
        'is_verified',
        'registration_status',
        'account_expires_at',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'email_status',
        'email_send_status',
        'ocr_status',
        'verification_status',
        'email_verify_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at'    => 'datetime',
            'account_expires_at'=> 'datetime',
            'password'          => 'hashed',
            'is_verified'       => 'boolean',
            'fraud_score'       => 'integer',
            'otp_attempts'      => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function yearLevel(): BelongsTo
    {
        return $this->belongsTo(YearLevel::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isCommittee(): bool
    {
        return $this->role === 'committee';
    }

    public function skipsVoterVerification(): bool
    {
        return in_array($this->role, ['admin', 'staff', 'committee'], true);
    }

    public function isExpired(): bool
    {
        if ($this->registration_status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->account_expires_at !== null && $this->account_expires_at->isPast();
    }

    public function remainingCourseYears(): int
    {
        $this->loadMissing(['course', 'yearLevel']);

        $duration = (int) ($this->course?->duration_years ?? 0);
        $yearLevel = (int) ($this->yearLevel?->sort_order ?? 0);

        return max(0, $duration - $yearLevel);
    }

    public function calculateAccountExpiresAt(?\DateTimeInterface $from = null): ?\Carbon\Carbon
    {
        $remaining = $this->remainingCourseYears();
        $fromAt = $from ? \Carbon\Carbon::instance($from) : now();

        if ($remaining <= 0) {
            return $fromAt->copy();
        }

        return $fromAt->copy()->addYears($remaining);
    }

    public function applyCourseExpiry(?\DateTimeInterface $from = null): void
    {
        $remaining = $this->remainingCourseYears();
        $expiresAt = $this->calculateAccountExpiresAt($from);

        if ($remaining <= 0) {
            $expiresAt = now()->subSecond();
        }

        $this->forceFill([
            'account_expires_at' => $expiresAt,
            'registration_status' => ($remaining <= 0 || ($expiresAt && $expiresAt->isPast()))
                ? self::STATUS_EXPIRED
                : self::STATUS_ACTIVE,
        ])->save();
    }

    public function markExpiredIfNeeded(): bool
    {
        if ($this->skipsVoterVerification() || $this->role !== 'voter') {
            return false;
        }

        if ($this->registration_status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->account_expires_at && $this->account_expires_at->isPast()) {
            $this->forceFill([
                'registration_status' => self::STATUS_EXPIRED,
            ])->save();

            return true;
        }

        return false;
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
    }

    // ── Static helpers ────────────────────────────────────────────────────

    public static function adminEmailSuffix(): string
    {
        return '@'.self::ADMIN_EMAIL_DOMAIN;
    }

    public static function committeeEmailSuffix(): string
    {
        return '@'.self::COMMITTEE_EMAIL_DOMAIN;
    }

    public static function isAdminEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), self::adminEmailSuffix());
    }

    public static function isCommitteeEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), self::committeeEmailSuffix());
    }

    public static function isSystemEmail(string $email): bool
    {
        return self::isAdminEmail($email) || self::isCommitteeEmail($email);
    }

    public static function roleFromEmail(string $email): string
    {
        if (self::isAdminEmail($email)) {
            return 'admin';
        }

        if (self::isCommitteeEmail($email)) {
            return 'committee';
        }

        return 'voter';
    }

    public static function buildAdminEmail(string $localPart): string
    {
        $localPart = strtolower(trim($localPart));

        if ($localPart === '' || str_contains($localPart, '@')) {
            throw new InvalidArgumentException('Invalid admin email local part.');
        }

        return $localPart.self::adminEmailSuffix();
    }

    public static function buildCommitteeEmail(string $localPart): string
    {
        $localPart = strtolower(trim($localPart));

        if ($localPart === '' || str_contains($localPart, '@')) {
            throw new InvalidArgumentException('Invalid committee email local part.');
        }

        return $localPart.self::committeeEmailSuffix();
    }

    public static function adminEmailLocalPart(string $email): string
    {
        if (!self::isAdminEmail($email)) {
            return '';
        }

        return str_replace(self::adminEmailSuffix(), '', strtolower(trim($email)));
    }

    public static function generateVoterIdNumber(): string
    {
        $year = now()->year;
        $last = self::whereNotNull('voter_id_number')
            ->where('voter_id_number', 'like', "VID-{$year}-%")
            ->orderByDesc('voter_id_number')
            ->value('voter_id_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return 'VID-' . $year . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
