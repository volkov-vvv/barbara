<?php

declare(strict_types=1);

namespace App\Http\Requests\Parent;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $parent */
        $parent = $this->user();
        /** @var User $student */
        $student = $this->route('student');

        return $parent->students()->where('users.id', $student->id)->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'daily_word_goal' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }
}
