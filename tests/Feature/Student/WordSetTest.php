<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\Word;
use App\Models\WordSet;
use Inertia\Testing\AssertableInertia as Assert;

test('student can list create update and delete own dictionary', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $otherLanguage = Language::query()->create(['code' => 'de', 'name' => 'German']);

    $this->actingAs($student)
        ->get(route('student.word-sets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/WordSets')
            ->has('wordSets.data', 0)
            ->has('languages', 2)
        );

    $this->actingAs($student)
        ->post(route('student.word-sets.store'), [
            'language_id' => $language->id,
            'title' => 'Homework',
            'description' => 'My words',
        ])
        ->assertRedirect();

    $wordSet = WordSet::query()->where('created_by', $student->id)->firstOrFail();

    expect($wordSet->title)->toBe('Homework');

    $this->actingAs($student)
        ->get(route('student.word-sets.show', $wordSet))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/WordSetShow')
            ->where('wordSet.id', $wordSet->id)
            ->has('words.data', 0)
            ->where('filters.search', null)
            ->where('sort.column', 'id')
            ->where('sort.direction', 'desc')
            ->where('columns.0.key', 'id')
            ->where('columns.1.key', 'image')
            ->has('columns', 6)
            ->has('languages', 2)
        );

    $this->actingAs($student)
        ->put(route('student.word-sets.update', $wordSet), [
            'language_id' => $otherLanguage->id,
            'title' => 'Updated homework',
            'description' => 'Updated description',
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    expect($wordSet->fresh())
        ->title->toBe('Updated homework')
        ->language_id->toBe($otherLanguage->id);

    $this->actingAs($student)
        ->delete(route('student.word-sets.destroy', $wordSet))
        ->assertRedirect(route('student.word-sets.index'));

    $this->assertDatabaseMissing('word_sets', ['id' => $wordSet->id]);
});

test('student can filter and sort dictionary words in table view', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Mine',
        'description' => 'Personal',
        'created_by' => $student->id,
    ]);

    $wordSet->words()->create([
        'text' => 'apple',
        'translation' => 'яблоко',
        'example_sentence' => 'I eat an apple.',
    ]);
    $wordSet->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);

    $this->actingAs($student)
        ->get(route('student.word-sets.show', [
            'wordSet' => $wordSet,
            'search' => 'apple',
            'sort' => 'text',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/WordSetShow')
            ->has('words.data', 1)
            ->where('words.data.0.text', 'apple')
            ->where('filters.search', 'apple')
            ->where('sort.column', 'text')
            ->where('sort.direction', 'asc')
        );

    $this->actingAs($student)
        ->get(route('student.word-sets.show', [
            'wordSet' => $wordSet,
            'has_example' => '0',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('words.data', 1)
            ->where('words.data.0.text', 'book')
        );
});

test('student can add update and delete words in own dictionary', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Mine',
        'description' => 'Personal',
        'created_by' => $student->id,
    ]);

    $this->actingAs($student)
        ->post(route('student.words.store', $wordSet), [
            'text' => 'cat',
            'translation' => 'кот',
            'example_sentence' => '',
            'audio_url' => null,
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    $word = Word::query()->where('word_set_id', $wordSet->id)->firstOrFail();

    expect($word->text)->toBe('cat')
        ->and($word->example_sentence)->toBe('');

    $this->actingAs($student)
        ->from(route('student.word-sets.show', $wordSet))
        ->put(route('student.words.update', [$wordSet, $word]), [
            'text' => 'dog',
            'translation' => 'собака',
            'example_sentence' => 'The dog runs.',
            'audio_url' => null,
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    expect($word->fresh())
        ->text->toBe('dog')
        ->translation->toBe('собака')
        ->example_sentence->toBe('The dog runs.');

    $this->actingAs($student)
        ->delete(route('student.words.destroy', [$wordSet, $word]))
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    $this->assertDatabaseMissing('words', ['id' => $word->id]);
});
