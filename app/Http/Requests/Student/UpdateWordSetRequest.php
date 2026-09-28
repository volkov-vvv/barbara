<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\WordSet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWordSetRequest extends FormRequest
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
            'language_id' => ['required', 'integer', 'exists:languages,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}
