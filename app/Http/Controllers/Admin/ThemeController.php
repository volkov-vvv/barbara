<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreThemeRequest;
use App\Http\Requests\Admin\UpdateThemeRequest;
use App\Models\Theme;
use App\Services\PublicDictionaryCache;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ThemeController extends Controller
{
    public function __construct(
        private readonly PublicDictionaryCache $dictionaryCache,
    ) {}

    public function index(): Response
    {
        $themes = Theme::query()
            ->withCount('words')
            ->orderBy('name')
            ->get()
            ->map(fn (Theme $theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
                'words_count' => $theme->words_count ?? 0,
            ]);

        return Inertia::render('admin/Themes', [
            'themes' => $themes,
        ]);
    }

    public function store(StoreThemeRequest $request): RedirectResponse
    {
        Theme::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme created.')]);

        return to_route('admin.themes.index');
    }

    public function update(UpdateThemeRequest $request, Theme $theme): RedirectResponse
    {
        $theme->update($request->validated());
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme updated.')]);

        return to_route('admin.themes.index');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        $theme->delete();
        $this->dictionaryCache->flush();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme deleted.')]);

        return to_route('admin.themes.index');
    }
}