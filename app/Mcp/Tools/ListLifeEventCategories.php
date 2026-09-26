<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\LifeEventCategoryResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the groups the account files its kinds of life event under.')]
#[IsReadOnly]
class ListLifeEventCategories extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories';

    public function handle(Request $request): Response
    {
        $categories = $this->user($request)->account->lifeEventCategories()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(LifeEventCategoryResource::collection($categories)->resolve());
    }
}
