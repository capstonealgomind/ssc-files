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

    public const STATUS_ACTIVE          = 'active';
    public const STATUS_PENDING_ID_SCAN = 'pending_id_scan';
    public const STATUS_PENDING_OTP     = 'pending_otp';

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'role',
        'student_id_number',
        'department_id',
        'course_id',
        'year_level_id',
        'voter_id_number',
        'id_image_path',
        'image_quality',
        'ocr_name',
        'ocr_student_id',
        'ocr_course',
        'fraud_score',
        'is_verified',
        'registration_status',
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

    // ── Static helpers ────────────────────────────────────────────────────

    public static function adminEmailSuffix(): string
    {
        return '@'.self::ADMIN_EMAIL_DOMAIN;
    }

    public static function isAdminEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), self::adminEmailSuffix());
    }

    public static function roleFromEmail(string $email): string
    {
        return self::isAdminEmail($email) ? 'admin' : 'voter';
    }

    public static function buildAdminEmail(string $localPart): string
    {
        $localPart = strtolower(trim($localPart));

        if ($localPart === '' || str_contains($localPart, '@')) {
            throw new InvalidArgumentException('Invalid admin email local part.');
        }

        return $localPart.self::adminEmailSuffix();
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
