<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\VaultResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the vaults of the account, the compartments its contacts are filed in.')]
class ListVaults extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.vaults';

    public function handle(Request $request): Response
    {
        $vaults = $this->user($request)->account->vaults()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(VaultResource::collection($vaults)->resolve());
    }
}
