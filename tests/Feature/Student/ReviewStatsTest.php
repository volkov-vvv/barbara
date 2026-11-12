<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('self-check index includes review stats for the student', function (): void {
    Carbon::setTestNow('2026-11-12 15:00:00');

    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Demo',
        'created_by' => null,
    ]);

    $easyWord = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'easy',
        'translation' => 'легко',
        'example_sentence' => 'Easy.',
    ]);
    $hardWord = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'hard',
        'translation' => 'трудно',
        'example_sentence' => 'Hard.',
    ]);
    $neutralWord = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'ok',
        'translation' => 'норм',
        'example_sentence' => 'OK.',
    ]);
    $newWord = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'fresh',
        'translation' => 'новый',
        'example_sentence' => 'Fresh.',
    ]);

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $easyWord->id,
        'repetitions' => 2,
        'ease_factor' => 2.8,
        'interval_days' => 6,
        'next_review_at' => Carbon::now()->addDay(),
        'last_reviewed_at' => Carbon::parse('2026-11-11 10:00:00'),
        'total_correct' => 2,
        'total_wrong' => 0,
    ]);
    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $hardWord->id,
        'repetitions' => 0,
        'ease_factor' => 1.8,
        'interval_days' => 1,
        'next_review_at' => Carbon::now()->subMinute(),
        'last_reviewed_at' => Carbon::parse('2026-11-12 12:00:00'),
        'total_correct' => 0,
        'total_wrong' => 2,
    ]);
    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $neutralWord->id,
        'repetitions' => 1,
        'ease_factor' => 2.5,
        'interval_days' => 1,
        'next_review_at' => Carbon::now()->addDays(2),
        'last_reviewed_at' => Carbon::parse('2026-11-10 09:00:00'),
        'total_correct' => 1,
        'total_wrong' => 0,
    ]);
    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $newWord->id,
        'repetitions' => 0,
        'ease_factor' => 2.5,
        'interval_days' => 0,
        'next_review_at' => Carbon::now(),
        'last_reviewed_at' => null,
        'total_correct' => 0,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->get(route('student.reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/ReviewCards')
            ->where('stats.total_cards', 4)
            ->where('stats.not_started_cards', 1)
            ->where('stats.easy_cards', 2)
            ->where('stats.hard_cards', 1)
            ->where(
                'stats.last_reviewed_at',
                Carbon::parse('2026-11-12 12:00:00')->toIso8601String(),
            )
        );

    Carbon::setTestNow();
});
