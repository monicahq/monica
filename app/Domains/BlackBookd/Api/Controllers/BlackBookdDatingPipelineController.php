<?php

namespace App\Domains\BlackBookd\Api\Controllers;

use App\Domains\BlackBookd\ManageDatingPipeline\Services\GetQuietDatingPipelines;
use App\Domains\BlackBookd\ManageDatingPipeline\Services\GetWeeklyDatingRoundup;
use App\Domains\BlackBookd\ManageDatingPipeline\Services\UpsertBlackBookdDatingPipelineByHandle;
use App\Http\Controllers\ApiController;
use App\Http\Resources\BlackBookd\DatingPipelineResource;
use App\Models\BlackBookdDatingPipeline;
use Illuminate\Http\Request;

class BlackBookdDatingPipelineController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show', 'quiet', 'weeklyRoundup']);
        $this->middleware('abilities:write')->only(['upsertByHandle', 'update', 'destroy']);
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
        $items = $vault->blackbookdDatingPipelines()->with('contact')->orderByDesc('updated_at')->paginate($this->getLimitPerPage());

        return DatingPipelineResource::collection($items);
    }

    public function show(Request $request, string $vaultId, string $pipelineId)
    {
        $vault = $this->vault($request, $vaultId);
        $pipeline = $vault->blackbookdDatingPipelines()->with('contact')->findOrFail($pipelineId);

        return new DatingPipelineResource($pipeline);
    }

    public function upsertByHandle(Request $request, string $vaultId)
    {
        $data = array_merge($this->base($request, $vaultId), $request->all());
        $pipeline = (new UpsertBlackBookdDatingPipelineByHandle)->execute($data);

        return new DatingPipelineResource($pipeline);
    }

    public function update(Request $request, string $vaultId, string $pipelineId)
    {
        $vault = $this->vault($request, $vaultId);
        $pipeline = $vault->blackbookdDatingPipelines()->findOrFail($pipelineId);
        $data = array_merge($this->base($request, $vaultId), $request->all(), ['handle' => $pipeline->handle, 'contact_id' => $pipeline->contact_id]);
        $pipeline = (new UpsertBlackBookdDatingPipelineByHandle)->execute($data);

        return new DatingPipelineResource($pipeline);
    }

    public function destroy(Request $request, string $vaultId, string $pipelineId)
    {
        $vault = $this->vault($request, $vaultId);
        $vault->blackbookdDatingPipelines()->findOrFail($pipelineId)->delete();

        return $this->respondObjectDeleted($pipelineId);
    }

    public function quiet(Request $request, string $vaultId)
    {
        $days = $request->integer('days', 7);
        $items = (new GetQuietDatingPipelines)->execute(array_merge($this->base($request, $vaultId), ['days' => $days]));

        return DatingPipelineResource::collection($items);
    }

    public function weeklyRoundup(Request $request, string $vaultId)
    {
        $roundup = (new GetWeeklyDatingRoundup)->execute(array_merge(
            $this->base($request, $vaultId),
            ['quiet_days' => $request->integer('quiet_days', 7)]
        ));

        return response()->json([
            'data' => collect($roundup)->map(fn ($group, $key) => [
                'bucket' => $key,
                'pipelines' => DatingPipelineResource::collection($group),
            ])->values(),
        ]);
    }
}