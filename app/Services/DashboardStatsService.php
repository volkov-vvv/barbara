<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Language;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Support\Carbon;

class DashboardStatsService
{
    /**
     * @return array{
     *     users: array{total: int, admins: int, students: int, parents: int},
     *     applications: array{total: int, today: int, last_7_days: int, recent: list<array{id: int, full_name: string, email: string, course: string, created_at: string|null}>},
     *     dictionaries: array{system_sets: int, personal_sets: int, words: int, languages: int},
     *     progress: array{due_cards: int, students_with_due: int, reviews_today: int, sessions_last_7_days: int, total_correct: int, total_wrong: int}
     * }
     */
    public function forAdmin(): array
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $weekAgo = $now->copy()->subDays(7);

        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $students = (int) ($roleCounts[UserRole::Student->value] ?? 0);
        $parents = (int) ($roleCounts[UserRole::Parent->value] ?? 0);
        $admins = (int) ($roleCounts[UserRole::Admin->value] ?? 0);

        $studentIds = User::query()
            ->where('role', UserRole::Student)
            ->pluck('id');

        $dueCards = UserWordProgress::query()
            ->whereIn('user_id', $studentIds)
            ->where('next_review_at', '<=', $now)
            ->count();

        $studentsWithDue = (int) UserWordProgress::query()
            ->whereIn('user_id', $studentIds)
            ->where('next_review_at', '<=', $now)
            ->selectRaw('COUNT(DISTINCT user_id) as aggregate')
            ->value('aggregate');

        $reviewsToday = UserWordProgress::query()
            ->whereIn('user_id', $studentIds)
            ->whereDate('last_reviewed_at', $today)
            ->count();

        $sessionsLast7Days = ReviewSession::query()
            ->whereIn('user_id', $studentIds)
            ->where('started_at', '>=', $weekAgo)
            ->count();

        $progressTotals = UserWordProgress::query()
            ->whereIn('user_id', $studentIds)
            ->selectRaw('COALESCE(SUM(total_correct), 0) as correct')
            ->selectRaw('COALESCE(SUM(total_wrong), 0) as wrong')
            ->first();

        $recentApplications = Application::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'full_name', 'email', 'course', 'created_at'])
            ->map(fn (Application $application): array => [
                'id' => $application->id,
                'full_name' => $application->full_name,
                'email' => $application->email,
                'course' => $application->course,
                'created_at' => $application->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'users' => [
                'total' => $students + $parents + $admins,
                'admins' => $admins,
                'students' => $students,
                'parents' => $parents,
            ],
            'applications' => [
                'total' => Application::query()->count(),
                'today' => Application::query()->whereDate('created_at', $today)->count(),
                'last_7_days' => Application::query()->where('created_at', '>=', $weekAgo)->count(),
                'recent' => $recentApplications,
            ],
            'dictionaries' => [
                'system_sets' => WordSet::query()->whereNull('created_by')->count(),
                'personal_sets' => WordSet::query()->whereNotNull('created_by')->count(),
                'words' => Word::query()->count(),
                'languages' => Language::query()->count(),
            ],
            'progress' => [
                'due_cards' => $dueCards,
                'students_with_due' => $studentsWithDue,
                'reviews_today' => $reviewsToday,
                'sessions_last_7_days' => $sessionsLast7Days,
                'total_correct' => (int) ($progressTotals->correct ?? 0),
                'total_wrong' => (int) ($progressTotals->wrong ?? 0),
            ],
        ];
    }
}
