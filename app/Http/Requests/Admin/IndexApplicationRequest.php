<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Application;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexApplicationRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const SORTABLE = [
        'id',
        'full_name',
        'email',
        'phone',
        'region',
        'course',
        'level',
        'comment',
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
            'region' => ['nullable', 'string', Rule::in(Application::REGIONS)],
            'course' => ['nullable', 'string', Rule::in(Application::COURSES)],
            'level' => ['nullable', 'string', Rule::in(Application::LEVELS)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
