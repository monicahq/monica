<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\AuthenticationException;
use Laravel\Mcp\Enums\ErrorCode;
use Laravel\Mcp\Exceptions\JsonRpcException;
use Laravel\Mcp\Request;

/**
 * What every tool does the same way: naming the API capability it mirrors, deciding whether the
 * presented key may see it at all, and reaching a record the way the API controllers reach one.
 *
 * A record outside the key's account, or outside the vault the request named, is reported as not
 * existing rather than as forbidden, in a translated sentence the assistant can relay as it stands.
 */
trait MirrorsAnApiCapability
{
    public function mirrors(): string
    {
        return $this->mirrors;
    }

    public function shouldRegister(Request $request): bool
    {
        return $request->user() !== null;
    }

    /** @throws AuthenticationException */
    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    protected function vault(Request $request, string $address): Vault
    {
        $vault = $this->user($request)->account->vaults()->addressedBy($address)->first();

        if ($vault === null) {
            throw $this->doesNotExist(__('That vault does not exist.'));
        }

        return $vault;
    }

    protected function contact(Vault $vault, string $address): Contact
    {
        $contact = Contact::query()
            ->where('vault_id', $vault->id)
            ->addressedBy($address)
            ->with(['vault', 'structureVersion.structure.owner', 'avatar'])
            ->first();

        if ($contact === null) {
            throw $this->doesNotExist(__('That contact does not exist.'));
        }

        return $contact;
    }

    protected function template(Request $request, string $address): Template
    {
        $template = $this->user($request)->account->templates()->where('uuid', $address)->first();

        if ($template === null) {
            throw $this->doesNotExist(__('That template does not exist.'));
        }

        return $template;
    }

    protected function doesNotExist(string $message): JsonRpcException
    {
        return new JsonRpcException($message, ErrorCode::INVALID_PARAMS->value);
    }
}
