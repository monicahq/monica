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

class ContactNameController extends Controller
{
    public function update(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();
        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($contact)->firstOrFail();

        $validated = $request->validate([
            'prefix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'suffix' => ['sometimes', 'nullable', 'string', 'max:50'],
            'maiden_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_order' => ['sometimes', 'nullable', Rule::enum(NameOrderEnum::class)],
        ]);

        new UpdateContactName(
            user: $request->user(),
            contact: $contact,
            prefix: array_key_exists('prefix', $validated) ? $validated['prefix'] : $contact->prefix,
            firstName: array_key_exists('first_name', $validated) ? $validated['first_name'] : $contact->first_name,
            middleName: array_key_exists('middle_name', $validated) ? $validated['middle_name'] : $contact->middle_name,
            lastName: array_key_exists('last_name', $validated) ? $validated['last_name'] : $contact->last_name,
            suffix: array_key_exists('suffix', $validated) ? $validated['suffix'] : $contact->suffix,
            maidenName: array_key_exists('maiden_name', $validated) ? $validated['maiden_name'] : $contact->maiden_name,
            nickname: array_key_exists('nickname', $validated) ? $validated['nickname'] : $contact->nickname,
            nameOrder: array_key_exists('name_order', $validated) ? NameOrderEnum::tryFrom((string) $validated['name_order']) : $contact->name_order,
        )->execute();

        return new ContactResource(
            $contact->refresh()->load(['vault', 'structureVersion.structure.owner', 'avatar']),
        )->response()->setStatusCode(200);
    }
}
