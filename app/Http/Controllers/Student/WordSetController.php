<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreWordSetRequest;
use App\Http\Requests\Student\UpdateWordSetRequest;
use App\Models\User;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'languages' => $this->dictionaryCache->languages()
                ->map(fn (array $language): array => [
                    'id' => $language['id'],
                    'code' => $language['code'],
                    'name' => $language['name'],
                ])
                ->values(),
        ]);
    }

    public function show(Request $request, WordSet $wordSet): Response
    {
        $this->ensureOwned($request, $wordSet);

        $wordSet->load(['language:id,code,name', 'words']);

        return Inertia::render('student/WordSetShow', [
            'wordSet' => $wordSet,
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

    private function ensureOwned(Request $request, WordSet $wordSet): void
    {
        abort_unless($wordSet->created_by === $request->user()?->id, 403);
    }
}
