<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserWordProgress;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class SpacedRepetitionService
{
    private const float MIN_EASE_FACTOR = 1.3;

    /**
     * Apply an SM-2 review response to the given progress record.
     *
     * Mutates the model in memory; the caller is responsible for persisting it.
     *
     * @param  int  $quality  Self-graded response quality from 0 (blackout) to 5 (perfect).
     */
    public function apply(UserWordProgress $progress, int $quality): UserWordProgress
    {
        if ($quality < 0 || $quality > 5) {
            throw new InvalidArgumentException('Quality must be an integer between 0 and 5.');
        }

        $repetitions = $progress->repetitions;
        $easeFactor = $progress->ease_factor;
        $intervalDays = $progress->interval_days;

        if ($quality < 3) {
            $repetitions = 0;
            $intervalDays = 1;
            $progress->total_wrong++;
        } else {
            $intervalDays = match (true) {
                $repetitions === 0 => 1,
                $repetitions === 1 => 6,
                default => (int) round($intervalDays * $easeFactor),
            };
            $repetitions++;
            $progress->total_correct++;
        }

        $easeFactor = $this->recalculateEaseFactor($easeFactor, $quality);
        $reviewedAt = Carbon::now();

        $progress->repetitions = $repetitions;
        $progress->ease_factor = $easeFactor;
        $progress->interval_days = $intervalDays;
        $progress->last_reviewed_at = $reviewedAt;
        $progress->next_review_at = $reviewedAt->copy()->addDays($intervalDays);

        return $progress;
    }

    private function recalculateEaseFactor(float $easeFactor, int $quality): float
    {
        $delta = 5 - $quality;
        $easeFactor += 0.1 - $delta * (0.08 + $delta * 0.02);

        return max(self::MIN_EASE_FACTOR, $easeFactor);
    }
}
