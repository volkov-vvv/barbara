<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\WordSet;
use App\Services\PublicDictionaryCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    config(['cache.public_dictionaries_store' => 'array']);
    Cache::store('array')->flush();
});

test('public dictionaries are served from cache on subsequent reads', function (): void {
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);

    WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Starter set',
        'created_by' => null,
    ]);

    $cache = app(PublicDictionaryCache::class);

    $first = $cache->dictionaries();
    expect($first)->toHaveCount(1)
        ->and($first->first())->toBeArray();

    WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Uncached until flush',
        'description' => 'Should not appear yet',
        'created_by' => null,
    ]);

    expect($cache->dictionaries())->toHaveCount(1);

    $cache->flush();

    expect($cache->dictionaries())->toHaveCount(2)
        ->and($cache->languages())->toHaveCount(1);
});

test('flushing a dictionary refreshes its show payload', function (): void {
    $language = Language::query()->create(['code' => 'fr', 'name' => 'French']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Cafe',
        'description' => 'Food words',
        'created_by' => null,
    ]);

    $cache = app(PublicDictionaryCache::class);
    $cached = $cache->dictionary($wordSet->id);

    expect($cached)->toBeArray()
        ->and($cached['title'])->toBe('Cafe');

    $wordSet->update(['title' => 'Bistro']);
    expect($cache->dictionary($wordSet->id)['title'])->toBe('Cafe');

    $cache->flushDictionary($wordSet->id);
    expect($cache->dictionary($wordSet->id)['title'])->toBe('Bistro');
});
