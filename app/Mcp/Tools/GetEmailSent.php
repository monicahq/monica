<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one email sent to the account: its kind, subject, email address, what it said with links and URLs removed, and whether it arrived.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetEmailSent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.emailsSent.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'email_sent' => ['required', 'string'],
        ]);

        return $this->answerThroughApi($request, $this->emailSent($request, $validated['email_sent'])->id);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'email_sent' => $schema->string()->description('The id of the email, as list-emails-sent gives it.')->required(),
        ];
    }
}
