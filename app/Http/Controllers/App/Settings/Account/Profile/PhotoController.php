<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\DestroyUserPhoto;
use App\Actions\UpdateUserPhoto;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        new UpdateUserPhoto(
            user: $request->user(),
            file: $validated['photo'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your photo is saved.'))
            ->with('status_description', __('It may take a minute to appear everywhere.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        new DestroyUserPhoto(user: $request->user())->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your photo is removed.'))
            ->with('status_description', __('We show your initials again.'));
    }

    public function show(Request $request, User $user, int $size): StreamedResponse
    {
        if ($user->account_id !== $request->user()->account_id) {
            abort(404);
        }

        if (! $user->hasPhoto()) {
            abort(404);
        }

        if (! in_array($size, User::photoPixelSizes(), true)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('filesystems.default'));
        $path = $user->photoVariantPath($size);

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, headers: [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
