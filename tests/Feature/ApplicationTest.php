<?php

declare(strict_types=1);

use App\Models\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('apply page is rendered', function () {
    $this->get(route('apply.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('apply/Create')
            ->has('regions')
            ->has('courses')
            ->has('levels'));
});

test('application can be submitted with a document', function () {
    Storage::fake('local');
    Log::spy();

    $document = UploadedFile::fake()->create('passport.pdf', 512, 'application/pdf');

    $response = $this->from(route('apply.create'))->post(route('apply.store'), [
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (999) 123-45-67',
        'region' => 'Москва',
        'document' => $document,
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
        'comment' => 'Хочу учиться',
        'privacy_consent' => '1',
    ]);

    $response->assertRedirect(route('apply.create'));

    $this->assertDatabaseHas('applications', [
        'email' => 'ivanov@example.com',
        'full_name' => 'Иванов Иван Иванович',
        'course' => 'Frontend-разработчик',
    ]);

    $files = Storage::disk('local')->allFiles('applications');
    expect($files)->not->toBeEmpty();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message): bool => $message === 'Learning application submitted')
        ->once();
});

test('application email must be unique', function () {
    Storage::fake('local');

    Application::query()->create([
        'full_name' => 'Петров Пётр Петрович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (900) 111-22-33',
        'region' => 'Москва',
        'document_path' => 'applications/existing.pdf',
        'course' => 'Backend-разработчик',
        'level' => 'Junior',
        'comment' => null,
    ]);

    $document = UploadedFile::fake()->create('passport.pdf', 512, 'application/pdf');

    $this->from(route('apply.create'))
        ->post(route('apply.store'), [
            'full_name' => 'Иванов Иван Иванович',
            'email' => 'ivanov@example.com',
            'phone' => '+7 (999) 123-45-67',
            'region' => 'Москва',
            'document' => $document,
            'course' => 'Frontend-разработчик',
            'level' => 'Новичок',
            'privacy_consent' => '1',
        ])
        ->assertRedirect(route('apply.create'))
        ->assertSessionHasErrors('email');
});

test('application requires a valid document', function () {
    Storage::fake('local');

    $this->from(route('apply.create'))
        ->post(route('apply.store'), [
            'full_name' => 'Иванов Иван Иванович',
            'email' => 'ivanov@example.com',
            'phone' => '+7 (999) 123-45-67',
            'region' => 'Москва',
            'course' => 'Frontend-разработчик',
            'level' => 'Новичок',
            'privacy_consent' => '1',
        ])
        ->assertRedirect(route('apply.create'))
        ->assertSessionHasErrors('document');
});
