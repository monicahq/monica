<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\EntityTypeResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the kinds of record the account has defined, beyond contacts.')]
class ListEntityTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.entityTypes';

    public function handle(Request $request): Response
    {
        $entityTypes = $this->user($request)->account->entityTypes()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(EntityTypeResource::collection($entityTypes)->resolve());
    }
}
