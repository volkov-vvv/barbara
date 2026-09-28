<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Language;
use App\Models\WordSet;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PublicDictionaryCache
{
    /**
     * Versioned keys so legacy serialized Eloquent payloads are ignored.
     */
    public const string LANGUAGES_KEY = 'public.dictionaries.v2.languages';

    public const string DICTIONARIES_KEY = 'public.dictionaries.v2.index';

    public const string DICTIONARY_KEY_PREFIX = 'public.dictionaries.v2.show.';

    public const int TTL_SECONDS = 3600;

    public function store(): Repository
    {
        return Cache::store((string) config(
            'cache.public_dictionaries_store',
            config('cache.default', 'database'),
        ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function languages(): Collection
    {
        /** @var list<array<string, mixed>> $languages */
        $languages = $this->store()->remember(
            self::LANGUAGES_KEY,
            self::TTL_SECONDS,
            fn (): array => Language::query()
                ->withCount('wordSets')
                ->orderBy('name')
                ->get()
                ->toArray(),
        );

        return collect($languages);
    }

    /**
     * System dictionaries (created_by is null) with language and word counts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dictionaries(): Collection
    {
        /** @var list<array<string, mixed>> $dictionaries */
        $dictionaries = $this->store()->remember(
            self::DICTIONARIES_KEY,
            self::TTL_SECONDS,
            fn (): array => WordSet::query()
                ->with('language:id,code,name')
                ->withCount('words')
                ->whereNull('created_by')
                ->orderBy('title')
                ->get()
                ->toArray(),
        );

        return collect($dictionaries);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function dictionary(int $wordSetId): ?array
    {
        /** @var array<string, mixed>|null $dictionary */
        $dictionary = $this->store()->remember(
            self::DICTIONARY_KEY_PREFIX.$wordSetId,
            self::TTL_SECONDS,
            function () use ($wordSetId): ?array {
                $wordSet = WordSet::query()
                    ->with(['language:id,code,name', 'words'])
                    ->whereNull('created_by')
                    ->find($wordSetId);

                return $wordSet?->toArray();
            },
        );

        return $dictionary;
    }

    public function flush(): void
    {
        $this->store()->forget(self::LANGUAGES_KEY);
        $this->store()->forget(self::DICTIONARIES_KEY);

        WordSet::query()
            ->whereNull('created_by')
            ->pluck('id')
            ->each(fn (int $id): bool => $this->store()->forget(self::DICTIONARY_KEY_PREFIX.$id));
    }

    public function flushDictionary(int $wordSetId): void
    {
        $this->store()->forget(self::DICTIONARIES_KEY);
        $this->store()->forget(self::DICTIONARY_KEY_PREFIX.$wordSetId);
    }
}
