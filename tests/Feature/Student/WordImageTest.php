<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\WordSet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('student can create a word with an image', function (): void {
    Storage::fake('public');

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
            'image' => UploadedFile::fake()->image('cat.jpg'),
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    $word = $wordSet->words()->where('text', 'cat')->firstOrFail();

    expect($word->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($word->image_path);
});

test('student can replace and remove a word image', function (): void {
    Storage::fake('public');

    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Mine',
        'description' => 'Personal',
        'created_by' => $student->id,
    ]);

    $oldPath = UploadedFile::fake()->image('old.png')->store('words', 'public');
    $word = $wordSet->words()->create([
        'text' => 'dog',
        'translation' => 'собака',
        'example_sentence' => '',
        'image_path' => $oldPath,
    ]);

    $this->actingAs($student)
        ->from(route('student.word-sets.show', $wordSet))
        ->put(route('student.words.update', [$wordSet, $word]), [
            'text' => 'dog',
            'translation' => 'собака',
            'example_sentence' => '',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    $word->refresh();
    expect($word->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($word->image_path);

    $currentPath = $word->image_path;

    $this->actingAs($student)
        ->from(route('student.word-sets.show', $wordSet))
        ->put(route('student.words.update', [$wordSet, $word]), [
            'text' => 'dog',
            'translation' => 'собака',
            'example_sentence' => '',
            'remove_image' => '1',
        ])
        ->assertRedirect(route('student.word-sets.show', $wordSet));

    $word->refresh();
    expect($word->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($currentPath);
});
