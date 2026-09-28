<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\WordSet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWordRequest extends FormRequest
{
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
            'text' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'example_sentence' => ['required', 'string'],
            'audio_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
