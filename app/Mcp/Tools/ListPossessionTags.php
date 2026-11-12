<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\LocaleCollation;
use App\Http\Resources\PossessionTagResource;
use App\Models\PossessionTag;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the possession tags of the account, the words it puts on its possessions such as “insured” or “to sell”, sorted by name. Each carries its colour.')]
#[IsReadOnly]
class ListPossessionTags extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags';

    public function handle(Request $request): Response
    {
        $possessionTags = $this->user($request)->account->possessionTags()->get();

        return Response::json(LocaleCollation::sortByCollator($possessionTags, 'name')
            ->map(fn (PossessionTag $possessionTag): array => new PossessionTagResource($possessionTag)->resolve())
            ->values()
            ->all());
    }
}
