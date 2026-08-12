<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\MoveRecordToTemplateVersion;
use App\Enums\VersionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Put one contact on another version of the template it renders from.
 *
 * Publishing a new version of a template moves nobody onto it. Which version a
 * record renders from changes only when somebody says so, record by record,
 * which is what this is: the button on the banner when a newer version is
 * waiting, and the one on each line of the version history that goes back.
 *
 * Both directions are the same act, so they are the same route. Naming no
 * version means the one the template publishes now, which is what the banner
 * offers and the only thing it ever offered. Naming one is how the history goes
 * back to a version the template has moved on from.
 *
 * What can be named is deliberately narrow: a published version of the template
 * this contact already renders from. That is what keeps the address from being a
 * way to put a record on somebody else's structure, or onto a draft nobody has
 * finished, or onto a retired version the action would refuse anyway.
 */
class ContactTemplateVersionController extends Controller
{
    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $version = $request->integer('template_version_id') === 0
            ? $contact->templateVersion->template->publishedVersion
            : $this->versionAskedFor($contact, $request->integer('template_version_id'));

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

    /**
     * The version somebody asked for, when it is one this contact may be put on.
     * The lookup is scoped rather than validated after the fact: a version of
     * another template, or one nobody has published, is not a bad field on a form
     * but an address for something that is not on offer.
     */
    private function versionAskedFor(Contact $contact, int $versionId): ?TemplateVersion
    {
        return TemplateVersion::query()
            ->whereKey($versionId)
            ->where('template_id', $contact->templateVersion->template_id)
            ->where('account_id', $contact->account_id)
            ->where('status', VersionStatusEnum::Published)
            ->first();
    }
}
