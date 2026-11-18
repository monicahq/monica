<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManageHealthLog\Services\CreateBlackBookdHealthLogEntry;
use App\Domains\BlackBookd\ManageHealthLog\Services\DestroyBlackBookdHealthLogEntry;
use App\Domains\BlackBookd\ManageHealthLog\Services\UpdateBlackBookdHealthLogEntry;
use App\Http\Controllers\ApiController;
use App\Http\Resources\BlackBookd\HealthLogResource;
use Illuminate\Http\Request;

class BlackBookdHealthLogController extends ApiController
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
        $items = $vault->blackbookdHealthLogEntries()->orderByDesc('occurred_on')->paginate($this->getLimitPerPage());

        return HealthLogResource::collection($items);
    }

    public function store(Request $request, string $vaultId)
    {
        $data = array_merge($this->base($request, $vaultId), $request->only([
            'entry_type', 'occurred_on', 'payload', 'notes', 'schedule_sti_reminder',
        ]));
        $entry = (new CreateBlackBookdHealthLogEntry)->execute($data);

        return new HealthLogResource($entry);
    }

    public function show(Request $request, string $vaultId, string $entryId)
    {
        $vault = $this->vault($request, $vaultId);
        $entry = $vault->blackbookdHealthLogEntries()->findOrFail($entryId);

        return new HealthLogResource($entry);
    }

    public function update(Request $request, string $vaultId, string $entryId)
    {
        $data = array_merge($this->base($request, $vaultId), $request->only([
            'entry_type', 'occurred_on', 'payload', 'notes',
        ]), ['entry_id' => $entryId]);
        $entry = (new UpdateBlackBookdHealthLogEntry)->execute($data);

        return new HealthLogResource($entry);
    }

    public function destroy(Request $request, string $vaultId, string $entryId)
    {
        (new DestroyBlackBookdHealthLogEntry)->execute(array_merge($this->base($request, $vaultId), ['entry_id' => $entryId]));

        return $this->respondObjectDeleted($entryId);
    }
}