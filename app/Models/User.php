<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $first_name
 * @property string $last_name
 * @property string|null $patronymic
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property int $daily_word_goal
 * @property Locale $locale
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $avatar
 * @property-read Collection<int, User> $students
 * @property-read Collection<int, User> $parents
 * @property-read Collection<int, WordSet> $wordSets
 * @property-read Collection<int, UserWordProgress> $wordProgress
 * @property-read Collection<int, ReviewSession> $reviewSessions
 */
#[Fillable([
    'name',
    'first_name',
    'last_name',
    'patronymic',
    'phone',
    'avatar_path',
    'email',
    'password',
    'role',
    'daily_word_goal',
    'locale',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'avatar_path'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = ['avatar'];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $composed = $user->composeDisplayName();

            if ($composed !== '') {
                $user->name = $composed;
            }
        });

        static::deleting(function (User $user): void {
            $user->clearAvatar();
        });
    }

    /**
     * Build a display name from ФИО parts.
     */
    public function composeDisplayName(): string
    {
        $parts = array_values(array_filter(
            [
                trim((string) ($this->last_name ?? '')),
                trim((string) ($this->first_name ?? '')),
                trim((string) ($this->patronymic ?? '')),
            ],
            static fn (string $part): bool => $part !== '',
        ));

        return implode(' ', $parts);
    }

    /**
     * Public URL for the user avatar, if any.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! is_string($this->avatar_path) || $this->avatar_path === '') {
                return null;
            }

            return Storage::disk('public')->url($this->avatar_path);
        });
    }

    public function storeAvatar(UploadedFile $image): string
    {
        $safeName = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '_',
            $image->getClientOriginalName(),
        ) ?: 'avatar';

        $storedPath = $image->storeAs(
            'avatars',
            uniqid('avatar_', true).'_'.$safeName,
            'public',
        );

        abort_unless(is_string($storedPath) && $storedPath !== '', 500);

        return $storedPath;
    }

    public function clearAvatar(): void
    {
        if (! is_string($this->avatar_path) || $this->avatar_path === '') {
            return;
        }

        if (Storage::disk('public')->exists($this->avatar_path)) {
            Storage::disk('public')->delete($this->avatar_path);
        }

        $this->avatar_path = null;
    }

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
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'locale' => Locale::class,
        ];
    }

    /**
     * Students linked to this parent.
     *
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * Parents linked to this student.
     *
     * @return BelongsToMany<User, $this>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * Word sets created by this user.
     *
     * @return HasMany<WordSet, $this>
     */
    public function wordSets(): HasMany
    {
        return $this->hasMany(WordSet::class, 'created_by');
    }

    /**
     * SM-2 progress records for this user.
     *
     * @return HasMany<UserWordProgress, $this>
     */
    public function wordProgress(): HasMany
    {
        return $this->hasMany(UserWordProgress::class);
    }

    /**
     * Review sessions for this user.
     *
     * @return HasMany<ReviewSession, $this>
     */
    public function reviewSessions(): HasMany
    {
        return $this->hasMany(ReviewSession::class);
    }
}
