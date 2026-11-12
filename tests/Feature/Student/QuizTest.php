<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function createQuizFixture(int $wordCount = 5): array
{
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Quiz set',
        'description' => 'Demo',
        'created_by' => null,
    ]);

    $words = collect();

    for ($i = 1; $i <= $wordCount; $i++) {
        $words->push(Word::query()->create([
            'word_set_id' => $wordSet->id,
            'text' => "слово-{$i}",
            'translation' => "word-{$i}",
            'example_sentence' => "Example {$i}",
        ]));
    }

    return compact('student', 'language', 'wordSet', 'words');
}

test('student quiz index returns due cards with four answer options', function (): void {
    Carbon::setTestNow('2026-11-12 12:00:00');

    ['student' => $student, 'words' => $words] = createQuizFixture(5);
    $target = $words->first();

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $target->id,
        'repetitions' => 0,
        'ease_factor' => 2.5,
        'interval_days' => 0,
        'next_review_at' => Carbon::now()->subMinute(),
        'total_correct' => 0,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->get(route('student.quizzes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/QuizCards')
            ->has('cards', 1)
            ->where('cards.0.word_id', $target->id)
            ->has('cards.0.options', 4)
            ->where('cards.0.options', function (mixed $options) use ($target): bool {
                $items = collect($options);

                return $items->count() === 4
                    && $items->contains(fn (mixed $option): bool => (int) data_get($option, 'id') === $target->id)
                    && $items->pluck('id')->unique()->count() === 4
                    && $items->every(
                        fn (mixed $option): bool => filled(data_get($option, 'label')),
                    );
            })
        );

    Carbon::setTestNow();
});

test('correct quiz answer applies quality 4 and increments session correct count', function (): void {
    Carbon::setTestNow('2026-11-12 12:00:00');

    ['student' => $student, 'words' => $words] = createQuizFixture(4);
    $target = $words->first();

    $progress = UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $target->id,
        'repetitions' => 0,
        'ease_factor' => 2.5,
        'interval_days' => 0,
        'next_review_at' => Carbon::now()->subMinute(),
        'total_correct' => 0,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->post(route('student.quizzes.store'), [
            'word_id' => $target->id,
            'selected_option_id' => $target->id,
        ])
        ->assertRedirect();

    $progress->refresh();

    expect($progress->repetitions)->toBe(1)
        ->and($progress->total_correct)->toBe(1)
        ->and($progress->total_wrong)->toBe(0)
        ->and($progress->interval_days)->toBe(1);

    $session = ReviewSession::query()
        ->where('user_id', $student->id)
        ->whereNull('finished_at')
        ->first();

    expect($session)->not->toBeNull()
        ->and($session->correct_count)->toBe(1)
        ->and($session->wrong_count)->toBe(0);

    Carbon::setTestNow();
});

test('incorrect quiz answer applies quality 1 and increments session wrong count', function (): void {
    Carbon::setTestNow('2026-11-12 12:00:00');

    ['student' => $student, 'words' => $words] = createQuizFixture(4);
    $target = $words->first();
    $wrong = $words->get(1);

    $progress = UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $target->id,
        'repetitions' => 2,
        'ease_factor' => 2.5,
        'interval_days' => 6,
        'next_review_at' => Carbon::now()->subMinute(),
        'total_correct' => 2,
        'total_wrong' => 0,
    ]);

    $this->actingAs($student)
        ->post(route('student.quizzes.store'), [
            'word_id' => $target->id,
            'selected_option_id' => $wrong->id,
        ])
        ->assertRedirect();

    $progress->refresh();

    expect($progress->repetitions)->toBe(0)
        ->and($progress->total_correct)->toBe(2)
        ->and($progress->total_wrong)->toBe(1)
        ->and($progress->interval_days)->toBe(1);

    $session = ReviewSession::query()
        ->where('user_id', $student->id)
        ->whereNull('finished_at')
        ->first();

    expect($session)->not->toBeNull()
        ->and($session->correct_count)->toBe(0)
        ->and($session->wrong_count)->toBe(1);

    Carbon::setTestNow();
});

test('non-students cannot access quiz routes', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('student.quizzes.index'))
        ->assertForbidden();
});
