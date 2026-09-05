<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MoveRecordToTemplateVersion;
use App\Enums\VersionStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\TemplateVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Put one contact on another version of the template it renders from.
 * Publishing moves nobody, so this is the only thing that does.
 *
 * Both directions are the same act and so the same endpoint. Naming no version
 * means the one the template publishes now. What can be named is deliberately
 * narrow, a published version of the template this contact already renders from,
 * which keeps the address from being a way onto somebody else's structure or
 * onto a draft nobody has finished.
 *
 * A version that is not on offer answers not found rather than invalid, because
 * it is an address for something that is not there rather than a bad field on a
 * form. That is the answer the browser path gives too.
 *
 * Nothing written on the record moves. A record points at a precise version and
 * stays on it until somebody moves it, and moving it snapshots what was there
 * first.
 */
class ContactTemplateVersionController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->where('uuid', $vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->where('uuid', $contact)->firstOrFail();

        $request->validate(['template_version_id' => ['nullable', 'integer']]);

        $version = $request->filled('template_version_id')
            ? $this->versionAskedFor($contact, $request->integer('template_version_id'))
            : $contact->templateVersion->template->publishedVersion;

        abort_if($version === null, 404);

        new MoveRecordToTemplateVersion(
            user: $request->user(),
            record: $contact,
            templateVersion: $version,
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'templateVersion.template', 'avatar']),
        )->response()->setStatusCode(200);
    }

    /**
     * The lookup is scoped rather than validated after the fact: a version of
     * another template is not a bad field on a form but an address for something
     * that is not on offer.
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
