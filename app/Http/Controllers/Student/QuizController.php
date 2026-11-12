<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RequestMoreQuizCardsRequest;
use App\Http\Requests\Student\StoreQuizAnswerRequest;
use App\Models\ReviewSession;
use App\Models\User;
use App\Models\UserWordProgress;
use App\Models\Word;
use App\Services\QuizDistractorService;
use App\Services\SpacedRepetitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizDistractorService $distractors,
        private readonly SpacedRepetitionService $spacedRepetition,
    ) {}

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

        $cards = $dueCards
            ->filter(fn (UserWordProgress $progress): bool => $progress->word !== null)
            ->map(fn (UserWordProgress $progress): array => $this->cardToArray($progress))
            ->values();

        return Inertia::render('student/QuizCards', [
            'cards' => $cards,
            'dueCount' => $cards->count(),
            'availableNewCount' => $this->availableNewWordsQuery($user)->count(),
        ]);
    }

    public function store(StoreQuizAnswerRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $word = Word::query()->findOrFail($data['word_id']);
        $selectedOptionId = (int) $data['selected_option_id'];
        $isCorrect = $selectedOptionId === $word->id;
        $quality = $isCorrect ? 4 : 1;

        $progress = UserWordProgress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'word_id' => $word->id,
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

        $this->spacedRepetition->apply($progress, $quality);
        $progress->save();

        $this->touchReviewSession($user, $quality);

        return back();
    }

    public function requestMore(RequestMoreQuizCardsRequest $request): RedirectResponse
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

            return to_route('student.quizzes.index');
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

        return to_route('student.quizzes.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function cardToArray(UserWordProgress $progress): array
    {
        /** @var Word $word */
        $word = $progress->word;
        $options = $this->distractors->optionsFor($word);

        $imageUrl = null;

        if (is_string($word->image_path) && $word->image_path !== '') {
            $imageUrl = Storage::disk('public')->url($word->image_path);
        }

        return [
            'id' => $progress->id,
            'word_id' => $progress->word_id,
            'word' => [
                'id' => $word->id,
                'text' => $word->text,
                'translation' => $word->translation,
                'example_sentence' => $word->example_sentence,
                'audio_url' => $word->audio_url,
                'image_url' => $imageUrl,
                'word_set' => $word->wordSet
                    ? [
                        'title' => $word->wordSet->title,
                        'language' => $word->wordSet->language
                            ? [
                                'code' => $word->wordSet->language->code,
                                'name' => $word->wordSet->language->name,
                            ]
                            : null,
                    ]
                    : null,
            ],
            'options' => $options
                ->map(fn (Word $option): array => [
                    'id' => $option->id,
                    'label' => $option->translation,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
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
        $now = Carbon::now();

        $session = ReviewSession::query()
            ->where('user_id', $user->id)
            ->whereNull('finished_at')
            ->latest('started_at')
            ->first();

        if ($session !== null && ! $session->started_at->isSameDay($now)) {
            $session->finished_at = $session->started_at->copy()->endOfDay();
            $session->save();
            $session = null;
        }

        if ($session === null) {
            $session = ReviewSession::query()->create([
                'user_id' => $user->id,
                'started_at' => $now,
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
