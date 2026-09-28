<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
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
        /** @var Language $language */
        $language = $this->route('language');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($language->id)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
