<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Theme;
use App\Models\User;
use App\Models\WordSet;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can list themes', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::query()->create(['name' => 'Animals']);

    $this->actingAs($admin)
        ->get(route('admin.themes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Themes')
            ->has('themes', 1)
            ->where('themes.0.id', $theme->id)
            ->where('themes.0.name', 'Animals')
            ->where('themes.0.words_count', 0));
});

test('admin can create a theme', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.themes.store'), [
            'name' => 'Food',
        ])
        ->assertRedirect(route('admin.themes.index'));

    $this->assertDatabaseHas('themes', ['name' => 'Food']);
});

test('admin can update a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::query()->create(['name' => 'Travel']);

    $this->actingAs($admin)
        ->put(route('admin.themes.update', $theme), [
            'name' => 'Trips',
        ])
        ->assertRedirect(route('admin.themes.index'));

    $this->assertDatabaseHas('themes', [
        'id' => $theme->id,
        'name' => 'Trips',
    ]);
});

test('admin can delete a theme', function (): void {
    $admin = User::factory()->admin()->create();
    $theme = Theme::query()->create(['name' => 'School']);

    $this->actingAs($admin)
        ->delete(route('admin.themes.destroy', $theme))
        ->assertRedirect(route('admin.themes.index'));

    $this->assertDatabaseMissing('themes', ['id' => $theme->id]);
});

test('student cannot manage themes', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('admin.themes.index'))
        ->assertForbidden();
});

test('admin can attach themes when creating a word', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);
    $animals = Theme::query()->create(['name' => 'Animals']);
    $nature = Theme::query()->create(['name' => 'Nature']);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.store', $wordSet), [
            'text' => 'cat',
            'translation' => 'кот',
            'example_sentence' => 'The cat sleeps.',
            'theme_ids' => [$animals->id, $nature->id],
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word = $wordSet->words()->where('text', 'cat')->firstOrFail();

    expect($word->themes()->pluck('themes.id')->sort()->values()->all())
        ->toBe([$animals->id, $nature->id]);
});

test('admin can create a word without themes', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);
    Theme::query()->create(['name' => 'Animals']);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.store', $wordSet), [
            'text' => 'desk',
            'translation' => 'парта',
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word = $wordSet->words()->where('text', 'desk')->firstOrFail();

    expect($word->themes)->toHaveCount(0)
        ->and($word->example_sentence)->toBe('');
});

test('admin can sync themes when updating a word', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);
    $animals = Theme::query()->create(['name' => 'Animals']);
    $food = Theme::query()->create(['name' => 'Food']);
    $word = $wordSet->words()->create([
        'text' => 'apple',
        'translation' => 'яблоко',
        'example_sentence' => '',
    ]);
    $word->themes()->attach($animals);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->put(route('admin.words.update', [$wordSet, $word]), [
            'text' => 'apple',
            'translation' => 'яблоко',
            'example_sentence' => 'I eat an apple.',
            'theme_ids' => [$food->id],
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    expect($word->fresh()->themes()->pluck('themes.id')->all())
        ->toBe([$food->id]);
});

test('word set show redirects to view page', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.show', $wordSet))
        ->assertRedirect(route('admin.word-sets.view', $wordSet));
});
