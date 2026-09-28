<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreWordRequest;
use App\Http\Requests\Student\UpdateWordRequest;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WordController extends Controller
{
    public function store(StoreWordRequest $request, WordSet $wordSet): RedirectResponse
    {
        $wordSet->words()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word created.')]);

        return to_route('student.word-sets.show', $wordSet);
    }

    public function update(UpdateWordRequest $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($word->word_set_id === $wordSet->id, 404);
        abort_unless($wordSet->created_by === $request->user()?->id, 403);

        $word->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word updated.')]);

        return to_route('student.word-sets.show', $wordSet);
    }

    public function destroy(Request $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($word->word_set_id === $wordSet->id, 404);
        abort_unless($wordSet->created_by === $request->user()?->id, 403);

        $word->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word deleted.')]);

        return to_route('student.word-sets.show', $wordSet);
    }
}
