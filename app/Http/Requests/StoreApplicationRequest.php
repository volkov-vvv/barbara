<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:applications,email'],
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['required', 'string', Rule::in(Application::REGIONS)],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpeg,png,jpg',
                'extensions:pdf,jpeg,png,jpg',
                'max:20480',
            ],
            'course' => ['required', 'string', Rule::in(Application::COURSES)],
            'level' => ['required', 'string', Rule::in(Application::LEVELS)],
            'comment' => ['nullable', 'string', 'max:2000'],
            'privacy_consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Укажите ФИО.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Укажите корректный email.',
            'email.unique' => 'Заявка с таким email уже существует.',
            'phone.required' => 'Укажите телефон.',
            'region.required' => 'Выберите регион.',
            'region.in' => 'Выберите регион из списка.',
            'document.required' => 'Прикрепите документ.',
            'document.file' => 'Документ должен быть файлом.',
            'document.uploaded' => 'Не удалось загрузить файл. Убедитесь, что размер не превышает 20 МБ и формат PDF/JPG/PNG.',
            'document.mimes' => 'Допустимы только PDF, JPG или PNG.',
            'document.extensions' => 'Допустимы только PDF, JPG или PNG.',
            'document.max' => 'Размер файла не должен превышать 20 МБ.',
            'course.required' => 'Выберите курс.',
            'course.in' => 'Выберите курс из списка.',
            'level.required' => 'Выберите уровень подготовки.',
            'level.in' => 'Выберите уровень из списка.',
            'privacy_consent.accepted' => 'Необходимо согласие с политикой конфиденциальности.',
        ];
    }
}
