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

/**
 * A page as whoever was sent a link to it sees it.
 *
 * There is no user here, and that is the feature rather than an omission. The
 * recipient needs no account, is never offered one, and never becomes a user of
 * the account they are reading into. Nothing on the page writes, and nothing may
 * ever be added to it that does.
 *
 * An unknown, an expired and a revoked token all answer the same way, because the
 * action makes them indistinguishable on purpose: somebody trying tokens learns
 * nothing from the difference, so neither does this controller.
 *
 * A protected link is opened with whatever the browsing session is holding for it,
 * which is what the form put there. That is why the password is checked here rather
 * than on the form: this is the only place a link is opened, so it is the only
 * place a visit is written, and walking between two tabs does not ask again.
 */
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
            /* What the browsing session was holding does not open this link,
               either because it was typed wrong on the form or because the owner
               has changed it since. Keeping it would write a failed visit on every
               click from here on, so it goes. */
            ShareLinkPassword::forget($request, $token);

            return view('shared.password', ['token' => $token, 'wrong' => true]);
        }

        $shareable = $shareLink->shareable;

        /* Only a contact has a page today. A vault link is a real row with
           nothing to render, and saying the link no longer works would be untrue,
           so it gets the sentence that is not. */
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
