<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\QuizDistractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('quiz distractors prefer the same word set and always include the correct word', function (): void {
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Demo',
        'created_by' => null,
    ]);

    $correct = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'кот',
        'translation' => 'cat',
        'example_sentence' => 'A cat.',
    ]);

    foreach (['dog', 'bird', 'fish', 'horse'] as $translation) {
        Word::query()->create([
            'word_set_id' => $wordSet->id,
            'text' => $translation.'-ru',
            'translation' => $translation,
            'example_sentence' => $translation,
        ]);
    }

    $options = app(QuizDistractorService::class)->optionsFor($correct);

    expect($options)->toHaveCount(4)
        ->and($options->contains(fn (Word $word): bool => $word->id === $correct->id))->toBeTrue()
        ->and($options->pluck('id')->unique())->toHaveCount(4)
        ->and($options->every(fn (Word $word): bool => $word->word_set_id === $wordSet->id))->toBeTrue();
});

test('quiz distractors fall back to other system dictionaries of the same language', function (): void {
    $language = Language::query()->create(['code' => 'de', 'name' => 'German']);
    $smallSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Tiny',
        'description' => 'Only two words',
        'created_by' => null,
    ]);
    $otherSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Bigger',
        'description' => 'Fallback pool',
        'created_by' => null,
    ]);

    $correct = Word::query()->create([
        'word_set_id' => $smallSet->id,
        'text' => 'дом',
        'translation' => 'Haus',
        'example_sentence' => 'Haus',
    ]);
    Word::query()->create([
        'word_set_id' => $smallSet->id,
        'text' => 'сад',
        'translation' => 'Garten',
        'example_sentence' => 'Garten',
    ]);

    foreach (['Baum', 'Buch', 'Stadt'] as $translation) {
        Word::query()->create([
            'word_set_id' => $otherSet->id,
            'text' => $translation.'-ru',
            'translation' => $translation,
            'example_sentence' => $translation,
        ]);
    }

    $options = app(QuizDistractorService::class)->optionsFor($correct);

    expect($options)->toHaveCount(4)
        ->and($options->contains(fn (Word $word): bool => $word->id === $correct->id))->toBeTrue()
        ->and($options->pluck('translation')->unique()->count())->toBe(4)
        ->and($options->contains(fn (Word $word): bool => $word->word_set_id === $otherSet->id))->toBeTrue();
});
