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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WordController extends Controller
{
    public function show(Request $request, WordSet $wordSet, Word $word): Response
    {
        abort_unless($word->word_set_id === $wordSet->id, 404);
        abort_unless($wordSet->created_by === $request->user()?->id, 403);

        $wordSet->load(['language:id,code,name']);

        return Inertia::render('student/WordShow', [
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
        ]);
    }

    public function store(StoreWordRequest $request, WordSet $wordSet): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['image']);

        if ($request->hasFile('image')) {
            /** @var UploadedFile $image */
            $image = $request->file('image');
            $validated['image_path'] = $this->storeWordImage($image);
        }

        $wordSet->words()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word created.')]);

        return to_route('student.word-sets.show', $wordSet);
    }

    public function update(UpdateWordRequest $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($word->word_set_id === $wordSet->id, 404);
        abort_unless($wordSet->created_by === $request->user()?->id, 403);

        $validated = $request->validated();
        unset($validated['image'], $validated['remove_image']);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word updated.')]);

        return back();
    }

    public function destroy(Request $request, WordSet $wordSet, Word $word): RedirectResponse
    {
        abort_unless($word->word_set_id === $wordSet->id, 404);
        abort_unless($wordSet->created_by === $request->user()?->id, 403);

        if (is_string($word->image_path) && $word->image_path !== '') {
            $this->deleteWordImage($word->image_path);
        }

        $word->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Word deleted.')]);

        return to_route('student.word-sets.show', $wordSet);
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
