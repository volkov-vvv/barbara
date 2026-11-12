<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Theme;
use App\Models\User;
use App\Models\WordSet;

test('admin can load export options for a word set', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3 Words',
        'description' => 'School vocabulary',
        'created_by' => null,
    ]);
    $theme = Theme::query()->create(['name' => 'School']);

    $word = $wordSet->words()->create([
        'text' => 'school bag',
        'translation' => 'портфель',
        'example_sentence' => '',
    ]);
    $word->themes()->attach($theme);

    $this->actingAs($admin)
        ->getJson(route('admin.word-sets.export.options', $wordSet))
        ->assertOk()
        ->assertJsonPath('wordSet.id', $wordSet->id)
        ->assertJsonPath('words.0.id', $word->id)
        ->assertJsonPath('words.0.text', 'school bag')
        ->assertJsonPath('words.0.theme_ids.0', $theme->id);
});

test('admin can export word set to pdf', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3 Words',
        'description' => 'School vocabulary',
        'created_by' => null,
    ]);

    $word = $wordSet->words()->create([
        'text' => 'school bag',
        'translation' => 'портфель',
        'example_sentence' => '',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.word-sets.export.pdf', [
            'wordSet' => $wordSet,
            'word_ids' => [$word->id],
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('export pdf requires word_ids', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3 Words',
        'description' => 'School vocabulary',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.export.pdf', $wordSet))
        ->assertSessionHasErrors('word_ids');
});

test('admin can export selected words with column and random options', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3 Words',
        'description' => 'School vocabulary',
        'created_by' => null,
    ]);

    $first = $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => 'A pen',
    ]);
    $second = $wordSet->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);
    $wordSet->words()->create([
        'text' => 'ruler',
        'translation' => 'линейка',
        'example_sentence' => '',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.word-sets.export.pdf', [
            'wordSet' => $wordSet,
            'word_ids' => [$first->id, $second->id],
            'random' => 1,
            'words_only' => 1,
            'translation_only' => 1,
            'without_example' => 1,
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

test('export ignores words from another dictionary', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'A',
        'description' => 'A',
        'created_by' => null,
    ]);
    $other = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'B',
        'description' => 'B',
        'created_by' => null,
    ]);

    $own = $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => '',
    ]);
    $foreign = $other->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.word-sets.export.pdf', [
            'wordSet' => $wordSet,
            'word_ids' => [$own->id, $foreign->id],
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

test('student cannot export system word set pdf', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($student)
        ->get(route('admin.word-sets.export.pdf', $wordSet))
        ->assertForbidden();
});

test('personal word set cannot be exported as system dictionary pdf', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Personal',
        'description' => 'Owned',
        'created_by' => $student->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.export.pdf', [
            'wordSet' => $wordSet,
            'word_ids' => [1],
        ]))
        ->assertNotFound();
});
