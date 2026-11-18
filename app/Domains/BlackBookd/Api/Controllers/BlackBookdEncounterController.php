<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManageEncounters\Services\CreateBlackBookdEncounter;
use App\Domains\BlackBookd\ManageEncounters\Services\DestroyBlackBookdEncounter;
use App\Domains\BlackBookd\ManageEncounters\Services\UpdateBlackBookdEncounter;
use App\Http\Controllers\ApiController;
use App\Http\Resources\BlackBookd\EncounterResource;
use Illuminate\Http\Request;

class BlackBookdEncounterController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show']);
        $this->middleware('abilities:write')->only(['store', 'update', 'destroy']);
        parent::__construct();
    }

    private function vault(Request $request, string $vaultId)
    {
        return $request->user()->account->vaults()->findOrFail($vaultId);
    }

    private function base(Request $request, string $vaultId): array
    {
        return [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
        ];
    }

    public function index(Request $request, string $vaultId)
    {
        $vault = $this->vault($request, $vaultId);
        $items = $vault->blackbookdEncounters()->with('contacts')->orderByDesc('occurred_at')->paginate($this->getLimitPerPage());

        return EncounterResource::collection($items);
    }

    public function store(Request $request, string $vaultId)
    {
        $data = array_merge($this->base($request, $vaultId), $request->only([
            'occurred_at', 'location_label', 'how_met_category', 'how_met_app', 'how_met_detail',
            'protection_used', 'activity_tags', 'substances', 'rating', 'private_notes', 'consent_notes', 'contact_ids',
        ]));

        $encounter = (new CreateBlackBookdEncounter)->execute($data);

        return (new EncounterResource($encounter))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $vaultId, string $encounterId)
    {
        $vault = $this->vault($request, $vaultId);
        $encounter = $vault->blackbookdEncounters()->with('contacts')->findOrFail($encounterId);

        return new EncounterResource($encounter);
    }

    public function update(Request $request, string $vaultId, string $encounterId)
    {
        $data = array_merge($this->base($request, $vaultId), $request->only([
            'occurred_at', 'location_label', 'how_met_category', 'how_met_app', 'how_met_detail',
            'protection_used', 'activity_tags', 'substances', 'rating', 'private_notes', 'consent_notes', 'contact_ids',
        ]), ['encounter_id' => $encounterId]);

        $encounter = (new UpdateBlackBookdEncounter)->execute($data);

        return new EncounterResource($encounter);
    }

    public function destroy(Request $request, string $vaultId, string $encounterId)
    {
        (new DestroyBlackBookdEncounter)->execute(array_merge($this->base($request, $vaultId), ['encounter_id' => $encounterId]));

        return $this->respondObjectDeleted($encounterId);
    }
}