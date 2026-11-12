<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Иван',
        'last_name' => 'Тестов',
        'patronymic' => 'Иванович',
        'phone' => '+79001112233',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('Иван')
        ->and($user->last_name)->toBe('Тестов')
        ->and($user->patronymic)->toBe('Иванович')
        ->and($user->phone)->toBe('+79001112233')
        ->and($user->name)->toBe('Тестов Иван Иванович');
});
