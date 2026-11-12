<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ImportPossessionType as ImportPossessionTypeAction;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new possession type from a file written by export-possession-type. Nothing already in the account changes. A file with any problem creates nothing, and every problem is listed. The change is written to the account journal.')]
class ImportPossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.import';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.PossessionTypeFileReader::MAX_BYTES],
        ]);

        try {
            $possessionType = new ImportPossessionTypeAction(
                user: $this->user($request),
                raw: $validated['file'],
            )->execute();
        } catch (PossessionTypeFileIsNotReadable $exception) {
            return Response::error(implode("\n", $exception->problems));
        }

        return Response::json(new PossessionTypeResource($possessionType)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'file' => $schema->string()->description('The whole file, as JSON text.')->required(),
        ];
    }
}
