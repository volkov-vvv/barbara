<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\StudentLearningAnalyticsService;
use Illuminate\Support\Carbon;

test('review answer closes yesterday open session and starts a new one', function (): void {
    Carbon::setTestNow('2026-09-30 15:00:00');

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

    $oldSession = ReviewSession::query()->create([
        'user_id' => $student->id,
        'started_at' => Carbon::parse('2026-09-28 16:00:00'),
        'finished_at' => null,
        'correct_count' => 5,
        'wrong_count' => 1,
    ]);

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $word->id,
        'repetitions' => 0,
        'ease_factor' => 2.5,
        'interval_days' => 0,
        'next_review_at' => Carbon::now()->subMinute(),
        'total_correct' => 0,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->post(route('student.reviews.store'), [
            'word_id' => $word->id,
            'quality' => 4,
        ])
        ->assertRedirect();

    $oldSession->refresh();
    expect($oldSession->finished_at)->not->toBeNull()
        ->and($oldSession->finished_at->toDateString())->toBe('2026-09-28');

    $todaySession = ReviewSession::query()
        ->where('user_id', $student->id)
        ->whereNull('finished_at')
        ->first();

    expect($todaySession)->not->toBeNull()
        ->and($todaySession->started_at->toDateString())->toBe('2026-09-30')
        ->and($todaySession->correct_count)->toBe(1)
        ->and($todaySession->wrong_count)->toBe(0);

    Carbon::setTestNow();
});

test('daily activity uses last reviewed dates so same-day practice appears', function (): void {
    Carbon::setTestNow('2026-09-30 15:00:00');

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
        'text' => 'world',
        'translation' => 'мир',
        'example_sentence' => 'Hello world.',
    ]);

    ReviewSession::query()->create([
        'user_id' => $student->id,
        'started_at' => Carbon::parse('2026-09-28 16:00:00'),
        'finished_at' => null,
        'correct_count' => 10,
        'wrong_count' => 2,
    ]);

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $word->id,
        'repetitions' => 1,
        'ease_factor' => 2.5,
        'interval_days' => 1,
        'next_review_at' => Carbon::now()->addDay(),
        'last_reviewed_at' => Carbon::parse('2026-09-30 14:00:00'),
        'total_correct' => 1,
        'total_wrong' => 0,
    ]);

    $activity = app(StudentLearningAnalyticsService::class)
        ->dailyActivityForUsers(collect([$student->id]));

    expect($activity->firstWhere('date', '2026-09-30'))
        ->not->toBeNull()
        ->reviews->toBe(1);

    Carbon::setTestNow();
});
