<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Word;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Word>
 */
class WordsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<Word>  $query
     */
    public function __construct(
        private readonly Builder $query,
    ) {}

    public function collection(): Collection
    {
        return $this->query->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Слово',
            'Перевод',
            'Пример',
            'Создано',
        ];
    }

    /**
     * @param  Word  $row
     * @return list<string|int|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->text,
            $row->translation,
            $row->example_sentence !== '' ? $row->example_sentence : null,
            $row->created_at?->format('d.m.Y H:i'),
        ];
    }
}
