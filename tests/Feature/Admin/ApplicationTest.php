<?php

declare(strict_types=1);

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view applications index', function (): void {
    $admin = User::factory()->admin()->create();

    Application::query()->create([
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (999) 123-45-67',
        'region' => 'Москва',
        'document_path' => 'applications/demo.pdf',
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
        'comment' => 'Тест',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Applications')
            ->has('applications.data', 1)
            ->has('filters')
            ->has('sort')
            ->where('sort.column', 'created_at')
            ->where('sort.direction', 'desc')
            ->has('columns')
            ->has('regions')
            ->has('courses')
            ->has('levels'));
});

test('admin can sort applications by email ascending', function (): void {
    $admin = User::factory()->admin()->create();

    Application::factory()->create([
        'email' => 'zeta@example.com',
        'full_name' => 'Zeta User',
    ]);
    Application::factory()->create([
        'email' => 'alpha@example.com',
        'full_name' => 'Alpha User',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.index', [
            'sort' => 'email',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Applications')
            ->has('applications.data', 2)
            ->where('applications.data.0.email', 'alpha@example.com')
            ->where('applications.data.1.email', 'zeta@example.com')
            ->where('sort.column', 'email')
            ->where('sort.direction', 'asc'));
});

test('admin cannot sort by invalid column', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.applications.index', [
            'sort' => 'password',
            'direction' => 'asc',
        ]))
        ->assertSessionHasErrors('sort');
});

test('admin can filter applications by course and search', function (): void {
    $admin = User::factory()->admin()->create();

    Application::query()->create([
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (999) 123-45-67',
        'region' => 'Москва',
        'document_path' => 'applications/demo-1.pdf',
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
        'comment' => null,
    ]);

    Application::query()->create([
        'full_name' => 'Петров Пётр Петрович',
        'email' => 'petrov@example.com',
        'phone' => '+7 (900) 111-22-33',
        'region' => 'Санкт-Петербург',
        'document_path' => 'applications/demo-2.pdf',
        'course' => 'QA-инженер',
        'level' => 'Junior',
        'comment' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.index', [
            'course' => 'QA-инженер',
            'search' => 'Петров',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Applications')
            ->has('applications.data', 1)
            ->where('applications.data.0.email', 'petrov@example.com')
            ->where('filters.course', 'QA-инженер')
            ->where('filters.search', 'Петров'));
});

test('admin can view application details', function (): void {
    $admin = User::factory()->admin()->create();

    $application = Application::query()->create([
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (999) 123-45-67',
        'region' => 'Москва',
        'document_path' => 'applications/demo.pdf',
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
        'comment' => 'Комментарий',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ApplicationShow')
            ->where('application.email', 'ivanov@example.com')
            ->where('application.comment', 'Комментарий'));
});

test('admin can download application document', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $path = 'applications/demo.pdf';
    Storage::disk('local')->put($path, 'pdf-content');

    $application = Application::query()->create([
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'ivanov@example.com',
        'phone' => '+7 (999) 123-45-67',
        'region' => 'Москва',
        'document_path' => $path,
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
        'comment' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.applications.download', $application))
        ->assertOk();
});

test('admin can update an application', function (): void {
    $admin = User::factory()->admin()->create();

    $application = Application::factory()->create([
        'email' => 'old@example.com',
        'course' => 'Frontend-разработчик',
        'level' => 'Новичок',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.applications.index'))
        ->put(route('admin.applications.update', $application), [
            'full_name' => 'Новое Имя',
            'email' => 'new@example.com',
            'phone' => '+7 (999) 111-22-33',
            'region' => 'Санкт-Петербург',
            'course' => 'QA-инженер',
            'level' => 'Junior',
            'comment' => 'Обновлено',
        ])
        ->assertRedirect(route('admin.applications.index'));

    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'full_name' => 'Новое Имя',
        'email' => 'new@example.com',
        'course' => 'QA-инженер',
        'level' => 'Junior',
        'comment' => 'Обновлено',
    ]);
});

test('admin cannot update application to duplicate email', function (): void {
    $admin = User::factory()->admin()->create();

    Application::factory()->create(['email' => 'taken@example.com']);
    $application = Application::factory()->create([
        'email' => 'free@example.com',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.applications.index'))
        ->put(route('admin.applications.update', $application), [
            'full_name' => $application->full_name,
            'email' => 'taken@example.com',
            'phone' => $application->phone,
            'region' => $application->region,
            'course' => $application->course,
            'level' => $application->level,
            'comment' => null,
        ])
        ->assertRedirect(route('admin.applications.index'))
        ->assertSessionHasErrors('email');
});

test('admin can delete an application', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $path = 'applications/to-delete.pdf';
    Storage::disk('local')->put($path, 'pdf-content');

    $application = Application::factory()->create([
        'document_path' => $path,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.applications.destroy', $application))
        ->assertRedirect(route('admin.applications.index'));

    $this->assertDatabaseMissing('applications', [
        'id' => $application->id,
    ]);

    Storage::disk('local')->assertMissing($path);
});

test('admin can export applications to excel with filters', function (): void {
    $admin = User::factory()->admin()->create();

    Application::factory()->create([
        'email' => 'frontend@example.com',
        'course' => 'Frontend-разработчик',
    ]);
    Application::factory()->create([
        'email' => 'qa@example.com',
        'course' => 'QA-инженер',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.applications.export.excel', [
            'course' => 'QA-инженер',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');
});

test('admin can export applications to pdf with filters', function (): void {
    $admin = User::factory()->admin()->create();

    Application::factory()->create([
        'email' => 'frontend@example.com',
        'course' => 'Frontend-разработчик',
        'region' => 'Москва',
    ]);
    Application::factory()->create([
        'email' => 'spb@example.com',
        'course' => 'Backend-разработчик',
        'region' => 'Санкт-Петербург',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.applications.export.pdf', [
            'region' => 'Москва',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('student cannot access admin applications', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('admin.applications.index'))
        ->assertForbidden();
});

test('guest cannot access admin applications', function (): void {
    $this->get(route('admin.applications.index'))
        ->assertRedirect();
});
