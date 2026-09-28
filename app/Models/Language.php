<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WordSet> $wordSets
 */
#[Fillable(['code', 'name'])]
class Language extends Model
{
    /**
     * @return HasMany<WordSet, $this>
     */
    public function wordSets(): HasMany
    {
        return $this->hasMany(WordSet::class);
    }
}
