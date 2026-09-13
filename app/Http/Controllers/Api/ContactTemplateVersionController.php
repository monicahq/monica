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

class ContactTemplateVersionController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

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
