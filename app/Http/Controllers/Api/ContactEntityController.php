<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityResource;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ContactEntityController extends Controller
{
    public function index(Request $request, string $vault, string $contact): JsonResponse
    {
        $contact = $this->contact($this->vault($request, $vault), $contact);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $entities = Entity::query()
            ->naming($contact)
            ->with([
                'vault',
                'entityType',
                'structureVersion.structure.owner',
                'avatar',
                'fieldValues.field',
                'fieldValues.valueListItem',
                'fieldValues.file.vault',
                'fieldValues.relatedContact',
                'fieldValues.relatedEntity',
            ])
            ->orderBy('sort_name')
            ->paginate($perPage);

        $withSensitive = $this->reachesSensitiveFields($request->user());

        return response()->json([
            'data' => array_map(function (Entity $entity) use ($withSensitive, $request): array {
                $resource = new EntityResource($entity);
                $resource->withSensitive = $withSensitive;

                return $resource->resolve($request);
            }, $entities->items()),
            'meta' => [
                'page' => $entities->currentPage(),
                'per_page' => $entities->perPage(),
                'total' => $entities->total(),
            ],
        ]);
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function contact(Vault $vault, string $address): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->addressedBy($address)
            ->firstOrFail();
    }
}
