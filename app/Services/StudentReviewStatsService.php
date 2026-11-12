<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserWordProgress;
use Illuminate\Support\Carbon;

class StudentReviewStatsService
{
    /**
     * Cards at or above the default SM-2 ease factor are treated as easy.
     */
    public const float EASY_EASE_FACTOR = 2.5;

    /**
     * @return array{
     *     total_cards: int,
     *     not_started_cards: int,
     *     easy_cards: int,
     *     hard_cards: int,
     *     last_reviewed_at: string|null
     * }
     */
    public function forUser(User $user): array
    {
        $query = UserWordProgress::query()->where('user_id', $user->id);

        $totalCards = (clone $query)->count();
        $notStartedCards = (clone $query)
            ->whereNull('last_reviewed_at')
            ->count();
        $startedQuery = (clone $query)->whereNotNull('last_reviewed_at');
        $easyCards = (clone $startedQuery)
            ->where('ease_factor', '>=', self::EASY_EASE_FACTOR)
            ->count();
        $hardCards = (clone $startedQuery)
            ->where('ease_factor', '<', self::EASY_EASE_FACTOR)
            ->count();

        $lastReviewedAt = (clone $query)
            ->whereNotNull('last_reviewed_at')
            ->max('last_reviewed_at');

        return [
            'total_cards' => $totalCards,
            'not_started_cards' => $notStartedCards,
            'easy_cards' => $easyCards,
            'hard_cards' => $hardCards,
            'last_reviewed_at' => $lastReviewedAt !== null
                ? Carbon::parse((string) $lastReviewedAt)->toIso8601String()
                : null,
        ];
    }
}
