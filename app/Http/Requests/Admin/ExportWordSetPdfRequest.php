<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportWordSetPdfRequest extends FormRequest
{
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
            'word_ids' => ['required', 'array', 'min:1'],
            'word_ids.*' => ['integer', 'distinct'],
            'random' => ['sometimes', 'boolean'],
            'words_only' => ['sometimes', 'boolean'],
            'translation_only' => ['sometimes', 'boolean'],
            'without_example' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $wordIds = $this->input('word_ids');

        if (is_array($wordIds)) {
            $this->merge([
                'word_ids' => array_values(array_map(
                    static fn (mixed $id): int => (int) $id,
                    $wordIds,
                )),
            ]);
        }

        foreach (['random', 'words_only', 'translation_only', 'without_example'] as $flag) {
            if ($this->has($flag)) {
                $this->merge([
                    $flag => filter_var($this->input($flag), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }
}
