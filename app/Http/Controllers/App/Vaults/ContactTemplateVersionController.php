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
 * Publishing moves nobody, so this is the only thing that does: the button on
 * the banner, and the one on each line of the version history that goes back.
 *
 * Both directions are the same act and so the same route. Naming no version
 * means the one the template publishes now. What can be named is deliberately
 * narrow, a published version of the template this contact already renders
 * from, which keeps the address from being a way onto somebody else's
 * structure or onto a draft nobody has finished.
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
     * The lookup is scoped rather than validated after the fact: a version of
     * another template is not a bad field on a form but an address for
     * something that is not on offer.
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
