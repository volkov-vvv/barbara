<?php

declare(strict_types=1);

use App\Models\User;

test('admin can access admin users index', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('student cannot access admin users index', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('student can access reviews index', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('student.reviews.index'))
        ->assertOk();
});

test('parent cannot access student reviews', function (): void {
    $parent = User::factory()->parent()->create();

    $this->actingAs($parent)
        ->get(route('student.reviews.index'))
        ->assertForbidden();
});

test('parent can access analytics for linked student only', function (): void {
    $parent = User::factory()->parent()->create();
    $linked = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    $parent->students()->attach($linked->id, ['relation' => 'mother']);

    $this->actingAs($parent)
        ->get(route('parent.analytics.index'))
        ->assertOk();

    $this->actingAs($parent)
        ->get(route('parent.analytics.show', $linked))
        ->assertOk();

    $this->actingAs($parent)
        ->get(route('parent.analytics.show', $other))
        ->assertForbidden();
});

test('admin can create a system language', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.languages.store'), [
            'code' => 'en',
            'name' => 'English',
        ])
        ->assertRedirect(route('admin.languages.index'));

    $this->assertDatabaseHas('languages', [
        'code' => 'en',
        'name' => 'English',
    ]);
});

test('admin can link parent and student', function (): void {
    $admin = User::factory()->admin()->create();
    $parent = User::factory()->parent()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)
        ->post(route('admin.parent-students.store'), [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relation' => 'father',
        ])
        ->assertRedirect(route('admin.parent-students.index'));

    expect($parent->fresh()->students()->where('users.id', $student->id)->exists())->toBeTrue();
});
