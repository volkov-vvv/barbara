<?php

declare(strict_types=1);

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'patronymic' => 'Иванович',
            'phone' => '+79001234567',
            'email' => 'test@example.com',
            'locale' => 'ru',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->first_name)->toBe('Иван')
        ->and($user->last_name)->toBe('Иванов')
        ->and($user->patronymic)->toBe('Иванович')
        ->and($user->phone)->toBe('+79001234567')
        ->and($user->name)->toBe('Иванов Иван Иванович')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->locale->value)->toBe('ru');
});

test('profile locale must be supported', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'patronymic' => $user->patronymic,
            'phone' => $user->phone,
            'email' => $user->email,
            'locale' => 'de',
        ]);

    $response
        ->assertSessionHasErrors('locale')
        ->assertRedirect(route('profile.edit'));
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
