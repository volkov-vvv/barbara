<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
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
 * @property-read Collection<int, User> $students
 * @property-read Collection<int, User> $parents
 * @property-read Collection<int, WordSet> $wordSets
 * @property-read Collection<int, UserWordProgress> $wordProgress
 * @property-read Collection<int, ReviewSession> $reviewSessions
 */
#[Fillable(['name', 'email', 'password', 'role', 'daily_word_goal', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
