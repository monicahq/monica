<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ChangeContactTemplate;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\StructureVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ContactTemplateController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $validated = $request->validate([
            'template' => ['required', 'string', 'max:255'],
            'structure_version_id' => ['nullable', 'integer'],
        ]);

        $template = $request->user()->account->templates()->where('uuid', $validated['template'])->firstOrFail();

        $version = isset($validated['structure_version_id'])
            ? StructureVersion::query()->where('account_id', $request->user()->account_id)->whereKey($validated['structure_version_id'])->firstOrFail()
            : null;

        try {
            new ChangeContactTemplate(
                user: $request->user(),
                contact: $contact,
                template: $template,
                structureVersion: $version,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['template' => $e->getMessage()]);
        }

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
