<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\Language;
use App\Services\PublicDictionaryCache;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/Languages', [
            'languages' => $this->dictionaryCache->languages(),
        ]);
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        Language::query()->create($request->validated());
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language created.')]);

        return to_route('admin.languages.index');
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $language->update($request->validated());
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.')]);

        return to_route('admin.languages.index');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language deleted.')]);

        return to_route('admin.languages.index');
    }
}
