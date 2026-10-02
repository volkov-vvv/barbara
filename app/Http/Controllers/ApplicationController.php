<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApplicationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('apply/Create', [
            'regions' => Application::REGIONS,
            'courses' => Application::COURSES,
            'levels' => Application::LEVELS,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        /** @var UploadedFile $document */
        $document = $request->file('document');

        $safeName = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '_',
            $document->getClientOriginalName(),
        ) ?: 'document';

        $storedPath = $document->storeAs(
            'applications',
            uniqid('app_', true).'_'.$safeName,
            'local',
        );

        if ($storedPath === false) {
            return back()->withErrors([
                'document' => 'Не удалось сохранить файл. Попробуйте ещё раз.',
            ]);
        }

        $validated = $request->validated();

        $application = Application::query()->create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'region' => $validated['region'],
            'document_path' => $storedPath,
            'course' => $validated['course'],
            'level' => $validated['level'],
            'comment' => $validated['comment'] ?? null,
        ]);

        Log::info('Learning application submitted', [
            'application_id' => $application->id,
            'email' => $application->email,
            'document_path' => $storedPath,
            'document_original_name' => $document->getClientOriginalName(),
            'document_size' => $document->getSize(),
            'document_mime' => $document->getMimeType(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Заявка успешно отправлена.',
        ]);

        return back();
    }
}
