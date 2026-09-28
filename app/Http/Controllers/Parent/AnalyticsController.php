<?php

declare(strict_types=1);

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\UpdateStudentGoalRequest;
use App\Models\User;
use App\Services\StudentLearningAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly StudentLearningAnalyticsService $analytics,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $parent */
        $parent = $request->user();

        $children = $parent->students()
            ->withCount([
                'wordProgress as due_count' => fn ($query) => $query->where('next_review_at', '<=', Carbon::now()),
            ])
            ->get();

        $childIds = $children->pluck('id');

        return Inertia::render('parent/Dashboard', [
            'children' => $this->analytics->summarizeStudents($children),
            'dailyActivity' => $this->analytics->dailyActivityForUsers($childIds),
            'weakTopics' => $this->analytics->weakTopicsForUsers($childIds),
        ]);
    }

    public function show(Request $request, User $student): Response
    {
        /** @var User $parent */
        $parent = $request->user();

        abort_unless(
            $parent->students()->where('users.id', $student->id)->exists(),
            403,
        );

        return Inertia::render(
            'parent/StudentAnalytics',
            $this->analytics->detail($student),
        );
    }

    public function updateGoal(UpdateStudentGoalRequest $request, User $student): RedirectResponse
    {
        $student->update([
            'daily_word_goal' => $request->validated('daily_word_goal'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Daily goal updated.')]);

        return back();
    }
}
