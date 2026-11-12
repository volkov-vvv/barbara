<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Theme;
use App\Models\User;
use App\Models\WordSet;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view a word details page', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);
    $theme = Theme::query()->create(['name' => 'School']);
    $word = $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => 'I write with a pen.',
    ]);
    $word->themes()->attach($theme);

    $this->actingAs($admin)
        ->get(route('admin.words.show', [$wordSet, $word]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordShow')
            ->where('wordSet.title', 'Grade 3')
            ->where('word.text', 'pen')
            ->where('word.translation', 'ручка')
            ->where('word.themes.0.name', 'School')
            ->has('themes'));
});

test('admin cannot view a word from another word set', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'A',
        'description' => 'A',
        'created_by' => null,
    ]);
    $otherSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'B',
        'description' => 'B',
        'created_by' => null,
    ]);
    $word = $otherSet->words()->create([
        'text' => 'cat',
        'translation' => 'кот',
        'example_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.words.show', [$wordSet, $word]))
        ->assertNotFound();
});

test('student cannot view admin word page', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);
    $word = $wordSet->words()->create([
        'text' => 'dog',
        'translation' => 'собака',
        'example_sentence' => '',
    ]);

    $this->actingAs($student)
        ->get(route('admin.words.show', [$wordSet, $word]))
        ->assertForbidden();
});

test('deleting a word from show redirects to dictionary view', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);
    $word = $wordSet->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.words.show', [$wordSet, $word]))
        ->delete(route('admin.words.destroy', [$wordSet, $word]))
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $this->assertDatabaseMissing('words', ['id' => $word->id]);
});
