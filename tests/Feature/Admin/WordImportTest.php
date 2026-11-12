<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Models\Word;
use App\Models\WordSet;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function makeWordsSpreadsheet(string $path, string $sheetName = 'шаблон для сайта'): void
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetName);
    $sheet->fromArray([
        ['Слово', 'Перевод', 'Пример'],
        ['привет', 'hello', 'Hello!'],
        ['книга', 'book', ''],
        ['', '', ''],
        ['ручка', 'pen', 'A blue pen'],
    ], null, 'A1');

    (new Xlsx($spreadsheet))->save($path);
}

test('admin can import words from excel file', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'Starter words',
        'created_by' => null,
    ]);

    $path = tempnam(sys_get_temp_dir(), 'words');
    expect($path)->not->toBeFalse();
    $xlsxPath = $path.'.xlsx';
    rename($path, $xlsxPath);
    makeWordsSpreadsheet($xlsxPath);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.import', $wordSet), [
            'file' => new UploadedFile(
                $xlsxPath,
                'words.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    expect(Word::query()->where('word_set_id', $wordSet->id)->count())->toBe(3)
        ->and(Word::query()->where([
            'word_set_id' => $wordSet->id,
            'text' => 'hello',
            'translation' => 'привет',
            'example_sentence' => 'Hello!',
        ])->exists())->toBeTrue()
        ->and(Word::query()->where([
            'word_set_id' => $wordSet->id,
            'text' => 'book',
            'translation' => 'книга',
            'example_sentence' => '',
        ])->exists())->toBeTrue();

    @unlink($xlsxPath);
});

test('excel import skips duplicate words by text', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Grade 3',
        'description' => 'Starter words',
        'created_by' => null,
    ]);

    $wordSet->words()->create([
        'text' => 'Hello',
        'translation' => 'old',
        'example_sentence' => '',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'words');
    expect($path)->not->toBeFalse();
    $xlsxPath = $path.'.xlsx';
    rename($path, $xlsxPath);
    makeWordsSpreadsheet($xlsxPath);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.import', $wordSet), [
            'file' => new UploadedFile(
                $xlsxPath,
                'words.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    expect(Word::query()->where('word_set_id', $wordSet->id)->count())->toBe(3)
        ->and(Word::query()->where([
            'word_set_id' => $wordSet->id,
            'text' => 'Hello',
            'translation' => 'old',
        ])->exists())->toBeTrue();

    @unlink($xlsxPath);
});

test('admin can still import words from pasted text', function (): void {
    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'Text import',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.import', $wordSet), [
            'words' => "cat|кот|A cat\n# comment\ndog|собака",
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    expect(Word::query()->where('word_set_id', $wordSet->id)->count())->toBe(2);
});

test('student cannot import words into system dictionary', function (): void {
    $student = User::factory()->student()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Basics',
        'description' => 'System',
        'created_by' => null,
    ]);

    $this->actingAs($student)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.import', $wordSet), [
            'words' => 'hi|привет',
        ])
        ->assertForbidden();
});

test('sample grade 3 excel template imports non-empty pairs', function (): void {
    $sample = base_path('temp/Слова 3 класс.xlsx');

    if (! is_file($sample)) {
        $this->markTestSkipped('Sample Excel file is not present in temp/.');
    }

    $admin = User::factory()->admin()->create();
    $language = Language::query()->create(['code' => 'en', 'name' => 'English']);
    $wordSet = WordSet::query()->create([
        'language_id' => $language->id,
        'title' => 'Слова 3 класс',
        'description' => 'From sample workbook',
        'created_by' => null,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.word-sets.view', $wordSet))
        ->post(route('admin.words.import', $wordSet), [
            'file' => new UploadedFile(
                $sample,
                'Слова 3 класс.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ])
        ->assertRedirect(route('admin.word-sets.view', $wordSet));

    expect(Word::query()->where('word_set_id', $wordSet->id)->count())->toBe(43)
        ->and(Word::query()->where([
            'word_set_id' => $wordSet->id,
            'text' => 'school bag',
            'translation' => 'портфель',
        ])->exists())->toBeTrue();
});
