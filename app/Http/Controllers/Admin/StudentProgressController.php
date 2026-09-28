<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentLearningAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StudentProgressController extends Controller
{
    public function __construct(
        private readonly StudentLearningAnalyticsService $analytics,
    ) {}

    public function index(Request $request): Response
    {
        $students = User::query()
            ->where('role', UserRole::Student)
            ->withCount([
                'wordProgress as due_count' => fn ($query) => $query->where('next_review_at', '<=', Carbon::now()),
            ])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        return Inertia::render('admin/StudentProgress', [
            'students' => $this->analytics->summarizeStudents($students),
            'dailyActivity' => $this->analytics->dailyActivityForUsers($studentIds),
            'weakTopics' => $this->analytics->weakTopicsForUsers($studentIds),
            'filters' => [
                'search' => $request->string('search')->toString() ?: null,
            ],
        ]);
    }

    public function show(User $student): Response
    {
        abort_unless($student->role === UserRole::Student, 404);

        return Inertia::render(
            'admin/StudentProgressShow',
            $this->analytics->detail($student),
        );
    }
}
