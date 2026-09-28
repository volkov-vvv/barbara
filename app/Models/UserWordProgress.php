<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $word_id
 * @property int $repetitions
 * @property float $ease_factor
 * @property int $interval_days
 * @property Carbon $next_review_at
 * @property Carbon|null $last_reviewed_at
 * @property int $total_correct
 * @property int $total_wrong
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Word $word
 */
#[Fillable([
    'user_id',
    'word_id',
    'repetitions',
    'ease_factor',
    'interval_days',
    'next_review_at',
    'last_reviewed_at',
    'total_correct',
    'total_wrong',
])]
class UserWordProgress extends Model
{
    protected $table = 'user_word_progress';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repetitions' => 'integer',
            'ease_factor' => 'float',
            'interval_days' => 'integer',
            'next_review_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'total_correct' => 'integer',
            'total_wrong' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Word, $this>
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}
