<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\WordSet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('student can view own word details page', function (): void {
    Storage::fake('public');

    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Mine',
        'description' => 'Personal',
        'created_by' => $student->id,
    ]);
    $imagePath = UploadedFile::fake()->image('pen.jpg')->store('words', 'public');
    $word = $wordSet->words()->create([
        'text' => 'pen',
        'translation' => 'ручка',
        'example_sentence' => 'I write with a pen.',
        'image_path' => $imagePath,
    ]);

    $this->actingAs($student)
        ->get(route('student.words.show', [$wordSet, $word]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/WordShow')
            ->where('wordSet.title', 'Mine')
            ->where('word.text', 'pen')
            ->where('word.translation', 'ручка')
            ->where('word.example_sentence', 'I write with a pen.')
            ->where('word.image_path', $imagePath)
        );
});

test('student cannot view another students word', function (): void {
    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Owner',
        'description' => 'Private',
        'created_by' => $owner->id,
    ]);
    $word = $wordSet->words()->create([
        'text' => 'cat',
        'translation' => 'кот',
        'example_sentence' => '',
    ]);

    $this->actingAs($intruder)
        ->get(route('student.words.show', [$wordSet, $word]))
        ->assertForbidden();
});

test('student cannot view a word from another word set', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'A',
        'description' => 'A',
        'created_by' => $student->id,
    ]);
    $otherSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'B',
        'description' => 'B',
        'created_by' => $student->id,
    ]);
    $word = $otherSet->words()->create([
        'text' => 'dog',
        'translation' => 'собака',
        'example_sentence' => '',
    ]);

    $this->actingAs($student)
        ->get(route('student.words.show', [$wordSet, $word]))
        ->assertNotFound();
});
