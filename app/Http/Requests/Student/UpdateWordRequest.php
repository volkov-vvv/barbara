<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Word;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Word $word */
        $word = $this->route('word');

        return $word->wordSet->created_by === $this->user()?->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'example_sentence' => ['required', 'string'],
            'audio_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
