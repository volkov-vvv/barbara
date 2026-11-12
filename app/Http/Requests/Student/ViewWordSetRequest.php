<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\WordSet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViewWordSetRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const SORTABLE = [
        'id',
        'text',
        'translation',
        'example_sentence',
        'created_at',
    ];

    public function authorize(): bool
    {
        /** @var WordSet $wordSet */
        $wordSet = $this->route('wordSet');

        return $wordSet->created_by === $this->user()?->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'has_example' => ['nullable', 'string', Rule::in(['0', '1'])],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
