<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportWordsRequest extends FormRequest
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
            'words' => ['nullable', 'string', 'max:100000'],
            'file' => [
                'nullable',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasWords = filled($this->input('words'));
            $hasFile = $this->hasFile('file');

            if (! $hasWords && ! $hasFile) {
                $validator->errors()->add(
                    'file',
                    __('Choose an Excel file or paste words to import.'),
                );
            }
        });
    }
}
