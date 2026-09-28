<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RequestMoreCardsRequest;
use App\Http\Requests\Student\StoreReviewAnswerRequest;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Services\SpacedRepetitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $dueCards = UserWordProgress::query()
            ->with(['word.wordSet.language'])
            ->where('user_id', $user->id)
            ->where('next_review_at', '<=', Carbon::now())
            ->orderBy('next_review_at')
            ->limit(50)
            ->get();

        return Inertia::render('student/ReviewCards', [
            'cards' => $dueCards,
            'dueCount' => $dueCards->count(),
            'availableNewCount' => $this->availableNewWordsQuery($user)->count(),
        ]);
    }

    public function store(
        StoreReviewAnswerRequest $request,
        SpacedRepetitionService $spacedRepetition,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        Word::query()->findOrFail($data['word_id']);

        $progress = UserWordProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'word_id' => $data['word_id'],
            ],
            [
                'repetitions' => 0,
                'ease_factor' => 2.5,
                'interval_days' => 0,
                'next_review_at' => Carbon::now(),
                'total_correct' => 0,
                'total_wrong' => 0,
            ],
        );

        $spacedRepetition->apply($progress, (int) $data['quality']);
        $progress->save();

        $this->touchReviewSession($user, (int) $data['quality']);

        return back();
    }

    public function requestMore(RequestMoreCardsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $count = (int) ($request->validated('count') ?? 10);

        $wordIds = $this->availableNewWordsQuery($user)
            ->inRandomOrder()
            ->limit($count)
            ->pluck('words.id');

        if ($wordIds->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('No more new cards available.'),
            ]);

            return to_route('student.reviews.index');
        }

        $now = Carbon::now();

        DB::transaction(function () use ($user, $wordIds, $now): void {
            foreach ($wordIds as $wordId) {
                UserWordProgress::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'word_id' => (int) $wordId,
                    ],
                    [
                        'repetitions' => 0,
                        'ease_factor' => 2.5,
                        'interval_days' => 0,
                        'next_review_at' => $now,
                        'total_correct' => 0,
                        'total_wrong' => 0,
                    ],
                );
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Added :count new cards to your queue.', [
                'count' => $wordIds->count(),
            ]),
        ]);

        return to_route('student.reviews.index');
    }

    /**
     * Words from system dictionaries and the student's own sets
     * that are not yet in the student's SM-2 progress.
     *
     * @return Builder<Word>
     */
    private function availableNewWordsQuery(User $user): Builder
    {
        return Word::query()
            ->whereHas('wordSet', function (Builder $query) use ($user): void {
                $query->where(function (Builder $inner) use ($user): void {
                    $inner->whereNull('created_by')
                        ->orWhere('created_by', $user->id);
                });
            })
            ->whereDoesntHave('progress', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            });
    }

    private function touchReviewSession(User $user, int $quality): void
    {
        $session = ReviewSession::query()
            ->where('user_id', $user->id)
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();

        if ($session === null) {
            $session = ReviewSession::query()->create([
                'user_id' => $user->id,
                'started_at' => Carbon::now(),
                'correct_count' => 0,
                'wrong_count' => 0,
            ]);
        }

        if ($quality < 3) {
            $session->wrong_count++;
        } else {
            $session->correct_count++;
        }

        $session->save();
    }
}
