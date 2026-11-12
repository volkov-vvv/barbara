<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

test('admin can create a user with personal fields', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'first_name' => 'Мария',
            'last_name' => 'Петрова',
            'patronymic' => 'Сергеевна',
            'phone' => '+79998887766',
            'email' => 'maria@example.com',
            'password' => 'password',
            'role' => UserRole::Student->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'maria@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('Мария')
        ->and($user->last_name)->toBe('Петрова')
        ->and($user->patronymic)->toBe('Сергеевна')
        ->and($user->phone)->toBe('+79998887766')
        ->and($user->name)->toBe('Петрова Мария Сергеевна')
        ->and($user->role)->toBe(UserRole::Student);
});

test('admin can update a user personal fields', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->student()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'patronymic' => null,
        'phone' => null,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'first_name' => 'Алексей',
            'last_name' => 'Сидоров',
            'patronymic' => 'Петрович',
            'phone' => '+71112223344',
            'email' => $user->email,
            'role' => UserRole::Student->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->first_name)->toBe('Алексей')
        ->and($user->last_name)->toBe('Сидоров')
        ->and($user->patronymic)->toBe('Петрович')
        ->and($user->phone)->toBe('+71112223344')
        ->and($user->name)->toBe('Сидоров Алексей Петрович');
});
