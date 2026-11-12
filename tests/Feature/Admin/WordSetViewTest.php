<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Theme;
use App\Models\User;
use App\Models\WordSet;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view dictionary words in a table page', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.view', $wordSet))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordSetView')
            ->where('wordSet.title', 'Grade 3')
            ->has('words.data', 1)
            ->where('words.data.0.text', 'pen')
            ->where('filters.search', null)
            ->where('sort.column', 'id')
            ->where('sort.direction', 'desc')
            ->where('columns.0.key', 'id')
            ->where('columns.0.default', true)
            ->where('columns.1.key', 'image')
            ->where('columns.5.key', 'themes')
            ->has('columns', 7)
            ->has('themes'));
});

test('admin can see themes on dictionary view page', function (): void {
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
        'example_sentence' => '',
    ]);
    $word->themes()->attach($theme);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.view', $wordSet))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordSetView')
            ->where('words.data.0.themes.0.name', 'School')
            ->has('themes', 1));
});

test('admin can filter dictionary words by theme', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);
    $animals = Theme::query()->create(['name' => 'Animals']);
    $food = Theme::query()->create(['name' => 'Food']);

    $cat = $wordSet->words()->create([
        'text' => 'cat',
        'translation' => 'кот',
        'example_sentence' => '',
    ]);
    $cat->themes()->attach($animals);

    $apple = $wordSet->words()->create([
        'text' => 'apple',
        'translation' => 'яблоко',
        'example_sentence' => '',
    ]);
    $apple->themes()->attach($food);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.view', [
            'wordSet' => $wordSet,
            'theme_id' => $animals->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordSetView')
            ->has('words.data', 1)
            ->where('words.data.0.text', 'cat')
            ->where('filters.theme_id', (string) $animals->id));
});

test('admin can filter dictionary words by search and example', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => 'I write with a pen.',
    ]);
    $wordSet->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.view', [
            'wordSet' => $wordSet,
            'search' => 'pen',
            'has_example' => '1',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordSetView')
            ->has('words.data', 1)
            ->where('words.data.0.text', 'pen')
            ->where('filters.search', 'pen')
            ->where('filters.has_example', '1'));
});

test('admin can sort dictionary words', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'zebra',
        'translation' => 'зебра',
        'example_sentence' => '',
    ]);
    $wordSet->words()->create([
        'text' => 'apple',
        'translation' => 'яблоко',
        'example_sentence' => '',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.word-sets.view', [
            'wordSet' => $wordSet,
            'sort' => 'text',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/WordSetView')
            ->where('words.data.0.text', 'apple')
            ->where('words.data.1.text', 'zebra')
            ->where('sort.column', 'text')
            ->where('sort.direction', 'asc'));
});

test('admin can export dictionary view to excel with filters', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => 'I write with a pen.',
    ]);
    $wordSet->words()->create([
        'text' => 'book',
        'translation' => 'книга',
        'example_sentence' => '',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.word-sets.view.export.excel', [
            'wordSet' => $wordSet,
            'search' => 'pen',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain(
        'spreadsheetml.sheet',
    );
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');
});

test('admin can export dictionary view to pdf with filters', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'School words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => '',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.word-sets.view.export.pdf', [
            'wordSet' => $wordSet,
            'has_example' => '0',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('student cannot view system dictionary page', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($student)
        ->get(route('admin.word-sets.view', $wordSet))
        ->assertForbidden();
});
