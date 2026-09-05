<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\OpenShareLink;
use App\Exceptions\ShareLinkNeedsAPassword;
use App\Exceptions\ShareLinkPasswordIsWrong;
use App\Helpers\ShareLinkPassword;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\ViewModels\Shared\SharedContactViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharedContactController extends Controller
{
    public function show(Request $request, string $token, ?string $page = null): View
    {
        try {
            $shareLink = new OpenShareLink(
                token: $token,
                password: ShareLinkPassword::remembered($request, $token),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            )->execute();
        } catch (ModelNotFoundException) {
            return view('shared.gone');
        } catch (ShareLinkNeedsAPassword) {
            return view('shared.password', ['token' => $token, 'wrong' => false]);
        } catch (ShareLinkPasswordIsWrong) {
            ShareLinkPassword::forget($request, $token);

            return view('shared.password', ['token' => $token, 'wrong' => true]);
        }

        $shareable = $shareLink->shareable;

        if (! $shareable instanceof Contact) {
            return view('shared.gone', ['line' => __('This link points at a whole vault, and there is no page for that yet.')]);
        }

        return view('shared.contact', [
            'viewModel' => new SharedContactViewModel(
                shareLink: $shareLink,
                contact: $shareable,
                token: $token,
                selectedPageKey: $page,
            ),
        ]);
    }
}
