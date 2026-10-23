<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateRelationship;
use App\Actions\DestroyRelationship;
use App\Actions\UpdateRelationship;
use App\Enums\RelationshipStatusEnum;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipDatesAreOutOfOrder;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipResource;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContactRelationshipController extends Controller
{
    public function index(Request $request, string $vault, string $contact): JsonResponse
    {
        $contact = $this->contact($this->vault($request, $vault), $contact);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $relationships = Relationship::query()
            ->forContact($contact)
            ->with(['vault', 'relationshipType', 'contactA', 'contactB'])
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => RelationshipResource::collection($relationships->items())->resolve(),
            'meta' => [
                'page' => $relationships->currentPage(),
                'per_page' => $relationships->perPage(),
                'total' => $relationships->total(),
            ],
        ]);
    }

    public function create(Request $request, string $vault, string $contact): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $contact = $this->contact($vault, $contact);

        $validated = $request->validate([
            'relationship_type' => [
                'required',
                'string',
                Rule::exists('relationship_types', 'uuid')->where('account_id', $request->user()->account_id),
            ],
            'other_contact' => ['nullable', 'string', 'prohibits:first_name,last_name'],
            'first_name' => ['nullable', 'required_with:last_name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'creates_record' => ['nullable', 'boolean'],
            'holds_role_a' => ['nullable', 'boolean'],
        ]);

        $otherContact = ($validated['other_contact'] ?? null) === null
            ? null
            : $this->contact($vault, $validated['other_contact']);

        try {
            $relationship = new CreateRelationship(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                relationshipType: $this->relationshipType($request, $validated['relationship_type']),
                otherContact: $otherContact,
                firstName: $validated['first_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                createsRecord: $request->boolean('creates_record'),
                swapped: ! $request->boolean('holds_role_a', true),
            )->execute();
        } catch (CannotLinkSomebodyToThemselves|RelationshipWouldCrossVaults $e) {
            throw ValidationException::withMessages(['other_contact' => $e->getMessage()]);
        } catch (RelationshipTypeIsNotAvailable|RelationshipWouldLoopParentage $e) {
            throw ValidationException::withMessages(['relationship_type' => $e->getMessage()]);
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['first_name' => $e->getMessage()]);
        }

        return new RelationshipResource($this->relationship($contact, $relationship->uuid))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $vault, string $contact, string $relationship): JsonResponse
    {
        $contact = $this->contact($this->vault($request, $vault), $contact);

        return new RelationshipResource($this->relationship($contact, $relationship))->response()->setStatusCode(200);
    }

    public function update(Request $request, string $vault, string $contact, string $relationship): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $contact = $this->contact($vault, $contact);
        $relationship = $this->relationship($contact, $relationship);

        $validated = $request->validate([
            'relationship_type' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('relationship_types', 'uuid')->where('account_id', $request->user()->account_id),
            ],
            'status' => ['sometimes', 'required', Rule::in(RelationshipStatusEnum::values())],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'holds_role_a' => ['sometimes', 'required', 'boolean'],
            'first_name' => ['nullable', 'required_with:last_name', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'creates_record' => ['nullable', 'boolean'],
        ]);

        $names = ($validated['first_name'] ?? null) !== null || $request->boolean('creates_record');
        $other = $relationship->contact_a_id === $contact->id ? $relationship->contactB : $relationship->contactA;

        if ($names && ! $other->is_partial) {
            throw ValidationException::withMessages([
                'first_name' => __('Only somebody without a record of their own can be named here.'),
            ]);
        }

        $relationshipType = array_key_exists('relationship_type', $validated)
            ? $this->relationshipType($request, $validated['relationship_type'])
            : $relationship->relationshipType;

        try {
            new UpdateRelationship(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                relationship: $relationship,
                relationshipType: $relationshipType,
                status: array_key_exists('status', $validated) ? RelationshipStatusEnum::from($validated['status']) : $relationship->status,
                startedOn: $this->date($validated, 'started_on', $relationship->started_on),
                endedOn: $this->date($validated, 'ended_on', $relationship->ended_on),
                contactHoldsRoleA: $request->boolean('holds_role_a', $relationship->contact_a_id === $contact->id),
                otherFirstName: $validated['first_name'] ?? null,
                otherLastName: $validated['last_name'] ?? null,
                createsRecord: $request->boolean('creates_record'),
            )->execute();
        } catch (RelationshipTypeIsNotAvailable|RelationshipWouldLoopParentage $e) {
            throw ValidationException::withMessages(['relationship_type' => $e->getMessage()]);
        } catch (RelationshipDatesAreOutOfOrder $e) {
            throw ValidationException::withMessages(['ended_on' => $e->getMessage()]);
        }

        return new RelationshipResource($this->relationship($contact, $relationship->uuid))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault, string $contact, string $relationship): Response
    {
        $vault = $this->vault($request, $vault);
        $contact = $this->contact($vault, $contact);

        new DestroyRelationship(
            user: $request->user(),
            vault: $vault,
            relationship: $this->relationship($contact, $relationship),
        )->execute();

        return response()->noContent(204);
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

    private function relationship(Contact $contact, string $uuid): Relationship
    {
        return Relationship::query()
            ->forContact($contact)
            ->where('uuid', $uuid)
            ->with(['vault', 'relationshipType', 'contactA', 'contactB'])
            ->firstOrFail();
    }

    private function relationshipType(Request $request, string $uuid): RelationshipType
    {
        return $request->user()->account->relationshipTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /** @param  array<string, mixed>  $validated */
    private function date(array $validated, string $key, ?CarbonImmutable $current): ?CarbonImmutable
    {
        if (! array_key_exists($key, $validated)) {
            return $current;
        }

        return $validated[$key] === null ? null : CarbonImmutable::parse($validated[$key]);
    }
}
