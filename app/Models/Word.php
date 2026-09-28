<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $word_set_id
 * @property string $text
 * @property string $translation
 * @property string $example_sentence
 * @property string|null $audio_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WordSet $wordSet
 * @property-read Collection<int, UserWordProgress> $progress
 */
#[Fillable(['word_set_id', 'text', 'translation', 'example_sentence', 'audio_url'])]
class Word extends Model
{
    /**
     * @return BelongsTo<WordSet, $this>
     */
    public function wordSet(): BelongsTo
    {
        return $this->belongsTo(WordSet::class);
    }

    /**
     * @return HasMany<UserWordProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserWordProgress::class);
    }
}
