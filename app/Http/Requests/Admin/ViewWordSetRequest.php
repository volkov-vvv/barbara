<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

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
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'has_example' => ['nullable', 'string', Rule::in(['0', '1'])],
            'theme_id' => ['nullable', 'integer', 'exists:themes,id'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
