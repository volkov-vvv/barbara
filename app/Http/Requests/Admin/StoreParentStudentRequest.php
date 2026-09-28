<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentStudentRequest extends FormRequest
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
            'parent_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Parent->value),
            ],
            'student_id' => [
                'required',
                'integer',
                'different:parent_id',
                Rule::exists('users', 'id')->where('role', UserRole::Student->value),
            ],
            'relation' => ['required', 'string', 'max:255'],
        ];
    }
}
