<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportWordsRequest;
use App\Http\Requests\Admin\StoreWordRequest;
use App\Http\Requests\Admin\UpdateWordRequest;
use App\Models\Word;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WordController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
    ) {}

    public function store(StoreWordRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $wordSet->words()->create($request->validated());
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word created.')]);

        return to_route('admin.word-sets.show', $wordSet);
    }

    public function import(ImportWordsRequest $request, WordSet $wordSet): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);

        $lines = preg_split('/\r\n|\r|\n/', $request->validated('words')) ?: [];
        $imported = 0;

        DB::transaction(function () use ($lines, $wordSet, &$imported): void {
            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = array_map('trim', explode('|', $line));

                if (count($parts) < 2) {
                    continue;
                }

                $wordSet->words()->create([
                    'text' => $parts[0],
                    'translation' => $parts[1],
                    'example_sentence' => $parts[2] ?? '',
                    'audio_url' => $parts[3] ?? null,
                ]);

                $imported++;
            }
        });

        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Imported :count words.', ['count' => $imported]),
        ]);

        return to_route('admin.word-sets.show', $wordSet);
    }

    public function update(UpdateWordRequest $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);
        abort_unless($word->word_set_id === $wordSet->id, 404);

        $word->update($request->validated());
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word updated.')]);

        return to_route('admin.word-sets.show', $wordSet);
    }

    public function destroy(WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($wordSet->created_by === null, 404);
        abort_unless($word->word_set_id === $wordSet->id, 404);

        $word->delete();
        $this->dictionaryCache->flushDictionary($wordSet->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word deleted.')]);

        return to_route('admin.word-sets.show', $wordSet);
    }
}
