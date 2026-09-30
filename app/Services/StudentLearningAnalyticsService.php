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

        $reviewsTodayByUser = UserWordProgress::query()
            ->whereIn('user_id', $childIds)
            ->whereDate('last_reviewed_at', Carbon::today())
            ->groupBy('user_id')
            ->selectRaw('user_id')
            ->selectRaw('COUNT(*) as reviews')
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

        $from = Carbon::now()->subDays(14)->startOfDay();

        $reviewsByDate = DB::table('user_word_progress')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('last_reviewed_at')
            ->where('last_reviewed_at', '>=', $from)
            ->selectRaw('DATE(last_reviewed_at) as date')
            ->selectRaw('COUNT(*) as reviews')
            ->groupByRaw('DATE(last_reviewed_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $sessionStatsByDate = DB::table('review_sessions')
            ->whereIn('user_id', $userIds)
            ->where('started_at', '>=', $from)
            ->selectRaw('DATE(started_at) as date')
            ->selectRaw('SUM(correct_count) as correct')
            ->selectRaw('SUM(wrong_count) as wrong')
            ->groupByRaw('DATE(started_at)')
            ->get()
            ->keyBy('date');

        $dates = $reviewsByDate->keys()
            ->merge($sessionStatsByDate->keys())
            ->unique()
            ->sort()
            ->values();

        return $dates->map(function (string $date) use ($reviewsByDate, $sessionStatsByDate): object {
            $sessionStats = $sessionStatsByDate->get($date);

            return (object) [
                'date' => $date,
                'reviews' => (int) ($reviewsByDate->get($date)->reviews ?? 0),
                'correct' => (int) ($sessionStats->correct ?? 0),
                'wrong' => (int) ($sessionStats->wrong ?? 0),
            ];
        });
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

        $recentSessions = $student->reviewSessions()
            ->latest('started_at')
            ->limit(20)
            ->get(['id', 'started_at', 'finished_at', 'correct_count', 'wrong_count'])
            ->map(fn (ReviewSession $session): array => $this->serializeSession($session))
            ->values()
            ->all();

        $lastSessionModel = $student->reviewSessions()
            ->latest('started_at')
            ->first(['id', 'started_at', 'finished_at', 'correct_count', 'wrong_count']);

        $reviewsToday = UserWordProgress::query()
            ->where('user_id', $student->id)
            ->whereDate('last_reviewed_at', Carbon::today())
            ->count();

        $dailyActivity = $this->dailyActivityForUsers(collect([$student->id]))
            ->map(fn (object $point): array => [
                'date' => (string) $point->date,
                'reviews' => (int) $point->reviews,
                'correct' => (int) $point->correct,
                'wrong' => (int) $point->wrong,
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
            'lastSession' => $this->lastSessionDetail($student, $lastSessionModel),
            'recentSessions' => $recentSessions,
            'dailyActivity' => $dailyActivity,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastSessionDetail(User $student, ?ReviewSession $session): ?array
    {
        if ($session === null) {
            return null;
        }

        $endedAt = $session->finished_at ?? Carbon::now();
        $total = $session->correct_count + $session->wrong_count;

        $reviewedWords = UserWordProgress::query()
            ->with(['word.wordSet:id,title'])
            ->where('user_id', $student->id)
            ->whereNotNull('last_reviewed_at')
            ->where('last_reviewed_at', '>=', $session->started_at)
            ->when(
                $session->finished_at !== null,
                fn ($query) => $query->where('last_reviewed_at', '<=', $session->finished_at),
            )
            ->orderByDesc('last_reviewed_at')
            ->limit(50)
            ->get()
            ->map(function (UserWordProgress $progress): array {
                return [
                    'id' => $progress->id,
                    'word_id' => $progress->word_id,
                    'text' => $progress->word?->text,
                    'translation' => $progress->word?->translation,
                    'word_set_title' => $progress->word?->wordSet?->title,
                    'last_reviewed_at' => $progress->last_reviewed_at?->toIso8601String(),
                    'ease_factor' => round((float) $progress->ease_factor, 2),
                    'interval_days' => $progress->interval_days,
                    'next_review_at' => $progress->next_review_at->toIso8601String(),
                    'repetitions' => $progress->repetitions,
                ];
            })
            ->values()
            ->all();

        return [
            ...$this->serializeSession($session),
            'duration_seconds' => (int) $session->started_at->diffInSeconds($endedAt),
            'accuracy_percent' => $total > 0
                ? round(($session->correct_count / $total) * 100, 1)
                : 0.0,
            'reviewed_words' => $reviewedWords,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     started_at: string,
     *     finished_at: string|null,
     *     correct_count: int,
     *     wrong_count: int,
     *     is_open: bool
     * }
     */
    private function serializeSession(ReviewSession $session): array
    {
        return [
            'id' => $session->id,
            'started_at' => $session->started_at->toIso8601String(),
            'finished_at' => $session->finished_at?->toIso8601String(),
            'correct_count' => $session->correct_count,
            'wrong_count' => $session->wrong_count,
            'is_open' => $session->finished_at === null,
        ];
    }
}
