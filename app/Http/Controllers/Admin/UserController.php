<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->string('role')->toString(), function ($query, string $role): void {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => [
                'role' => $request->string('role')->toString() ?: null,
            ],
            'roles' => array_column(UserRole::cases(), 'value'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['avatar', 'password']);
        /** @var UserRole $role */
        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        $user = new User([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => $request->validated('password'),
            'role' => $role,
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $user->storeAvatar($request->file('avatar'));
        }

        $user->save();
        $user->syncRoles([$role->value]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['avatar', 'remove_avatar', 'password']);
        /** @var UserRole $role */
        $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'role' => $role,
        ]);

        if ($request->boolean('remove_avatar')) {
            $user->clearAvatar();
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            $user->clearAvatar();
            $user->avatar_path = $user->storeAvatar($request->file('avatar'));
        }

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->syncRoles([$role->value]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('admin.users.index');
    }
}
