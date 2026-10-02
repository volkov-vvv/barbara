<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\Language;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats', null));
});

test('admin dashboard includes overview stats', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->student()->create();
    User::factory()->parent()->create();

    $language = Language::query()->create([
        'code' => 'en',
        'name' => 'English',
    ]);

    $systemSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System set',
        'created_by' => null,
    ]);

    $word = Word::query()->create([
        'word_set_id' => $systemSet->id,
        'text' => 'hello',
        'translation' => 'привет',
        'example_sentence' => 'Hello world',
        'audio_url' => null,
    ]);

    $student = User::factory()->student()->create();

    UserWordProgress::query()->create([
        'user_id' => $student->id,
        'word_id' => $word->id,
        'repetitions' => 0,
        'ease_factor' => 2.5,
        'interval_days' => 0,
        'next_review_at' => Carbon::now()->subHour(),
        'last_reviewed_at' => Carbon::today(),
        'total_correct' => 3,
        'total_wrong' => 1,
    ]);

    Application::factory()->create([
        'email' => 'applicant@example.com',
        'full_name' => 'Test Applicant',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->where('stats.users.admins', 1)
            ->where('stats.users.students', 2)
            ->where('stats.users.parents', 1)
            ->where('stats.applications.total', 1)
            ->where('stats.dictionaries.system_sets', 1)
            ->where('stats.dictionaries.words', 1)
            ->where('stats.progress.due_cards', 1)
            ->where('stats.progress.reviews_today', 1)
            ->where('stats.progress.total_correct', 3)
            ->has('stats.applications.recent', 1));
});
