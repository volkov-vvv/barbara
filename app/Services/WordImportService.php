<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WordSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WordImportService
{
    private const string PREFERRED_SHEET = 'шаблон для сайта';

    /**
     * Russian template columns are inverted vs app fields:
     * «Перевод» holds the foreign word (text), «Слово» holds the native gloss.
     *
     * @var list<string>
     */
    private const array TEXT_HEADERS = ['перевод', 'word', 'text', 'foreign', 'foreign word'];

    /**
     * @var list<string>
     */
    private const array TRANSLATION_HEADERS = ['слово', 'translation', 'meaning', 'native'];

    /**
     * @var list<string>
     */
    private const array EXAMPLE_HEADERS = [
        'пример',
        'example',
        'example_sentence',
        'example sentence',
        'sentence',
    ];

    public function importFromText(WordSet $wordSet, string $raw): int
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }

            $rows[] = [
                'text' => $parts[0],
                'translation' => $parts[1],
                'example_sentence' => $parts[2] ?? '',
                'audio_url' => ($parts[3] ?? null) !== '' ? ($parts[3] ?? null) : null,
            ];
        }

        return $this->persist($wordSet, $rows);
    }

    public function importFromSpreadsheet(WordSet $wordSet, string $path): int
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName(self::PREFERRED_SHEET)
            ?? $spreadsheet->getSheet(0);

        return $this->persist($wordSet, $this->parseSheet($sheet));
    }

    /**
     * @return list<array{text: string, translation: string, example_sentence: string, audio_url: null}>
     */
    private function parseSheet(Worksheet $sheet): array
    {
        $matrix = $sheet->toArray(null, true, true, false);

        if ($matrix === []) {
            return [];
        }

        $headerRow = array_shift($matrix);
        $map = $this->resolveColumnMap(is_array($headerRow) ? $headerRow : []);

        if ($map === null) {
            // No recognizable headers: treat first row as data, columns 0/1 as text/translation.
            array_unshift($matrix, is_array($headerRow) ? $headerRow : []);
            $map = ['text' => 0, 'translation' => 1, 'example' => null];
        }

        $rows = [];

        foreach ($matrix as $row) {
            if (! is_array($row)) {
                continue;
            }

            $text = $this->cellString($row, $map['text']);
            $translation = $this->cellString($row, $map['translation']);

            if ($text === '' || $translation === '') {
                continue;
            }

            $rows[] = [
                'text' => $text,
                'translation' => $translation,
                'example_sentence' => $map['example'] === null
                    ? ''
                    : $this->cellString($row, $map['example']),
                'audio_url' => null,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array{text: int, translation: int, example: int|null}|null
     */
    private function resolveColumnMap(array $headerRow): ?array
    {
        $textIndex = null;
        $translationIndex = null;
        $exampleIndex = null;

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader($header);

            if ($normalized === '') {
                continue;
            }

            if ($textIndex === null && in_array($normalized, self::TEXT_HEADERS, true)) {
                $textIndex = (int) $index;

                continue;
            }

            if ($translationIndex === null && in_array($normalized, self::TRANSLATION_HEADERS, true)) {
                $translationIndex = (int) $index;

                continue;
            }

            if ($exampleIndex === null && in_array($normalized, self::EXAMPLE_HEADERS, true)) {
                $exampleIndex = (int) $index;
            }
        }

        if ($textIndex === null || $translationIndex === null) {
            return null;
        }

        return [
            'text' => $textIndex,
            'translation' => $translationIndex,
            'example' => $exampleIndex,
        ];
    }

    private function normalizeHeader(mixed $header): string
    {
        $value = trim((string) $header);

        if ($value === '') {
            return '';
        }

        $value = Str::lower($value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function cellString(array $row, int $index): string
    {
        if (! array_key_exists($index, $row) || $row[$index] === null) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    /**
     * @param  list<array{text: string, translation: string, example_sentence: string, audio_url: ?string}>  $rows
     */
    private function persist(WordSet $wordSet, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $existing = $wordSet->words()
            ->pluck('text')
            ->map(fn (string $text): string => mb_strtolower(trim($text)))
            ->all();

        $existingLookup = array_fill_keys($existing, true);
        $imported = 0;

        DB::transaction(function () use ($wordSet, $rows, &$existingLookup, &$imported): void {
            foreach ($rows as $row) {
                $key = mb_strtolower($row['text']);

                if (isset($existingLookup[$key])) {
                    continue;
                }

                $wordSet->words()->create([
                    'text' => $row['text'],
                    'translation' => $row['translation'],
                    'example_sentence' => $row['example_sentence'],
                    'audio_url' => $row['audio_url'],
                ]);

                $existingLookup[$key] = true;
                $imported++;
            }
        });

        return $imported;
    }
}
