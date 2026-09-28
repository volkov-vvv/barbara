<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParentStudentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParentStudentController extends Controller
{
    public function index(): Response
    {
        $links = DB::table('parent_student')
            ->join('users as parents', 'parents.id', '=', 'parent_student.parent_id')
            ->join('users as students', 'students.id', '=', 'parent_student.student_id')
            ->select([
                'parent_student.id',
                'parent_student.parent_id',
                'parent_student.student_id',
                'parent_student.relation',
                'parents.name as parent_name',
                'parents.email as parent_email',
                'students.name as student_name',
                'students.email as student_email',
            ])
            ->orderByDesc('parent_student.id')
            ->paginate(20);

        return Inertia::render('admin/ParentStudents', [
            'links' => $links,
            'parents' => User::query()->role(UserRole::Parent->value)->orderBy('name')->get(['id', 'name', 'email']),
            'students' => User::query()->role(UserRole::Student->value)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreParentStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var User $parent */
        $parent = User::query()->findOrFail($data['parent_id']);
        $parent->students()->syncWithoutDetaching([
            $data['student_id'] => ['relation' => $data['relation']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parent-student link created.')]);

        return to_route('admin.parent-students.index');
    }

    public function destroy(int $parentStudent): RedirectResponse
    {
        DB::table('parent_student')->where('id', $parentStudent)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parent-student link removed.')]);

        return to_route('admin.parent-students.index');
    }
}
