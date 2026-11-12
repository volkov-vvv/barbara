<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can upload a profile avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'first_name' => 'Иван',
        'last_name' => 'Иванов',
        'locale' => 'en',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'patronymic' => '',
            'phone' => '',
            'email' => $user->email,
            'locale' => 'en',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull()
        ->and($user->avatar)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar_path);
});

test('user can replace and remove a profile avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'first_name' => 'Иван',
        'last_name' => 'Иванов',
        'locale' => 'en',
    ]);

    $oldPath = UploadedFile::fake()->image('old.png')->store('avatars', 'public');
    $user->forceFill(['avatar_path' => $oldPath])->save();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => $user->email,
            'locale' => 'en',
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($user->avatar_path);

    $currentPath = $user->avatar_path;

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => $user->email,
            'locale' => 'en',
            'remove_avatar' => '1',
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->toBeNull()
        ->and($user->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($currentPath);
});
