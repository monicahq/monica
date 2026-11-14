<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\JoinPreviewList;
use App\Http\Controllers\Controller;
use App\Rules\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class PreviewListEntryController extends Controller
{
    public function new(): View
    {
        abort_unless(config('monica.preview_list_enabled') && ! config('monica.registration_enabled'), 404);

        return view('app.auth.preview-list');
    }

    public function create(Request $request): RedirectResponse
    {
        abort_unless(config('monica.preview_list_enabled') && ! config('monica.registration_enabled'), 404);

        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', 'disposable_email'],
        ];

        if (config('turnstile.enabled')) {
            $rules['cf-turnstile-response'] = ['required', new Turnstile];
        }

        $validated = $request->validate($rules);

        new JoinPreviewList(
            email: $validated['email'],
            locale: App::currentLocale(),
        )->execute();

        return redirect()->route('auth.previewList.new')
            ->with('status', __('You are on the list. We will write to you when you can create your account.'));
    }
}
