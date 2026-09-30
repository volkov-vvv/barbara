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

test('admin can view student progress index and detail', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.show', $student))
        ->assertOk();
});

test('admin student progress detail includes last session breakdown', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Starter words',
        'created_by' => null,
    ]);
    $word = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'hello',
        'translation' => 'привет',
        'example_sentence' => 'Hello there.',
        'audio_url' => null,
    ]);

    $startedAt = Carbon::now()->subMinutes(20);

    ReviewSession::query()->create([
        'user_id' => $student->id,
        'started_at' => $startedAt,
        'finished_at' => null,
        'correct_count' => 3,
        'wrong_count' => 1,
    ]);

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $word->id,
        'repetitions' => 1,
        'ease_factor' => 2.6,
        'interval_days' => 1,
        'next_review_at' => Carbon::now()->addDay(),
        'last_reviewed_at' => $startedAt->copy()->addMinutes(5),
        'total_correct' => 3,
        'total_wrong' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.student-progress.show', $student))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/StudentProgressShow')
            ->where('lastSession.correct_count', 3)
            ->where('lastSession.wrong_count', 1)
            ->where('lastSession.accuracy_percent', 75)
            ->where('lastSession.is_open', true)
            ->has('lastSession.reviewed_words', 1)
            ->where('lastSession.reviewed_words.0.text', 'hello')
            ->where('lastSession.reviewed_words.0.translation', 'привет')
            ->where('lastSession.reviewed_words.0.word_set_title', 'Basics'));
});

test('admin cannot open progress for a non-student user', function (): void {
    $admin = User::factory()->admin()->create();
    $parent = User::factory()->parent()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.show', $parent))
        ->assertNotFound();
});

test('student cannot access admin student progress', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('admin.student-progress.index'))
        ->assertForbidden();
});
