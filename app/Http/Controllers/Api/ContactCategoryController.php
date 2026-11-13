<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use App\Enums\ContactGroupingEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class ContactCategoryController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()->addressedBy($vault)->firstOrFail();

        $validated = $request->validate([
            'filter' => ['nullable', 'string', Rule::in(ContactGroupingEnum::values())],
        ]);

        $held = $vault->categoriesHeldByContacts(
            ContactGroupingEnum::fromValueOrEveryone($validated['filter'] ?? null),
            $this->reachesSensitiveFields($request->user()),
        );

        return response()->json([
            'data' => array_map(fn (array $category): array => [
                'type' => 'contact_category',
                'id' => $category['item']->id,
                'attributes' => [
                    'label' => $category['item']->label,
                    'is_active' => $category['item']->is_active,
                    'contacts_count' => $category['contacts'],
                ],
            ], $held),
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
}
