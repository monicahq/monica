<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Profile;

use App\Actions\DestroyEmployeePhoto;
use App\Actions\UpdateEmployeePhoto;
use App\Http\Controllers\Controller;
use App\Models\Employee;
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

        new UpdateEmployeePhoto(
            user: $request->user(),
            file: $validated['photo'],
        )->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your photo is saved.'))
            ->with('status_description', __('It may take a minute to appear everywhere.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        new DestroyEmployeePhoto(user: $request->user())->execute();

        return redirect()->route('settings.profile.index')
            ->with('status', __('Your photo is removed.'))
            ->with('status_description', __('We show your initials again.'));
    }

    /**
     * Serve one version of an employee's photo. Photos are personal, so they
     * live on the private disk and are read through here rather than from a
     * public URL, and only by the people who work at the same company.
     */
    public function show(Request $request, Employee $employee, int $size): StreamedResponse
    {
        if ($employee->company_id !== $request->user()->company_id) {
            abort(404);
        }

        if (! $employee->hasPhoto()) {
            abort(404);
        }

        // Only the sizes actually written to disk, so this cannot be turned
        // into an endpoint that resizes whatever it is asked for.
        if (! in_array($size, Employee::photoPixelSizes(), true)) {
            abort(404);
        }

        $disk = Storage::disk((string) config('filesystems.default'));
        $path = $employee->photoVariantPath($size);

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, headers: [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
