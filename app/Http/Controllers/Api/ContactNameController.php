<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateContactName;
use App\Enums\NameOrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The one thing about a contact the product imposes rather than the template,
 * since sorting, searching, headers and initials are all built from it.
 *
 * This replaces rather than patches, which is why every part is demanded even
 * when the answer is null. The operation underneath writes a part it is not
 * given as empty, so a caller sending nothing but a new last name would erase
 * the prefix, the nickname and the rest. Nothing may disappear without being
 * said.
 *
 * Every part may be null, including all of them at once. A contact reduced to no
 * name still has to sort, search and draw initials, and the model supplies what
 * it always supplies rather than the request being refused.
 */
class ContactNameController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->where('uuid', $vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->where('uuid', $contact)->firstOrFail();

        $validated = $request->validate([
            'prefix' => ['present', 'nullable', 'string', 'max:50'],
            'first_name' => ['present', 'nullable', 'string', 'max:255'],
            'middle_name' => ['present', 'nullable', 'string', 'max:255'],
            'last_name' => ['present', 'nullable', 'string', 'max:255'],
            'suffix' => ['present', 'nullable', 'string', 'max:50'],
            'maiden_name' => ['present', 'nullable', 'string', 'max:255'],
            'nickname' => ['present', 'nullable', 'string', 'max:255'],
            'name_order' => ['present', 'nullable', Rule::enum(NameOrderEnum::class)],
        ]);

        new UpdateContactName(
            user: $request->user(),
            contact: $contact,
            prefix: $validated['prefix'],
            firstName: $validated['first_name'],
            middleName: $validated['middle_name'],
            lastName: $validated['last_name'],
            suffix: $validated['suffix'],
            maidenName: $validated['maiden_name'],
            nickname: $validated['nickname'],
            nameOrder: $validated['name_order'] === null ? null : NameOrderEnum::from($validated['name_order']),
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'templateVersion.template', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
