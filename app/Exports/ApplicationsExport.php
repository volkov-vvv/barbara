<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Application>
 */
class ApplicationsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<Application>  $query
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
            'ФИО',
            'Email',
            'Телефон',
            'Регион',
            'Курс',
            'Уровень',
            'Комментарий',
            'Документ',
            'Создана',
        ];
    }

    /**
     * @param  Application  $row
     * @return list<string|int|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->full_name,
            $row->email,
            $row->phone,
            $row->region,
            $row->course,
            $row->level,
            $row->comment,
            $row->documentOriginalName(),
            $row->created_at?->format('d.m.Y H:i'),
        ];
    }
}
