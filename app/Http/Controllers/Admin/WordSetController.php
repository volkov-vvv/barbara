<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\WordsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportWordSetPdfRequest;
use App\Http\Requests\Admin\StoreWordSetRequest;
use App\Http\Requests\Admin\UpdateWordSetRequest;
use App\Http\Requests\Admin\ViewWordSetRequest;
use App\Models\Theme;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WordSetController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = 20;
        $page = max(1, (int) $request->integer('page', 1));
        $languageId = $request->filled('language_id')
            ? $request->integer('language_id')
            : null;

        $dictionaries = $this->dictionaryCache->dictionaries();

        if ($languageId !== null) {
            $dictionaries = $dictionaries
                ->where('language_id', $languageId)
                ->values();
        }

        $wordSets = new LengthAwarePaginator(
            $dictionaries->forPage($page, $perPage)->values(),
            $dictionaries->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return Inertia::render('admin/WordSets', [
            'wordSets' => $wordSets,
            'languages' => $this->dictionaryCache->languages()
                ->map(fn (array $language): array => [
                    'id' => $language['id'],
                    'code' => $language['code'],
                    'name' => $language['name'],
                ])
                ->values(),
            'filters' => [
                'language_id' => $languageId,
            ],
        ]);
    }

    public function show(WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        return to_route('admin.word-sets.view', $wordSet);
    }

    public function view(ViewWordSetRequest $request, WordSet $wordSet): Response
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->load(['language:id,code,name']);
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        $words = $this->sortedWordsQuery($wordSet, $filters)
            ->with(['themes:id,name'])
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Word $word): array => $this->wordToArray($word));

        return Inertia::render('admin/WordSetView', [
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
            'themes' => Theme::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $filters['search'] ?? null,
                'has_example' => $filters['has_example'] ?? null,
                'theme_id' => isset($filters['theme_id'])
                    ? (string) $filters['theme_id']
                    : null,
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
                ['key' => 'themes', 'label' => 'themes', 'default' => true, 'sortable' => false],
                ['key' => 'created_at', 'label' => 'created_at', 'default' => false, 'sortable' => true],
            ],
        ]);
    }

    public function viewExportExcel(ViewWordSetRequest $request, WordSet $wordSet): BinaryFileResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $filters = $request->validated();
        $slug = Str::slug($wordSet->title);
        $filename = ($slug !== '' ? $slug : 'dictionary').'-words-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new WordsExport($this->sortedWordsQuery($wordSet, $filters)),
            $filename,
        );
    }

    public function viewExportPdf(ViewWordSetRequest $request, WordSet $wordSet): HttpResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->load(['language:id,code,name']);
        $filters = $request->validated();
        $words = $this->sortedWordsQuery($wordSet, $filters)->get();
        $slug = Str::slug($wordSet->title);
        $filename = ($slug !== '' ? $slug : 'dictionary').'-words-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('admin.words-export-pdf', [
            'wordSet' => $wordSet,
            'words' => $words,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function exportOptions(WordSet $wordSet): JsonResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $words = $wordSet->words()
            ->with(['themes:id'])
            ->orderBy('id')
            ->get(['id', 'text', 'translation'])
            ->map(fn (Word $word): array => [
                'id' => $word->id,
                'text' => $word->text,
                'translation' => $word->translation,
                'theme_ids' => $word->themes->pluck('id')->values()->all(),
            ]);

        return response()->json([
            'wordSet' => [
                'id' => $wordSet->id,
                'title' => $wordSet->title,
            ],
            'words' => $words,
        ]);
    }

    public function exportPdf(ExportWordSetPdfRequest $request, WordSet $wordSet): HttpResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->load(['language:id,code,name']);

        /** @var list<int> $wordIds */
        $wordIds = array_values($request->validated('word_ids'));
        $random = $request->boolean('random');
        $wordsOnly = $request->boolean('words_only');
        $translationOnly = $request->boolean('translation_only');
        $withoutExample = $request->boolean('without_example');
        $showExample = ! $withoutExample;

        /** @var Collection<int, Word> $words */
        $words = $wordSet->words()
            ->whereIn('id', $wordIds)
            ->get();

        $order = array_flip($wordIds);
        $words = $words
            ->sortBy(fn (Word $word): int => $order[$word->id] ?? PHP_INT_MAX)
            ->values();

        if ($random) {
            $words = $words->shuffle()->values();
        }

        /** @var list<array{heading: string, fillText: bool, fillTranslation: bool, showExample: bool}> $sections */
        $sections = match (true) {
            $wordsOnly && $translationOnly => [
                [
                    'heading' => 'Слово — перевод',
                    'fillText' => true,
                    'fillTranslation' => true,
                    'showExample' => $showExample,
                ],
                [
                    'heading' => 'Только слова',
                    'fillText' => true,
                    'fillTranslation' => false,
                    'showExample' => false,
                ],
                [
                    'heading' => 'Только перевод',
                    'fillText' => false,
                    'fillTranslation' => true,
                    'showExample' => false,
                ],
            ],
            $wordsOnly => [
                [
                    'heading' => 'Только слова',
                    'fillText' => true,
                    'fillTranslation' => false,
                    'showExample' => false,
                ],
            ],
            $translationOnly => [
                [
                    'heading' => 'Только перевод',
                    'fillText' => false,
                    'fillTranslation' => true,
                    'showExample' => false,
                ],
            ],
            default => [
                [
                    'heading' => 'Слово — перевод',
                    'fillText' => true,
                    'fillTranslation' => true,
                    'showExample' => $showExample,
                ],
            ],
        };

        $slug = Str::slug($wordSet->title);
        $filename = ($slug !== '' ? $slug : 'dictionary').'-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('admin.word-set-export-pdf', [
            'wordSet' => $wordSet,
            'words' => $words,
            'random' => $random,
            'sections' => $sections,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function store(StoreWordSetRequest $request): RedirectResponse
    {
        $wordSet = WordSet::query()->create([
            ...$request->validated(),
            'created_by' => null,
        ]);

        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set created.')]);

        return to_route('admin.word-sets.view', $wordSet);
    }

    public function update(UpdateWordSetRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->update($request->validated());
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set updated.')]);

        return back();
    }

    public function destroy(WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSetId = $wordSet->id;
        $wordSet->delete();
        $this->dictionaryCache->flushDictionary($wordSetId);
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set deleted.')]);

        return to_route('admin.word-sets.index');
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     has_example?: string|null,
     *     theme_id?: int|string|null,
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
     *     theme_id?: int|string|null,
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
            )
            ->when(
                $filters['theme_id'] ?? null,
                fn (Builder $query, int|string $themeId) => $query->whereHas(
                    'themes',
                    fn (Builder $themes) => $themes->where('themes.id', (int) $themeId),
                ),
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
            'themes' => $word->relationLoaded('themes')
                ? $word->themes
                    ->map(fn (Theme $theme): array => [
                        'id' => $theme->id,
                        'name' => $theme->name,
                    ])
                    ->values()
                    ->all()
                : [],
            'created_at' => $word->created_at?->toIso8601String(),
        ];
    }
}
