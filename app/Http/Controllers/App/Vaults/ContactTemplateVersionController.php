<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\MoveRecordToTemplateVersion;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Put one contact on the version of its template that is published now.
 *
 * Publishing a new version of a template moves nobody onto it. Which version a
 * record renders from changes only when somebody says so, record by record,
 * which is what this is: the button on the banner the contact page shows when a
 * newer version is waiting.
 *
 * There is nothing to choose, so nothing arrives in the request. The one place
 * a record can be sent from here is the published version of the template it
 * already renders from, and reading that from the template is what keeps the
 * address from being a way to put a record on somebody else's structure.
 */
class ContactTemplateVersionController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $version = $contact->templateVersion->template->publishedVersion;

        abort_if($version === null, 404);

        new MoveRecordToTemplateVersion(
            user: $request->user(),
            record: $contact,
            templateVersion: $version,
        )->execute();

        return redirect()
            ->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __(':name is on version :number now.', [
                'name' => $contact->name,
                'number' => $version->version_number,
            ]))
            ->with('status_description', __('Nothing written down was touched.'));
    }
}
