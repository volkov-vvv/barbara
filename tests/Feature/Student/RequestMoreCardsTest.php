<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;

test('student can request more cards from system dictionaries', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Demo',
        'created_by' => null,
    ]);

    $word = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'hello',
        'translation' => 'привет',
        'example_sentence' => 'Hello there.',
    ]);

    $this->actingAs($student)
        ->post(route('student.reviews.more'), ['count' => 5])
        ->assertRedirect(route('student.reviews.index'));

    $this->assertDatabaseHas('user_word_progress', [
        'user_id' => $student->id,
        'word_id' => $word->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/ReviewCards')
            ->has('cards', 1)
            ->where('availableNewCount', 0));
});

test('request more does not duplicate existing progress', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'de', 'name' => 'German']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Starter',
        'description' => 'Demo',
        'created_by' => null,
    ]);

    $word = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'Haus',
        'translation' => 'дом',
        'example_sentence' => 'Das Haus.',
    ]);

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $word->id,
        'repetitions' => 1,
        'ease_factor' => 2.5,
        'interval_days' => 1,
        'next_review_at' => now()->addDay(),
        'total_correct' => 1,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->post(route('student.reviews.more'))
        ->assertRedirect(route('student.reviews.index'));

    expect(
        UserWordProgress::query()
            ->where('user_id', $student->id)
            ->where('word_id', $word->id)
            ->count(),
    )->toBe(1);
});
