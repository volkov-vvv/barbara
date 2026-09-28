<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\Word;
use App\Models\WordSet;

test('student cannot view another students word set', function (): void {
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();

    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Owner set',
        'description' => 'Private',
        'created_by' => $owner->id,
    ]);

    $this->actingAs($intruder)
        ->get(route('student.word-sets.show', $wordSet))
        ->assertForbidden();
});

test('student cannot update another students word set', function (): void {
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();

    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Owner set',
        'description' => 'Private',
        'created_by' => $owner->id,
    ]);

    $this->actingAs($intruder)
        ->put(route('student.word-sets.update', $wordSet), [
            'language_id' => $language->id,
            'title' => 'Hacked',
            'description' => 'Nope',
        ])
        ->assertForbidden();

    expect($wordSet->fresh()->title)->toBe('Owner set');
});

test('student cannot delete another students word', function (): void {
    $language = Language::query()->create(['code' => 'de', 'name' => 'German']);
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();

    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Owner set',
        'description' => 'Private',
        'created_by' => $owner->id,
    ]);

    $word = Word::query()->create([
        'word_set_id' => $wordSet->id,
        'text' => 'Hund',
        'translation' => 'Dog',
        'example_sentence' => 'Der Hund bellt.',
    ]);

    $this->actingAs($intruder)
        ->delete(route('student.words.destroy', [$wordSet, $word]))
        ->assertForbidden();

    $this->assertDatabaseHas('words', ['id' => $word->id]);
});

test('parent cannot update goal for unlinked student', function (): void {
    $parent = User::factory()->parent()->create();
    $student = User::factory()->student()->create(['daily_word_goal' => 20]);

    $this->actingAs($parent)
        ->put(route('parent.analytics.goal', $student), [
            'daily_word_goal' => 50,
        ])
        ->assertForbidden();

    expect($student->fresh()->daily_word_goal)->toBe(20);
});

test('parent can update goal for linked student', function (): void {
    $parent = User::factory()->parent()->create();
    $student = User::factory()->student()->create(['daily_word_goal' => 20]);
    $parent->students()->attach($student->id, ['relation' => 'father']);

    $this->actingAs($parent)
        ->put(route('parent.analytics.goal', $student), [
            'daily_word_goal' => 35,
        ])
        ->assertRedirect();

    expect($student->fresh()->daily_word_goal)->toBe(35);
});

test('student cannot access parent analytics', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('parent.analytics.index'))
        ->assertForbidden();
});

test('admin cannot access system word set owned by student via admin show', function (): void {
    $language = Language::query()->create(['code' => 'es', 'name' => 'Spanish']);
    $student = User::factory()->student()->create();
    $admin = User::factory()->admin()->create();

    $personalSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Personal',
        'description' => 'Mine',
        'created_by' => $student->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.show', $personalSet))
        ->assertNotFound();
});
