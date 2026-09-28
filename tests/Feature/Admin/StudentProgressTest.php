<?php

declare(strict_types=1);

use App\Models\User;

test('admin can view student progress index and detail', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.show', $student))
        ->assertOk();
});

test('admin cannot open progress for a non-student user', function (): void {
    $admin = User::factory()->admin()->create();
    $parent = User::factory()->parent()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-progress.show', $parent))
        ->assertNotFound();
});

test('student cannot access admin student progress', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('admin.student-progress.index'))
        ->assertForbidden();
});
