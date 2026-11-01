<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateGroup;
use App\Actions\DestroyGroup;
use App\Actions\UpdateGroup;
use App\Enums\DatePrecisionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $groups = Group::query()
            ->where('vault_id', $vault->id)
            ->with('vault')
            ->withCount('currentMembers')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(
                fn (Group $group): array => new GroupResource($group)->resolve($request),
                $groups->items(),
            ),
            'meta' => [
                'page' => $groups->currentPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault, string $group): JsonResponse
    {
        return new GroupResource($this->group($this->vault($request, $vault), $group))->response();
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate($this->rules(isCreating: true));

        $group = new CreateGroup(
            user: $request->user(),
            vault: $vault,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            startedOn: isset($validated['started_on']) ? CarbonImmutable::parse($validated['started_on']) : null,
            startedPrecision: DatePrecisionEnum::from($validated['started_precision'] ?? DatePrecisionEnum::Day->value),
            endedOn: isset($validated['ended_on']) ? CarbonImmutable::parse($validated['ended_on']) : null,
            endedPrecision: DatePrecisionEnum::from($validated['ended_precision'] ?? DatePrecisionEnum::Day->value),
        )->execute();

        return new GroupResource($this->group($vault, $group->uuid))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        $validated = $request->validate($this->rules(isCreating: false));

        $startedOn = array_key_exists('started_on', $validated) ? $validated['started_on'] : $group->started_on?->format('Y-m-d');
        $endedOn = array_key_exists('ended_on', $validated) ? $validated['ended_on'] : $group->ended_on?->format('Y-m-d');

        new UpdateGroup(
            user: $request->user(),
            vault: $vault,
            group: $group,
            name: $validated['name'] ?? $group->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $group->description,
            startedOn: $startedOn !== null ? CarbonImmutable::parse($startedOn) : null,
            startedPrecision: isset($validated['started_precision']) ? DatePrecisionEnum::from($validated['started_precision']) : $this->keptPrecision($group->started_precision),
            endedOn: $endedOn !== null ? CarbonImmutable::parse($endedOn) : null,
            endedPrecision: isset($validated['ended_precision']) ? DatePrecisionEnum::from($validated['ended_precision']) : $this->keptPrecision($group->ended_precision),
        )->execute();

        return new GroupResource($this->group($vault, $group->uuid))->response();
    }

    public function destroy(Request $request, string $vault, string $group): Response
    {
        $vault = $this->vault($request, $vault);

        new DestroyGroup(
            user: $request->user(),
            vault: $vault,
            group: $this->group($vault, $group),
        )->execute();

        return response()->noContent(204);
    }

    /**
     * A date given without a precision is taken as known to the day.
     */
    private function keptPrecision(DatePrecisionEnum $precision): DatePrecisionEnum
    {
        return $precision === DatePrecisionEnum::Unknown ? DatePrecisionEnum::Day : $precision;
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
            ->with('vault')
            ->withCount('currentMembers')
            ->firstOrFail();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $isCreating): array
    {
        return [
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'started_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ];
    }
}
