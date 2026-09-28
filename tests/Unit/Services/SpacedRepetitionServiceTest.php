<?php

declare(strict_types=1);

use App\Models\UserWordProgress;
use App\Services\SpacedRepetitionService;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-28 12:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makeProgress(
    int $repetitions = 0,
    float $easeFactor = 2.5,
    int $intervalDays = 0,
    int $totalCorrect = 0,
    int $totalWrong = 0,
): UserWordProgress {
    return new UserWordProgress([
        'repetitions' => $repetitions,
        'ease_factor' => $easeFactor,
        'interval_days' => $intervalDays,
        'total_correct' => $totalCorrect,
        'total_wrong' => $totalWrong,
        'next_review_at' => now(),
    ]);
}

function service(): SpacedRepetitionService
{
    return new SpacedRepetitionService;
}

test('rejects quality outside the 0-5 range', function (int $quality): void {
    expect(fn () => service()->apply(makeProgress(), $quality))
        ->toThrow(InvalidArgumentException::class);
})->with([-1, 6]);

test('failed review resets repetitions and schedules next day', function (int $quality): void {
    $progress = service()->apply(
        makeProgress(repetitions: 4, easeFactor: 2.5, intervalDays: 15, totalWrong: 1),
        $quality,
    );

    expect($progress->repetitions)->toBe(0)
        ->and($progress->interval_days)->toBe(1)
        ->and($progress->total_wrong)->toBe(2)
        ->and($progress->total_correct)->toBe(0)
        ->and($progress->last_reviewed_at?->equalTo(now()))->toBeTrue()
        ->and($progress->next_review_at->equalTo(now()->addDay()))->toBeTrue();
})->with([0, 1, 2]);

test('first successful review sets interval to 1 day and increments repetitions', function (): void {
    $progress = service()->apply(makeProgress(), 4);

    expect($progress->repetitions)->toBe(1)
        ->and($progress->interval_days)->toBe(1)
        ->and($progress->total_correct)->toBe(1)
        ->and($progress->next_review_at->equalTo(now()->addDay()))->toBeTrue();
});

test('second successful review sets interval to 6 days', function (): void {
    $progress = service()->apply(
        makeProgress(repetitions: 1, intervalDays: 1),
        5,
    );

    expect($progress->repetitions)->toBe(2)
        ->and($progress->interval_days)->toBe(6)
        ->and($progress->next_review_at->equalTo(now()->addDays(6)))->toBeTrue();
});

test('subsequent successful review multiplies previous interval by ease factor', function (): void {
    $progress = service()->apply(
        makeProgress(repetitions: 2, easeFactor: 2.5, intervalDays: 6),
        4,
    );

    expect($progress->repetitions)->toBe(3)
        ->and($progress->interval_days)->toBe(15)
        ->and($progress->next_review_at->equalTo(now()->addDays(15)))->toBeTrue();
});

test('ease factor increases after a perfect response', function (): void {
    $progress = service()->apply(makeProgress(easeFactor: 2.5), 5);

    // EF' = 2.5 + (0.1 - 0 * (...)) = 2.6
    expect($progress->ease_factor)->toEqualWithDelta(2.6, 0.0001);
});

test('ease factor decreases after a poor successful response', function (): void {
    $progress = service()->apply(
        makeProgress(repetitions: 1, easeFactor: 2.5, intervalDays: 1),
        3,
    );

    // delta = 2; EF' = 2.5 + (0.1 - 2 * (0.08 + 2 * 0.02)) = 2.5 + (0.1 - 0.24) = 2.36
    expect($progress->ease_factor)->toEqualWithDelta(2.36, 0.0001);
});

test('ease factor never falls below 1.3', function (): void {
    $progress = service()->apply(
        makeProgress(repetitions: 0, easeFactor: 1.3),
        0,
    );

    expect($progress->ease_factor)->toEqualWithDelta(1.3, 0.0001);
});

test('failed review still recalculates ease factor', function (): void {
    $progress = service()->apply(makeProgress(easeFactor: 2.5), 0);

    // delta = 5; EF' = 2.5 + (0.1 - 5 * (0.08 + 5 * 0.02)) = 2.5 + (0.1 - 0.9) = 1.7
    expect($progress->ease_factor)->toEqualWithDelta(1.7, 0.0001)
        ->and($progress->repetitions)->toBe(0)
        ->and($progress->interval_days)->toBe(1);
});
