<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\SupportCategoryEnum;
use App\Helpers\SupportAvailability;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Ask the people who run this instance for help. Pick a category, write a subject and a message of at least 10 characters. A person reads it and replies by email and in the request. The user is sent a copy. Files cannot be sent from here.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld]
class CreateSupportRequest extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests.create';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User && SupportAvailability::isOffered();
    }

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->enum(SupportCategoryEnum::values())->description('What it is about: feature, billing, email (emails not received), question, or technical (something broken).')->required(),
            'subject' => $schema->string()->description('A short summary.')->required(),
            'body' => $schema->string()->description('What is going on, in Markdown, at least 10 characters.')->required(),
        ];
    }
}
