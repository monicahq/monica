<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyTemplate;
use App\Exceptions\CannotDeleteATemplateInUse;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a template and all its versions. A template still describing records is refused. The change is written to the account journal.')]
#[IsDestructive]
class DeleteTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        try {
            new DestroyTemplate(
                user: $this->user($request),
                template: $this->template($request, $validated['template']),
            )->execute();
        } catch (CannotDeleteATemplateInUse $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The template is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template, as list-templates gives it.')->required(),
        ];
    }
}
