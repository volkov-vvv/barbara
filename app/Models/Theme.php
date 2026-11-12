<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Word> $words
 * @property-read int|null $words_count
 */
#[Fillable(['name'])]
class Theme extends Model
{
    /**
     * @return BelongsToMany<Word, $this>
     */
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class)->withTimestamps();
    }
}
