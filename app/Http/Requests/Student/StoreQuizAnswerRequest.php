<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizAnswerRequest extends FormRequest
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
            'word_id' => ['required', 'integer', 'exists:words,id'],
            'selected_option_id' => ['required', 'integer', 'exists:words,id'],
        ];
    }
}
