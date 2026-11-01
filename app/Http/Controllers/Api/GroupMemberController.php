<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateGroupMembership;
use App\Actions\DestroyGroupMembership;
use App\Actions\UpdateGroupMembership;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupMembershipResource;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupMemberController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $memberships = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereHas('contact')
            ->with(GroupMembershipResource::RELATIONS)
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (GroupMember $membership): array => new GroupMembershipResource($membership)->resolve($request),
                $memberships->items(),
            ),
            'meta' => [
                'page' => $memberships->currentPage(),
                'per_page' => $memberships->perPage(),
                'total' => $memberships->total(),
            ],
        ]);
    }

    public function create(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        $validated = $request->validate(['contact' => ['required', 'string'], ...$this->rules()]);

        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($validated['contact'])->firstOrFail();

        try {
            $membership = new CreateGroupMembership(
                user: $request->user(),
                vault: $vault,
                group: $group,
                contact: $contact,
                role: $validated['role'] ?? null,
                joinedOn: isset($validated['joined_on']) ? CarbonImmutable::parse($validated['joined_on']) : null,
                joinedPrecision: DatePrecisionEnum::from($validated['joined_precision'] ?? DatePrecisionEnum::Day->value),
                leftOn: isset($validated['left_on']) ? CarbonImmutable::parse($validated['left_on']) : null,
                leftPrecision: DatePrecisionEnum::from($validated['left_precision'] ?? DatePrecisionEnum::Day->value),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup $e) {
            throw ValidationException::withMessages(['contact' => $e->getMessage()]);
        } catch (MembershipEndsBeforeItStarts $e) {
            throw ValidationException::withMessages(['left_on' => $e->getMessage()]);
        }

        return new GroupMembershipResource($membership->load(GroupMembershipResource::RELATIONS))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $group, string $membership): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $membership = $this->membership($this->group($vault, $group), $membership);

        $validated = $request->validate($this->rules());

        $joinedOn = array_key_exists('joined_on', $validated) ? $validated['joined_on'] : $membership->joined_on?->format('Y-m-d');
        $leftOn = array_key_exists('left_on', $validated) ? $validated['left_on'] : $membership->left_on?->format('Y-m-d');
        $joinedPrecision = $validated['joined_precision'] ?? $membership->joined_precision->value;
        $leftPrecision = $validated['left_precision'] ?? $membership->left_precision->value;

        try {
            new UpdateGroupMembership(
                user: $request->user(),
                vault: $vault,
                membership: $membership,
                role: array_key_exists('role', $validated) ? $validated['role'] : $membership->role,
                joinedOn: $joinedOn !== null ? CarbonImmutable::parse($joinedOn) : null,
                joinedPrecision: $joinedPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($joinedPrecision),
                leftOn: $leftOn !== null ? CarbonImmutable::parse($leftOn) : null,
                leftPrecision: $leftPrecision === DatePrecisionEnum::Unknown->value ? DatePrecisionEnum::Day : DatePrecisionEnum::from($leftPrecision),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup $e) {
            throw ValidationException::withMessages(['left_on' => $e->getMessage()]);
        } catch (MembershipEndsBeforeItStarts $e) {
            throw ValidationException::withMessages(['left_on' => $e->getMessage()]);
        }

        return new GroupMembershipResource($membership->fresh(GroupMembershipResource::RELATIONS))->response();
    }

    public function destroy(Request $request, string $vault, string $group, string $membership): Response
    {
        $vault = $this->vault($request, $vault);

        new DestroyGroupMembership(
            user: $request->user(),
            vault: $vault,
            membership: $this->membership($this->group($vault, $group), $membership),
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function group(Vault $vault, string $address): Group
    {
        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->firstOrFail();
    }

    private function membership(Group $group, string $address): GroupMember
    {
        return GroupMember::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->whereHas('contact')
            ->with(GroupMembershipResource::RELATIONS)
            ->firstOrFail();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
            'joined_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'left_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ];
    }
}
