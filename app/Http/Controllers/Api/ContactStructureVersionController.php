<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MoveRecordToStructureVersion;
use App\Enums\VersionStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\StructureVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactStructureVersionController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $request->validate(['structure_version_id' => ['nullable', 'integer']]);

        $version = $request->filled('structure_version_id')
            ? $this->versionAskedFor($contact, $request->integer('structure_version_id'))
            : $contact->structureVersion->structure->publishedVersion;

        abort_if($version === null, 404);

        new MoveRecordToStructureVersion(
            user: $request->user(),
            record: $contact,
            structureVersion: $version,
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }

    private function versionAskedFor(Contact $contact, int $versionId): ?StructureVersion
    {
        return StructureVersion::query()
            ->whereKey($versionId)
            ->where('structure_id', $contact->structureVersion->structure_id)
            ->where('account_id', $contact->account_id)
            ->where('status', VersionStatusEnum::Published)
            ->first();
    }
}
