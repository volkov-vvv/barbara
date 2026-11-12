<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Theme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
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
        $theme = $this->route('theme');
        $themeId = $theme instanceof Theme ? $theme->id : $theme;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('themes', 'name')->ignore($themeId),
            ],
        ];
    }
}
