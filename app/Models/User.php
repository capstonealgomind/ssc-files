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
    public const STATUS_DISABLED        = 'disabled';
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
        'year_level_updated_school_year_start',
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
        'is_expired',
        'is_disabled',
        'year_level_update_override',
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
            'is_expired'        => 'boolean',
            'is_disabled'       => 'boolean',
            'year_level_update_override' => 'boolean',
            'fraud_score'       => 'integer',
            'otp_attempts'      => 'integer',
            'year_level_updated_school_year_start' => 'integer',
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

    public function yearLevelAppeals(): HasMany
    {
        return $this->hasMany(YearLevelAppeal::class);
    }

    public function currentYearLevelAppeal(): ?YearLevelAppeal
    {
        $startYear = (int) SchoolYearSetting::current()->start_year;

        if ($this->relationLoaded('yearLevelAppeals')) {
            return $this->yearLevelAppeals
                ->where('school_year_start', $startYear)
                ->sortByDesc('id')
                ->first();
        }

        return $this->yearLevelAppeals()
            ->where('school_year_start', $startYear)
            ->latest('id')
            ->first();
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function pagePermissions(): HasMany
    {
        return $this->hasMany(CommitteePagePermission::class);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isCommittee(): bool
    {
        return $this->role === 'committee';
    }

    /**
     * @return list<string>
     */
    public function allowedPages(): array
    {
        if ($this->role === 'admin') {
            return array_keys(\App\Support\CommitteePageCatalog::pages());
        }

        if ($this->role !== 'committee') {
            return [];
        }

        if ($this->relationLoaded('pagePermissions')) {
            return $this->pagePermissions->pluck('page_key')->unique()->values()->all();
        }

        return $this->pagePermissions()->pluck('page_key')->unique()->values()->all();
    }

    public function canAccessPage(string $pageKey): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role !== 'committee') {
            return false;
        }

        return in_array($pageKey, $this->allowedPages(), true);
    }

    public function syncDefaultPagePermissions(): void
    {
        if ($this->role !== 'committee') {
            return;
        }

        foreach (\App\Support\CommitteePageCatalog::DEFAULT_PAGES as $pageKey) {
            $this->pagePermissions()->firstOrCreate(['page_key' => $pageKey]);
        }
    }

    public function skipsVoterVerification(): bool
    {
        return in_array($this->role, ['admin', 'staff', 'committee'], true);
    }

    public function isExpired(): bool
    {
        return (bool) $this->is_expired;
    }

    public function isDisabled(): bool
    {
        return (bool) $this->is_disabled;
    }

    public function lockYearLevelForCurrentSchoolYear(): void
    {
        $startYear = (int) SchoolYearSetting::current()->start_year;

        if ($startYear <= 0) {
            return;
        }

        $this->forceFill([
            'year_level_updated_school_year_start' => $startYear,
        ])->save();
    }

    public function remainingCourseYears(): int
    {
        $this->loadMissing(['course', 'yearLevel']);

        $duration = (int) ($this->course?->duration_years ?? 0);
        $yearLevel = (int) ($this->yearLevel?->sort_order ?? 0);

        // Years left after this school year. 4th year of a 4-year course → 0.
        return max(0, $duration - $yearLevel);
    }

    public function hasUpdatedYearLevelThisSchoolYear(): bool
    {
        $startYear = (int) SchoolYearSetting::current()->start_year;

        return $startYear > 0
            && (int) $this->year_level_updated_school_year_start === $startYear;
    }

    public function canEditYearLevel(): bool
    {
        if ($this->role !== 'voter' || $this->isExpired() || $this->isDisabled()) {
            return false;
        }

        if ($this->hasUpdatedYearLevelThisSchoolYear()) {
            return false;
        }

        if ($this->year_level_update_override) {
            return true;
        }

        return SchoolYearSetting::current()->isYearLevelEditWindowOpen();
    }

    public function mustUpdateYearLevelBeforeVoting(): bool
    {
        return (bool) $this->year_level_update_override
            && ! $this->hasUpdatedYearLevelThisSchoolYear();
    }

    public function calculateAccountExpiresAt(?\DateTimeInterface $from = null): ?\Carbon\Carbon
    {
        $fromAt = $from
            ? \Carbon\Carbon::instance($from)
            : ($this->created_at?->copy() ?? now());

        $schoolYear = SchoolYearSetting::current();

        if (! $schoolYear->isConfigured()) {
            return $fromAt->copy()->addYear();
        }

        $remaining = $this->remainingCourseYears();
        $computed = $this->expiryDateOnAnniversary(
            $fromAt,
            $schoolYear->expireYearForRemainingYears($remaining),
        );

        // Last year of the course: do not push expiry later when the school year moves.
        if ($remaining === 0 && $this->account_expires_at?->isFuture() && $this->account_expires_at->lt($computed)) {
            return $this->account_expires_at->copy();
        }

        return $computed;
    }

    public function applyCourseExpiry(?\DateTimeInterface $from = null): void
    {
        // Once marked expired or disabled, keep the stored flags even if dates are recalculated.
        if ($this->is_expired || $this->is_disabled) {
            return;
        }

        // If the stored expiry date has already been reached, lock expired
        // without rewriting the date so later date edits cannot un-expire.
        if ($this->account_expires_at && $this->account_expires_at->isPast()) {
            $this->forceFill([
                'is_expired' => true,
                'registration_status' => self::STATUS_EXPIRED,
            ])->save();

            return;
        }

        $expiresAt = $this->calculateAccountExpiresAt($from ?? $this->created_at ?? now());
        $expired = $expiresAt && $expiresAt->isPast();

        $this->forceFill([
            'account_expires_at' => $expiresAt,
            'is_expired' => $expired,
            'registration_status' => $expired
                ? self::STATUS_EXPIRED
                : self::STATUS_ACTIVE,
        ])->save();
    }

    public static function syncExpiriesForCurrentSchoolYear(): int
    {
        $count = 0;

        static::query()
            ->where('role', 'voter')
            ->where('is_expired', false)
            ->where('is_disabled', false)
            ->whereNotNull('course_id')
            ->whereNotNull('year_level_id')
            ->whereIn('registration_status', [
                self::STATUS_ACTIVE,
            ])
            ->with(['course:id,duration_years', 'yearLevel:id,sort_order'])
            ->orderBy('id')
            ->chunkById(100, function ($voters) use (&$count) {
                foreach ($voters as $voter) {
                    $voter->applyCourseExpiry();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Same month, day, and time as $fromAt, in $year.
     */
    private function expiryDateOnAnniversary(\Carbon\Carbon $fromAt, int $year): \Carbon\Carbon
    {
        $month = (int) $fromAt->month;
        $day = (int) $fromAt->day;
        $lastDay = (int) \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->day;
        $safeDay = min($day, $lastDay);

        return \Carbon\Carbon::create(
            $year,
            $month,
            $safeDay,
            (int) $fromAt->hour,
            (int) $fromAt->minute,
            (int) $fromAt->second,
            $fromAt->timezone,
        );
    }

    public function markExpiredIfNeeded(): bool
    {
        if ($this->skipsVoterVerification() || $this->role !== 'voter') {
            return false;
        }

        if ($this->is_expired) {
            if ($this->registration_status !== self::STATUS_EXPIRED) {
                $this->forceFill([
                    'registration_status' => self::STATUS_EXPIRED,
                ])->save();
            }

            return true;
        }

        if ($this->account_expires_at && $this->account_expires_at->isPast()) {
            $this->forceFill([
                'is_expired' => true,
                'registration_status' => self::STATUS_EXPIRED,
            ])->save();

            return true;
        }

        return false;
    }

    public function markDisabledIfNeeded(): bool
    {
        if ($this->skipsVoterVerification() || $this->role !== 'voter') {
            return false;
        }

        if ($this->is_disabled) {
            if ($this->registration_status !== self::STATUS_DISABLED && ! $this->is_expired) {
                $this->forceFill([
                    'registration_status' => self::STATUS_DISABLED,
                ])->save();
            }

            return true;
        }

        if ($this->year_level_update_override) {
            return false;
        }

        if ($this->is_expired || ! $this->is_verified) {
            return false;
        }

        $settings = SchoolYearSetting::current();

        if (! $settings->isYearLevelEditWindowEnded()) {
            return false;
        }

        if ($this->hasUpdatedYearLevelThisSchoolYear()) {
            return false;
        }

        $this->forceFill([
            'is_disabled' => true,
            'registration_status' => self::STATUS_DISABLED,
        ])->save();

        return true;
    }

    public static function disableVotersWhoMissedYearLevelUpdate(): int
    {
        $settings = SchoolYearSetting::current();

        if (! $settings->isYearLevelEditWindowEnded()) {
            return 0;
        }

        $startYear = (int) $settings->start_year;

        return static::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->where('is_expired', false)
            ->where('is_disabled', false)
            ->where('year_level_update_override', false)
            ->where(function ($query) use ($startYear) {
                $query->whereNull('year_level_updated_school_year_start')
                    ->orWhere('year_level_updated_school_year_start', '!=', $startYear);
            })
            ->update([
                'is_disabled' => true,
                'registration_status' => self::STATUS_DISABLED,
            ]);
    }

    public static function restoreYearLevelDisabledVoters(): int
    {
        return static::query()
            ->where('role', 'voter')
            ->where('is_disabled', true)
            ->where('is_expired', false)
            ->update([
                'is_disabled' => false,
                'registration_status' => self::STATUS_ACTIVE,
            ]);
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
