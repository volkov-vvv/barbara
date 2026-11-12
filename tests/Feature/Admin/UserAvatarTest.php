<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can create a user with an avatar', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'first_name' => 'Анна',
            'last_name' => 'Смирнова',
            'email' => 'anna@example.com',
            'password' => 'password',
            'role' => UserRole::Student->value,
            'avatar' => UploadedFile::fake()->image('anna.jpg'),
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'anna@example.com')->firstOrFail();

    expect($user->avatar_path)->not->toBeNull()
        ->and($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('admin can remove a user avatar', function (): void {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $user = User::factory()->student()->create();
    $path = UploadedFile::fake()->image('old.jpg')->store('avatars', 'public');
    $user->forceFill(['avatar_path' => $path])->save();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => UserRole::Student->value,
            'remove_avatar' => '1',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
