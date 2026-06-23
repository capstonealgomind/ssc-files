<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN_EMAIL_DOMAIN = 'sscevs.admin.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
}
