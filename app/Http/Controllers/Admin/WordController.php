<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportWordsRequest;
use App\Http\Requests\Admin\StoreWordRequest;
use App\Http\Requests\Admin\UpdateWordRequest;
use App\Models\Theme;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use App\Services\WordImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WordController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
        private readonly WordImportService $wordImport,
    ) {}

    public function store(StoreWordRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $validated = $request->validated();
        /** @var list<int> $themeIds */
        $themeIds = array_values($validated['theme_ids'] ?? []);
        unset($validated['theme_ids'], $validated['image']);

        if ($request->hasFile('image')) {
            /** @var UploadedFile $image */
            $image = $request->file('image');
            $validated['image_path'] = $this->storeWordImage($image);
        }

        $word = $wordSet->words()->create($validated);
        $word->themes()->sync($themeIds);
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word created.')]);

        return back();
    }

    public function show(WordSet $wordSet, Word $word): Response
    {
        abort_unless($wordSet->created_by === null, 404);
        abort_unless($word->word_set_id === $wordSet->id, 404);

        $wordSet->load(['language:id,code,name']);
        $word->load(['themes:id,name']);

        return Inertia::render('admin/WordShow', [
            'wordSet' => [
                'id' => $wordSet->id,
                'title' => $wordSet->title,
                'language' => $wordSet->language
                    ? [
                        'id' => $wordSet->language->id,
                        'code' => $wordSet->language->code,
                        'name' => $wordSet->language->name,
                    ]
                    : null,
            ],
            'word' => $this->wordToArray($word),
            'themes' => Theme::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function import(ImportWordsRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        if ($request->hasFile('file')) {
            $path = $request->file('file')?->getRealPath();

            abort_unless(is_string($path) && $path !== '', 422);

            $imported = $this->wordImport->importFromSpreadsheet($wordSet, $path);
        } else {
            $imported = $this->wordImport->importFromText(
                $wordSet,
                (string) $request->validated('words'),
            );
        }

        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Imported :count words.', ['count' => $imported]),
        ]);

        return back();
    }

    public function update(UpdateWordRequest $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);
        abort_unless($word->word_set_id === $wordSet->id, 404);

        $validated = $request->validated();
        /** @var list<int> $themeIds */
        $themeIds = array_values($validated['theme_ids'] ?? []);
        unset($validated['theme_ids'], $validated['image'], $validated['remove_image']);

        if ($request->boolean('remove_image') && is_string($word->image_path) && $word->image_path !== '') {
            $this->deleteWordImage($word->image_path);
            $validated['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            /** @var UploadedFile $image */
            $image = $request->file('image');

            if (is_string($word->image_path) && $word->image_path !== '') {
                $this->deleteWordImage($word->image_path);
            }

            $validated['image_path'] = $this->storeWordImage($image);
        }

        $word->update($validated);
        $word->themes()->sync($themeIds);
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word updated.')]);

        return back();
    }

    public function destroy(WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);
        abort_unless($word->word_set_id === $wordSet->id, 404);

        if (is_string($word->image_path) && $word->image_path !== '') {
            $this->deleteWordImage($word->image_path);
        }

        $word->delete();
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word deleted.')]);

        return to_route('admin.word-sets.view', $wordSet);
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
            'updated_at' => $word->updated_at?->toIso8601String(),
        ];
    }

    private function storeWordImage(UploadedFile $image): string
    {
        $safeName = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '_',
            $image->getClientOriginalName(),
        ) ?: 'image';

        $storedPath = $image->storeAs(
            'words',
            uniqid('word_', true).'_'.$safeName,
            'public',
        );

        abort_unless(is_string($storedPath) && $storedPath !== '', 500);

        return $storedPath;
    }

    private function deleteWordImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
