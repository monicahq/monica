<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\DestroyPossessionLocationStay;
use App\Actions\MovePossession;
use App\Actions\UpdatePossessionLocationStay;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionLocationStayResource;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionLocationStayController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('locationStays.location');

        $stays = $record->locationStays
            ->each(fn (PossessionLocationStay $stay) => $stay->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionLocationStayResource::collection($stays)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $stay): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionLocationStayResource($this->stay($record, $stay))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $validated = $request->validate([
            'location_id' => ['required', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'moved_on' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $stay = new MovePossession(
            user: $request->user(),
            possession: $record,
            location: $this->location($request, $validated['location_id']),
            movedOn: CarbonImmutable::parse($validated['moved_on']),
            reason: $validated['reason'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return new PossessionLocationStayResource($stay)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $possession, string $stay): JsonResponse
    {
        $record = $this->stay($this->possession($this->vault($request, $vault), $possession), $stay);
        $validated = $request->validate([
            'location_id' => ['sometimes', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'arrived_on' => ['nullable', 'date_format:Y-m-d'],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $date = fn (string $field): ?CarbonImmutable => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : CarbonImmutable::parse($validated[$field]))
            : $record->{$field};
        $kept = fn (string $field): ?string => array_key_exists($field, $validated) ? $validated[$field] : $record->{$field};

        new UpdatePossessionLocationStay(
            user: $request->user(),
            stay: $record,
            location: isset($validated['location_id']) ? $this->location($request, $validated['location_id']) : $record->location,
            arrivedOn: $date('arrived_on'),
            leftOn: $date('left_on'),
            reason: $kept('reason'),
            note: $kept('note'),
        )->execute();

        return new PossessionLocationStayResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $stay): Response
    {
        $record = $this->stay($this->possession($this->vault($request, $vault), $possession), $stay);

        new DestroyPossessionLocationStay(
            user: $request->user(),
            stay: $record,
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function possession(Vault $vault, string $uuid): Possession
    {
        return Possession::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function stay(Possession $possession, string $uuid): PossessionLocationStay
    {
        return PossessionLocationStay::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function location(Request $request, string $uuid): Location
    {
        return Location::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
