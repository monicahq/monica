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
