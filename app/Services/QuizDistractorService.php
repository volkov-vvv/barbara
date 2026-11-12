<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Word;
use Illuminate\Support\Collection;

class QuizDistractorService
{
    public const int OPTION_COUNT = 4;

    /**
     * Build a shuffled list of answer options for a quiz card.
     * The correct word is always included; distractors come from the same
     * word set first, then other system dictionaries of the same language.
     *
     * @return Collection<int, Word>
     */
    public function optionsFor(Word $word, int $count = self::OPTION_COUNT): Collection
    {
        $count = max(2, $count);
        $needed = $count - 1;

        $word->loadMissing('wordSet:id,language_id');

        $excludeIds = collect([$word->id]);
        $excludeTranslations = collect([mb_strtolower(trim($word->translation))]);

        $distractors = $this->pickDistractors(
            Word::query()
                ->where('word_set_id', $word->word_set_id)
                ->whereNotIn('id', $excludeIds->all()),
            $needed,
            $excludeTranslations,
        );

        $excludeIds = $excludeIds->merge($distractors->pluck('id'))->unique()->values();
        $excludeTranslations = $excludeTranslations
            ->merge($distractors->map(fn (Word $item): string => mb_strtolower(trim($item->translation))))
            ->unique()
            ->values();

        if ($distractors->count() < $needed) {
            $languageId = $word->wordSet?->language_id;
            $remaining = $needed - $distractors->count();

            if ($languageId !== null && $remaining > 0) {
                $extra = $this->pickDistractors(
                    Word::query()
                        ->whereHas(
                            'wordSet',
                            function ($query) use ($languageId): void {
                                $query->where('language_id', $languageId)
                                    ->whereNull('created_by');
                            },
                        )
                        ->whereNotIn('id', $excludeIds->all()),
                    $remaining,
                    $excludeTranslations,
                );

                $distractors = $distractors->concat($extra)->values();
            }
        }

        return $distractors
            ->push($word)
            ->unique('id')
            ->shuffle()
            ->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Word>  $query
     * @param  Collection<int, string>  $excludeTranslations
     * @return Collection<int, Word>
     */
    private function pickDistractors($query, int $limit, Collection $excludeTranslations): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        /** @var Collection<int, Word> $candidates */
        $candidates = $query
            ->inRandomOrder()
            ->limit(max($limit * 4, 12))
            ->get(['id', 'word_set_id', 'text', 'translation', 'example_sentence', 'audio_url', 'image_path']);

        $selected = collect();

        foreach ($candidates as $candidate) {
            $translation = mb_strtolower(trim($candidate->translation));

            if ($translation === '' || $excludeTranslations->contains($translation)) {
                continue;
            }

            $selected->push($candidate);
            $excludeTranslations = $excludeTranslations->push($translation)->unique()->values();

            if ($selected->count() >= $limit) {
                break;
            }
        }

        return $selected->values();
    }
}
