<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ApplicationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexApplicationRequest;
use App\Http\Requests\Admin\UpdateApplicationRequest;
use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(IndexApplicationRequest $request): Response
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $applications = $this->sortedQuery($filters)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Application $application): array => $this->toArray($application));

        return Inertia::render('admin/Applications', [
            'applications' => $applications,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'region' => $filters['region'] ?? null,
                'course' => $filters['course'] ?? null,
                'level' => $filters['level'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'sort' => [
                'column' => $sort,
                'direction' => $direction,
            ],
            'regions' => Application::REGIONS,
            'courses' => Application::COURSES,
            'levels' => Application::LEVELS,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'default' => false, 'sortable' => true],
                ['key' => 'full_name', 'label' => 'full_name', 'default' => true, 'sortable' => true],
                ['key' => 'email', 'label' => 'email', 'default' => true, 'sortable' => true],
                ['key' => 'phone', 'label' => 'phone', 'default' => true, 'sortable' => true],
                ['key' => 'region', 'label' => 'region', 'default' => true, 'sortable' => true],
                ['key' => 'course', 'label' => 'course', 'default' => true, 'sortable' => true],
                ['key' => 'level', 'label' => 'level', 'default' => true, 'sortable' => true],
                ['key' => 'comment', 'label' => 'comment', 'default' => false, 'sortable' => true],
                ['key' => 'document', 'label' => 'document', 'default' => false, 'sortable' => false],
                ['key' => 'created_at', 'label' => 'created_at', 'default' => true, 'sortable' => true],
            ],
        ]);
    }

    public function exportExcel(IndexApplicationRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $filename = 'applications-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new ApplicationsExport($this->sortedQuery($filters)),
            $filename,
        );
    }

    public function exportPdf(IndexApplicationRequest $request): HttpResponse
    {
        $filters = $request->validated();
        $applications = $this->sortedQuery($filters)->get();
        $filename = 'applications-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('admin.applications-export-pdf', [
            'applications' => $applications,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function show(Application $application): Response
    {
        return Inertia::render('admin/ApplicationShow', [
            'application' => $this->toArray($application, withUpdatedAt: true),
            'regions' => Application::REGIONS,
            'courses' => Application::COURSES,
            'levels' => Application::LEVELS,
        ]);
    }

    public function update(
        UpdateApplicationRequest $request,
        Application $application,
    ): RedirectResponse {
        $validated = $request->validated();

        if ($request->hasFile('document')) {
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

            if (
                $application->document_path !== ''
                && Storage::disk('local')->exists($application->document_path)
            ) {
                Storage::disk('local')->delete($application->document_path);
            }

            $validated['document_path'] = $storedPath;
        }

        unset($validated['document']);

        $application->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Заявка обновлена.',
        ]);

        return back();
    }

    public function destroy(Application $application): RedirectResponse
    {
        if (
            $application->document_path !== ''
            && Storage::disk('local')->exists($application->document_path)
        ) {
            Storage::disk('local')->delete($application->document_path);
        }

        $application->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Заявка удалена.',
        ]);

        return to_route('admin.applications.index');
    }

    public function download(Application $application): StreamedResponse
    {
        abort_unless(
            Storage::disk('local')->exists($application->document_path),
            404,
        );

        return Storage::disk('local')->download(
            $application->document_path,
            $application->documentOriginalName(),
        );
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     region?: string|null,
     *     course?: string|null,
     *     level?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     sort?: string|null,
     *     direction?: string|null
     * }  $filters
     * @return Builder<Application>
     */
    private function sortedQuery(array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        return $this->filteredQuery($filters)
            ->orderBy($sort, $direction)
            ->orderByDesc('id');
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     region?: string|null,
     *     course?: string|null,
     *     level?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     sort?: string|null,
     *     direction?: string|null
     * }  $filters
     * @return Builder<Application>
     */
    private function filteredQuery(array $filters): Builder
    {
        return Application::query()
            ->when(
                $filters['search'] ?? null,
                function ($query, string $search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                },
            )
            ->when($filters['region'] ?? null, fn ($query, string $region) => $query->where('region', $region))
            ->when($filters['course'] ?? null, fn ($query, string $course) => $query->where('course', $course))
            ->when($filters['level'] ?? null, fn ($query, string $level) => $query->where('level', $level))
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo),
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Application $application, bool $withUpdatedAt = false): array
    {
        $data = [
            'id' => $application->id,
            'full_name' => $application->full_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'region' => $application->region,
            'course' => $application->course,
            'level' => $application->level,
            'comment' => $application->comment,
            'document_path' => $application->document_path,
            'document_name' => $application->documentOriginalName(),
            'created_at' => $application->created_at?->toIso8601String(),
        ];

        if ($withUpdatedAt) {
            $data['updated_at'] = $application->updated_at?->toIso8601String();
        }

        return $data;
    }
}
