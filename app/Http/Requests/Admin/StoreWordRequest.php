<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWordRequest extends FormRequest
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
            'text' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'example_sentence' => ['nullable', 'string'],
            'audio_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'image', 'max:2048'],
            'theme_ids' => ['nullable', 'array'],
            'theme_ids.*' => ['integer', 'exists:themes,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('example_sentence')) {
            $this->merge(['example_sentence' => '']);
        }
    }
}
