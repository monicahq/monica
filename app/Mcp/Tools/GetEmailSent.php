<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\EmailSentResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one email sent to the account: its kind, subject, address, what it said with links and web addresses removed, and whether it arrived.')]
#[IsReadOnly]
class GetEmailSent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.emailsSent.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'email_sent' => ['required', 'string'],
        ]);

        return Response::json(new EmailSentResource($this->emailSent($request, $validated['email_sent']), withBody: true)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'email_sent' => $schema->string()->description('The identifier of the email, as list-emails-sent gives it.')->required(),
        ];
    }
}
