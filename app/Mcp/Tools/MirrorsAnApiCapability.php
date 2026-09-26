<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Contact;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;

/**
 * What every tool does the same way: naming the API capability it mirrors, deciding whether the
 * presented key may see it at all, and reaching a record the way the API controllers reach one.
 *
 * A record outside the key's account, or outside the vault the request named, is reported as not
 * existing rather than as forbidden, in a translated sentence the assistant can relay as it stands.
 * The refusal is raised as a validation error because that is the one kind the MCP package relays
 * word for word whatever the debug setting. Any other exception reaches the assistant as a generic
 * server error once debug is off, and is reported as one.
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

    protected function entityType(Request $request, string $address): EntityType
    {
        $entityType = $this->user($request)->account->entityTypes()->where('uuid', $address)->first();

        if ($entityType === null) {
            throw $this->doesNotExist(__('That entity type does not exist.'));
        }

        return $entityType;
    }

    protected function version(Template $template, int $number): StructureVersion
    {
        $version = $template->versions()->where('version_number', $number)->first();

        if ($version === null) {
            throw $this->doesNotExist(__('That version does not exist.'));
        }

        return $version;
    }

    protected function section(StructureVersion $version, string $id): StructureVersionSection
    {
        $section = $version->assembly()->where('structure_version_sections.id', $id)->first();

        if ($section === null) {
            throw $this->doesNotExist(__('That section does not exist.'));
        }

        return $section;
    }

    protected function field(SectionVersion $sectionVersion, string $id): Field
    {
        $field = $sectionVersion->fields()->whereKey($id)->first();

        if ($field === null) {
            throw $this->doesNotExist(__('That field does not exist.'));
        }

        return $field;
    }

    protected function page(StructureVersion $version, string $uuid): StructureVersionPage
    {
        $page = $version->pages()->where('uuid', $uuid)->first();

        if ($page === null) {
            throw $this->doesNotExist(__('That page does not exist.'));
        }

        return $page;
    }

    protected function doesNotExist(string $message): ValidationException
    {
        return ValidationException::withMessages(['address' => $message]);
    }
}
