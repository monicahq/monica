<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\TemplateResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the templates of the account, the structures its records are described by.')]
#[IsReadOnly]
class ListTemplates extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates';

    public function handle(Request $request): Response
    {
        $templates = $this->user($request)->account->templates()
            ->with(['structure.entityType', 'publishedVersion'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(TemplateResource::collection($templates)->resolve());
    }
}
