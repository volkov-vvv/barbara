<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreWordSetRequest;
use App\Http\Requests\Student\UpdateWordSetRequest;
use App\Http\Requests\Student\ViewWordSetRequest;
use App\Models\User;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WordSetController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $wordSets = WordSet::query()
            ->with('language:id,code,name')
            ->withCount('words')
            ->where('created_by', $user->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('student/WordSets', [
            'wordSets' => $wordSets,
            'languages' => $this->languagesPayload(),
        ]);
    }

    public function show(ViewWordSetRequest $request, WordSet $wordSet): Response
    {
        $wordSet->load(['language:id,code,name']);
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        $words = $this->sortedWordsQuery($wordSet, $filters)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Word $word): array => $this->wordToArray($word));

        return Inertia::render('student/WordSetShow', [
            'wordSet' => [
                'id' => $wordSet->id,
                'title' => $wordSet->title,
                'description' => $wordSet->description,
                'language_id' => $wordSet->language_id,
                'language' => $wordSet->language
                    ? [
                        'id' => $wordSet->language->id,
                        'code' => $wordSet->language->code,
                        'name' => $wordSet->language->name,
                    ]
                    : null,
            ],
            'words' => $words,
            'languages' => $this->languagesPayload(),
            'filters' => [
                'search' => $filters['search'] ?? null,
                'has_example' => $filters['has_example'] ?? null,
            ],
            'sort' => [
                'column' => $sort,
                'direction' => $direction,
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'id', 'default' => true, 'sortable' => true],
                ['key' => 'image', 'label' => 'image', 'default' => true, 'sortable' => false],
                ['key' => 'text', 'label' => 'text', 'default' => true, 'sortable' => true],
                ['key' => 'translation', 'label' => 'translation', 'default' => true, 'sortable' => true],
                ['key' => 'example_sentence', 'label' => 'example', 'default' => true, 'sortable' => true],
                ['key' => 'created_at', 'label' => 'created_at', 'default' => false, 'sortable' => true],
            ],
        ]);
    }

    public function store(StoreWordSetRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $wordSet = WordSet::query()->create([
            ...$request->validated(),
            'created_by' => $user->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set created.')]);

        return to_route('student.word-sets.show', $wordSet);
    }

    public function update(UpdateWordSetRequest $request, WordSet $wordSet): RedirectResponse
    {
        $wordSet->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set updated.')]);

        return to_route('student.word-sets.show', $wordSet);
    }

    public function destroy(Request $request, WordSet $wordSet): RedirectResponse
    {
        $this->ensureOwned($request, $wordSet);

        $wordSet->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set deleted.')]);

        return to_route('student.word-sets.index');
    }

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    private function languagesPayload(): array
    {
        return $this->dictionaryCache->languages()
            ->map(fn (array $language): array => [
                'id' => $language['id'],
                'code' => $language['code'],
                'name' => $language['name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     has_example?: string|null,
     *     sort?: string|null,
     *     direction?: string|null
     * }  $filters
     * @return Builder<Word>
     */
    private function sortedWordsQuery(WordSet $wordSet, array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        return $this->filteredWordsQuery($wordSet, $filters)
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     has_example?: string|null,
     *     sort?: string|null,
     *     direction?: string|null
     * }  $filters
     * @return Builder<Word>
     */
    private function filteredWordsQuery(WordSet $wordSet, array $filters): Builder
    {
        return $wordSet->words()
            ->getQuery()
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $search): void {
                    $query->where(function (Builder $inner) use ($search): void {
                        $inner->where('text', 'like', "%{$search}%")
                            ->orWhere('translation', 'like', "%{$search}%")
                            ->orWhere('example_sentence', 'like', "%{$search}%");
                    });
                },
            )
            ->when(
                ($filters['has_example'] ?? null) === '1',
                fn (Builder $query) => $query->where('example_sentence', '!=', ''),
            )
            ->when(
                ($filters['has_example'] ?? null) === '0',
                fn (Builder $query) => $query->where(function (Builder $inner): void {
                    $inner->whereNull('example_sentence')
                        ->orWhere('example_sentence', '');
                }),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function wordToArray(Word $word): array
    {
        $imageUrl = null;

        if (is_string($word->image_path) && $word->image_path !== '') {
            $imageUrl = Storage::disk('public')->url($word->image_path);
        }

        return [
            'id' => $word->id,
            'text' => $word->text,
            'translation' => $word->translation,
            'example_sentence' => $word->example_sentence,
            'audio_url' => $word->audio_url,
            'image_path' => $word->image_path,
            'image_url' => $imageUrl,
            'created_at' => $word->created_at?->toIso8601String(),
        ];
    }

    private function ensureOwned(Request $request, WordSet $wordSet): void
    {
        abort_unless($wordSet->created_by === $request->user()?->id, 403);
    }
}
