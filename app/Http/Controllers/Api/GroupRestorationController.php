<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\RestoreGroup;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupRestorationController extends Controller
{
    public function update(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        new RestoreGroup(
            user: $request->user(),
            vault: $vault,
            group: $group,
        )->execute();

        return new GroupResource($this->group($vault, $group->uuid))->response();
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
            ->withTrashed()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->with('vault')
            ->withCount('currentMembers')
            ->firstOrFail();
    }
}
