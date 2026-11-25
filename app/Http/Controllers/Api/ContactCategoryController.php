<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ChangeContactCategories;
use App\Enums\ApiKeyScopeEnum;
use App\Enums\ContactGroupingEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactCategoryChangeResource;
use App\Models\Contact;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
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

    public function update(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'contacts' => ['required', 'array', 'min:1', 'max:'.ChangeContactCategories::LIMIT],
            'contacts.*.contact' => ['required', 'string', 'max:255'],
            'contacts.*.categories' => ['present', 'array'],
            'contacts.*.categories.*' => ['integer'],
        ]);

        $assignments = [];

        foreach ($validated['contacts'] as $index => $assignment) {
            $contact = Contact::query()->withTrashed()
                ->where('vault_id', $vault->id)
                ->addressedBy($assignment['contact'])
                ->first();

            if ($contact === null) {
                throw ValidationException::withMessages([
                    "contacts.{$index}.contact" => __('That contact does not exist.'),
                ]);
            }

            $categories = ValueListItem::query()
                ->where('account_id', $request->user()->account_id)
                ->whereIn('id', $assignment['categories'])
                ->get();

            if ($categories->count() !== count(array_unique($assignment['categories']))) {
                throw ValidationException::withMessages([
                    "contacts.{$index}.categories" => __('That category does not exist.'),
                ]);
            }

            $assignments[] = ['contact' => $contact, 'categories' => $categories->values()->all()];
        }

        try {
            $outcome = new ChangeContactCategories(
                user: $request->user(),
                vault: $vault,
                assignments: $assignments,
                reachesSensitiveFields: $this->reachesSensitiveFields($request->user()),
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['contacts' => $e->getMessage()]);
        }

        return response()->json(['data' => new ContactCategoryChangeResource($outcome)]);
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
