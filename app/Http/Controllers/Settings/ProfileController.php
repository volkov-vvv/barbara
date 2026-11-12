<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'status' => $request->session()->get('status'),
            'locales' => collect(Locale::cases())->map(fn (Locale $locale): array => [
                'value' => $locale->value,
                'label' => $locale->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        if ($request->boolean('remove_avatar')) {
            $user->clearAvatar();
            $data['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            $user->clearAvatar();
            $data['avatar_path'] = $user->storeAvatar($request->file('avatar'));
        }

        $user->fill($data);
        $user->save();

        $locale = $user->locale->value;
        App::setLocale($locale);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.profile_updated'),
        ]);

        return to_route('profile.edit')
            ->cookie('locale', $locale, 60 * 24 * 365);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
