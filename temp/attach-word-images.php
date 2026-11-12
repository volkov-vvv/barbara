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

/** @var array<int, string> $searchByWordId */
$searchByWordId = [
    25 => 'school backpack',
    26 => 'ballpoint pen',
    27 => 'pencil',
    28 => 'eraser rubber',
    29 => 'ruler measuring',
    30 => 'pencil case',
    31 => 'open book',
    32 => 'number 11',
    33 => 'number 12',
    34 => 'number 13',
    35 => 'number 14',
    36 => 'number 15',
    37 => 'number 16',
    38 => 'number 17',
    39 => 'number 18',
    40 => 'number 19',
    41 => 'number 20',
    42 => 'United Kingdom flag',
    43 => 'mathematics calculator',
    44 => 'world map globe',
    45 => 'children playing sport',
    46 => 'history museum',
    47 => 'nature science',
    48 => 'painting art palette',
    49 => 'musical notes',
    50 => 'person pointing self',
    51 => 'two people talking',
    52 => 'boy child portrait',
    53 => 'girl child portrait',
    54 => 'cat animal',
    55 => 'group of children',
    56 => 'group of people',
    57 => 'mother with child',
    58 => 'father with child',
    59 => 'brother siblings',
    60 => 'sister siblings',
    61 => 'grandmother',
    62 => 'grandfather',
    63 => 'uncle family',
    64 => 'aunt family',
    65 => 'cousins children',
    66 => 'elderly woman grandmother',
    67 => 'elderly man grandfather',
];

function findImageUrl(string $query): ?string
{
    $response = Http::timeout(20)
        ->withHeaders([
            'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
            'Accept' => 'application/json',
        ])
        ->get('https://commons.wikimedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'generator' => 'search',
            'gsrsearch' => $query.' filetype:bitmap',
            'gsrnamespace' => 6,
            'gsrlimit' => 8,
            'prop' => 'imageinfo',
            'iiprop' => 'url|mime|size',
            'iiurlwidth' => 640,
        ]);

    if (! $response->successful()) {
        return null;
    }

    /** @var array<string, mixed> $pages */
    $pages = $response->json('query.pages') ?? [];

    foreach ($pages as $page) {
        $info = $page['imageinfo'][0] ?? null;

        if (! is_array($info)) {
            continue;
        }

        $mime = (string) ($info['mime'] ?? '');
        $url = (string) ($info['thumburl'] ?? $info['url'] ?? '');

        if ($url === '' || ! str_starts_with($mime, 'image/')) {
            continue;
        }

        if (str_contains($mime, 'svg')) {
            continue;
        }

        return $url;
    }

    return null;
}

function downloadImage(string $url): ?array
{
    $response = Http::timeout(30)
        ->withHeaders([
            'User-Agent' => 'BarbaraWordImages/1.0 (educational vocabulary app)',
        ])
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
$words = $wordSet->words()->orderBy('id')->get();

$ok = 0;
$fail = 0;

foreach ($words as $word) {
    /** @var Word $word */
    $query = $searchByWordId[$word->id] ?? $word->translation;

    echo sprintf('[%d] %s / %s → "%s"... ', $word->id, $word->text, $word->translation, $query);

    $imageUrl = findImageUrl($query);

    if ($imageUrl === null) {
        echo "NO IMAGE\n";
        $fail++;
        usleep(250000);

        continue;
    }

    $downloaded = downloadImage($imageUrl);

    if ($downloaded === null) {
        echo "DOWNLOAD FAIL\n";
        $fail++;
        usleep(250000);

        continue;
    }

    if (is_string($word->image_path) && $word->image_path !== '') {
        Storage::disk('public')->delete($word->image_path);
    }

    $filename = 'words/word_'.$word->id.'_'.Str::lower(Str::random(8)).'.'.$downloaded['extension'];
    Storage::disk('public')->put($filename, $downloaded['bytes']);

    $word->update(['image_path' => $filename]);
    echo "OK ({$filename})\n";
    $ok++;

    usleep(300000);
}

echo "\nDone. attached={$ok} failed={$fail}\n";
