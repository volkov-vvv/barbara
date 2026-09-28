<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWordSetRequest;
use App\Http\Requests\Admin\UpdateWordSetRequest;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

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

    public function show(WordSet $wordSet): Response
    {
        abort_unless($wordSet->created_by === null, 404);

        $cached = $this->dictionaryCache->dictionary($wordSet->id);

        return Inertia::render('admin/WordSetShow', [
            'wordSet' => $cached ?? $wordSet->load(['language:id,code,name', 'words']),
        ]);
    }

    public function store(StoreWordSetRequest $request): RedirectResponse
    {
        $wordSet = WordSet::query()->create([
            ...$request->validated(),
            'created_by' => null,
        ]);

        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set created.')]);

        return to_route('admin.word-sets.show', $wordSet);
    }

    public function update(UpdateWordSetRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->update($request->validated());
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word set updated.')]);

        return to_route('admin.word-sets.show', $wordSet);
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
}
