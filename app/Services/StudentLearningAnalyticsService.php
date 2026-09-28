<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentLearningAnalyticsService
{
    /**
     * @param  Collection<int, User>  $students
     * @return array<int, array<string, mixed>>
     */
    public function summarizeStudents(Collection $students): array
    {
        $childIds = $students->pluck('id');

        $totalsByUser = UserWordProgress::query()
            ->whereIn('user_id', $childIds)
            ->groupBy('user_id')
            ->selectRaw('user_id')
            ->selectRaw('COALESCE(SUM(total_correct), 0) as correct')
            ->selectRaw('COALESCE(SUM(total_wrong), 0) as wrong')
            ->get()
            ->keyBy('user_id');

        $reviewsTodayByUser = ReviewSession::query()
            ->whereIn('user_id', $childIds)
            ->whereDate('started_at', Carbon::today())
            ->groupBy('user_id')
            ->selectRaw('user_id')
            ->selectRaw('COALESCE(SUM(correct_count + wrong_count), 0) as reviews')
            ->get()
            ->keyBy('user_id');

        $sessionsWeekByUser = ReviewSession::query()
            ->whereIn('user_id', $childIds)
            ->where('started_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as sessions_count')
            ->get()
            ->keyBy('user_id');

        return $students->map(function (User $student) use ($totalsByUser, $reviewsTodayByUser, $sessionsWeekByUser): array {
            $totals = $totalsByUser->get($student->id);

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'relation' => $student->getRelationValue('pivot')?->relation,
                'due_count' => (int) ($student->getAttribute('due_count') ?? 0),
                'total_correct' => (int) ($totals->correct ?? 0),
                'total_wrong' => (int) ($totals->wrong ?? 0),
                'daily_word_goal' => $student->daily_word_goal,
                'reviews_today' => (int) ($reviewsTodayByUser->get($student->id)->reviews ?? 0),
                'sessions_last_7_days' => (int) ($sessionsWeekByUser->get($student->id)->sessions_count ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, mixed>  $userIds
     * @return Collection<int, mixed>
     */
    public function dailyActivityForUsers(Collection $userIds): Collection
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('review_sessions')
            ->whereIn('user_id', $userIds)
            ->where('started_at', '>=', Carbon::now()->subDays(14))
            ->selectRaw('DATE(started_at) as date')
            ->selectRaw('SUM(correct_count) as correct')
            ->selectRaw('SUM(wrong_count) as wrong')
            ->selectRaw('SUM(correct_count + wrong_count) as reviews')
            ->groupByRaw('DATE(started_at)')
            ->orderBy('date')
            ->get();
    }

    /**
     * @param  Collection<int, mixed>  $userIds
     * @return Collection<int, mixed>
     */
    public function weakTopicsForUsers(Collection $userIds, int $limit = 8): Collection
    {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('user_word_progress')
            ->join('words', 'words.id', '=', 'user_word_progress.word_id')
            ->join('word_sets', 'word_sets.id', '=', 'words.word_set_id')
            ->whereIn('user_word_progress.user_id', $userIds)
            ->groupBy('word_sets.id', 'word_sets.title')
            ->select([
                'word_sets.id',
                'word_sets.title',
                DB::raw('SUM(user_word_progress.total_wrong) as total_wrong'),
                DB::raw('SUM(user_word_progress.total_correct) as total_correct'),
            ])
            ->havingRaw('SUM(user_word_progress.total_wrong) > 0')
            ->orderByDesc('total_wrong')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $student): array
    {
        $performance = UserWordProgress::query()
            ->where('user_id', $student->id)
            ->selectRaw('COUNT(*) as tracked_words')
            ->selectRaw('COALESCE(SUM(total_correct), 0) as total_correct')
            ->selectRaw('COALESCE(SUM(total_wrong), 0) as total_wrong')
            ->selectRaw('COALESCE(AVG(ease_factor), 0) as avg_ease_factor')
            ->first();

        $sessions = $student->reviewSessions()
            ->where('started_at', '>=', Carbon::now()->subDays(14))
            ->latest('started_at')
            ->get(['id', 'started_at', 'finished_at', 'correct_count', 'wrong_count']);

        $recentSessions = $student->reviewSessions()
            ->latest('started_at')
            ->limit(20)
            ->get(['id', 'started_at', 'finished_at', 'correct_count', 'wrong_count'])
            ->map(fn (ReviewSession $session): array => [
                'id' => $session->id,
                'started_at' => $session->started_at->toIso8601String(),
                'finished_at' => $session->finished_at?->toIso8601String(),
                'correct_count' => $session->correct_count,
                'wrong_count' => $session->wrong_count,
                'is_open' => $session->finished_at === null,
            ])
            ->values()
            ->all();

        $reviewsToday = $sessions
            ->filter(fn (ReviewSession $session): bool => $session->started_at->isToday())
            ->sum(fn (ReviewSession $session): int => $session->correct_count + $session->wrong_count);

        /** @var array<int, array{date: string, correct: int|float, wrong: int|float, reviews: int|float}> $dailyActivity */
        $dailyActivity = $sessions
            ->groupBy(fn (ReviewSession $session): string => $session->started_at->toDateString())
            ->map(fn ($daySessions, string $date): array => [
                'date' => $date,
                'correct' => $daySessions->sum('correct_count'),
                'wrong' => $daySessions->sum('wrong_count'),
                'reviews' => $daySessions->sum(fn (ReviewSession $s): int => $s->correct_count + $s->wrong_count),
            ])
            ->values()
            ->all();

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'daily_word_goal' => $student->daily_word_goal,
                'reviews_today' => (int) $reviewsToday,
            ],
            'performance' => [
                'tracked_words' => (int) ($performance->tracked_words ?? 0),
                'total_correct' => (int) ($performance->total_correct ?? 0),
                'total_wrong' => (int) ($performance->total_wrong ?? 0),
                'avg_ease_factor' => round((float) ($performance->avg_ease_factor ?? 0), 2),
            ],
            'weakTopics' => $this->weakTopicsForUsers(collect([$student->id]), 10),
            'recentSessions' => $recentSessions,
            'dailyActivity' => $dailyActivity,
        ];
    }
}
