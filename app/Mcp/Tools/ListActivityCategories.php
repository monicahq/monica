<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\ActivityCategoryResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the groups the account files its kinds of activity under.')]
class ListActivityCategories extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.activityCategories';

    public function handle(Request $request): Response
    {
        $categories = $this->user($request)->account->activityCategories()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(ActivityCategoryResource::collection($categories)->resolve());
    }
}
