<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\WordSet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can create a word with an image', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.store', $wordSet), [
            'text' => 'cat',
            'translation' => 'кот',
            'example_sentence' => '',
            'image' => UploadedFile::fake()->image('cat.jpg'),
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word = $wordSet->words()->where('text', 'cat')->firstOrFail();

    expect($word->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($word->image_path);
});

test('admin can replace and remove a word image', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $oldPath = UploadedFile::fake()->image('old.png')->store('words', 'public');
    $word = $wordSet->words()->create([
        'text' => 'dog',
        'translation' => 'собака',
        'example_sentence' => '',
        'image_path' => $oldPath,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->put(route('admin.words.update', [$wordSet, $word]), [
            'text' => 'dog',
            'translation' => 'собака',
            'example_sentence' => '',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word->refresh();
    expect($word->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($word->image_path);

    $currentPath = $word->image_path;

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->put(route('admin.words.update', [$wordSet, $word]), [
            'text' => 'dog',
            'translation' => 'собака',
            'example_sentence' => '',
            'remove_image' => '1',
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word->refresh();
    expect($word->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($currentPath);
});

test('admin can create a word without an image', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.store', $wordSet), [
            'text' => 'pen',
            'translation' => 'ручка',
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    $word = $wordSet->words()->where('text', 'pen')->firstOrFail();

    expect($word->image_path)->toBeNull();
});
