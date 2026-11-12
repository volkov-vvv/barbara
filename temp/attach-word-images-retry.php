<?php

declare(strict_types=1);

use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var array<int, list<string>> $queriesByWordId */
$queriesByWordId = [
    35 => ['14', 'number fourteen'],
    36 => ['15', 'number fifteen'],
    37 => ['16', 'number sixteen'],
    38 => ['17', 'number seventeen'],
    39 => ['18', 'number eighteen'],
    40 => ['19', 'number nineteen'],
    41 => ['20', 'number twenty'],
    42 => ['English language', 'British flag'],
    43 => ['mathematics', 'calculator'],
    44 => ['geography globe', 'world map'],
    45 => ['physical education', 'soccer ball'],
    46 => ['history', 'ancient ruins'],
    47 => ['science', 'microscope'],
    48 => ['art painting', 'paint brushes'],
    49 => ['music', 'piano'],
    50 => ['selfie person', 'me'],
    51 => ['you pointing', 'conversation'],
    52 => ['boy', 'male child'],
    53 => ['girl', 'female child'],
    54 => ['cat', 'kitten'],
    55 => ['we friends', 'children group'],
    56 => ['they people', 'crowd'],
    57 => ['mother', 'mom'],
    58 => ['father', 'dad'],
    59 => ['brother', 'boys siblings'],
    60 => ['sister', 'girls siblings'],
    61 => ['grandmother', 'grandma'],
    62 => ['grandfather', 'grandpa'],
    67 => ['great grandfather', 'old man'],
];

function findOpenverseUrl(string $query): ?string
{
    $response = Http::timeout(20)
        ->withHeaders([
            'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
            'Accept' => 'application/json',
        ])
        ->get('https://api.openverse.org/v1/images/', [
            'q' => $query,
            'page_size' => 5,
            'license' => 'cc0,pdm,by,by-sa',
            'extension' => 'jpg,png',
        ]);

    if (! $response->successful()) {
        return null;
    }

    /** @var list<array{url?: string, thumbnail?: string}> $results */
    $results = $response->json('results') ?? [];

    foreach ($results as $result) {
        $url = (string) ($result['url'] ?? '');

        if ($url !== '') {
            return $url;
        }
    }

    return null;
}

function findCommonsUrl(string $query): ?string
{
    $response = Http::timeout(20)
        ->withHeaders([
            'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
            'Accept' => 'application/json',
        ])
        ->get('https://commons.wikimedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'list' => 'search',
            'srsearch' => $query,
            'srnamespace' => 6,
            'srlimit' => 5,
        ]);

    if (! $response->successful()) {
        return null;
    }

    /** @var list<array{title?: string}> $hits */
    $hits = $response->json('query.search') ?? [];

    foreach ($hits as $hit) {
        $title = (string) ($hit['title'] ?? '');

        if ($title === '') {
            continue;
        }

        $infoResponse = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
            ])
            ->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'format' => 'json',
                'titles' => $title,
                'prop' => 'imageinfo',
                'iiprop' => 'url|mime',
                'iiurlwidth' => 640,
            ]);

        if (! $infoResponse->successful()) {
            continue;
        }

        /** @var array<string, mixed> $pages */
        $pages = $infoResponse->json('query.pages') ?? [];

        foreach ($pages as $page) {
            $info = $page['imageinfo'][0] ?? null;

            if (! is_array($info)) {
                continue;
            }

            $mime = (string) ($info['mime'] ?? '');
            $url = (string) ($info['thumburl'] ?? $info['url'] ?? '');

            if ($url !== '' && str_starts_with($mime, 'image/') && ! str_contains($mime, 'svg')) {
                return $url;
            }
        }
    }

    return null;
}

function downloadImage(string $url): ?array
{
    $response = Http::timeout(40)
        ->withHeaders([
            'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
        ])
        ->withOptions(['allow_redirects' => true])
        ->get($url);

    if (! $response->successful()) {
        return null;
    }

    $bytes = $response->body();

    if ($bytes === '' || strlen($bytes) < 500) {
        return null;
    }

    $contentType = strtolower((string) $response->header('Content-Type'));
    $extension = match (true) {
        str_contains($contentType, 'png') => 'png',
        str_contains($contentType, 'webp') => 'webp',
        str_contains($contentType, 'gif') => 'gif',
        default => 'jpg',
    };

    return [
        'bytes' => $bytes,
        'extension' => $extension,
    ];
}

$wordSet = WordSet::query()->findOrFail(6);
$words = $wordSet->words()
    ->whereNull('image_path')
    ->orderBy('id')
    ->get();

$ok = 0;
$fail = 0;

foreach ($words as $word) {
    /** @var Word $word */
    $queries = $queriesByWordId[$word->id] ?? [$word->translation];

    echo sprintf('[%d] %s... ', $word->id, $word->text);

    $imageUrl = null;

    foreach ($queries as $query) {
        $imageUrl = findOpenverseUrl($query) ?? findCommonsUrl($query);

        if ($imageUrl !== null) {
            echo "query=\"{$query}\" ";
            break;
        }

        usleep(200000);
    }

    if ($imageUrl === null) {
        echo "NO IMAGE\n";
        $fail++;
        usleep(400000);

        continue;
    }

    $downloaded = downloadImage($imageUrl);

    if ($downloaded === null) {
        echo "DOWNLOAD FAIL ({$imageUrl})\n";
        $fail++;
        usleep(400000);

        continue;
    }

    $filename = 'words/word_'.$word->id.'_'.Str::lower(Str::random(8)).'.'.$downloaded['extension'];
    Storage::disk('public')->put($filename, $downloaded['bytes']);
    $word->update(['image_path' => $filename]);
    echo "OK\n";
    $ok++;
    usleep(400000);
}

echo "\nDone. attached={$ok} failed={$fail}\n";
$totalWithImages = $wordSet->words()->whereNotNull('image_path')->count();
echo "Total with images: {$totalWithImages}/{$wordSet->words()->count()}\n";
