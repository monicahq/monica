<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ApiKeyScopeEnum;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\EmailSent;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Log;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * What every tool does the same way: naming the API capability it mirrors, deciding whether the
 * presented key may see it at all, and reaching a record the way the API controllers reach one.
 *
 * A record outside the key's account, or outside the vault the request named, is reported as not
 * existing rather than as forbidden, in a translated sentence the assistant can relay as it stands.
 * The refusal is raised as a validation error because that is the one kind the MCP package relays
 * word for word whatever the debug setting. Any other exception reaches the assistant as a generic
 * server error once debug is off, and is reported as one.
 *
 * A lookup that succeeds leaves the vault and the record it found on the request, which is how the
 * MCP history names what a call reached without ever naming what it was refused.
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

    /**
     * Whether the key presented may read the values of sensitive fields, which the API decides the
     * same way: a key holds the ability, a connected assistant never does.
     */
    protected function reachesSensitiveFields(Request $request): bool
    {
        $token = $this->user($request)->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }

    protected function vault(Request $request, string $address): Vault
    {
        $vault = $this->user($request)->account->vaults()->addressedBy($address)->first();

        if ($vault === null) {
            throw $this->doesNotExist(__('That vault does not exist.'));
        }

        request()->attributes->set('mcp_vault', $vault);

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

        return $this->found($contact);
    }

    protected function lifeEvent(Vault $vault, string $address, bool $withTrashed = false): LifeEvent
    {
        $lifeEvent = LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->when($withTrashed, fn (Builder $query): Builder => $query->withTrashed())
            ->with(LifeEventResource::RELATIONS)
            ->first();

        if ($lifeEvent === null) {
            throw $this->doesNotExist(__('That life event does not exist.'));
        }

        return $this->found($lifeEvent);
    }

    protected function template(Request $request, string $address): Template
    {
        $template = $this->user($request)->account->templates()->where('uuid', $address)->first();

        if ($template === null) {
            throw $this->doesNotExist(__('That template does not exist.'));
        }

        return $this->found($template);
    }

    protected function entityType(Request $request, string $address): EntityType
    {
        $entityType = $this->user($request)->account->entityTypes()->where('uuid', $address)->first();

        if ($entityType === null) {
            throw $this->doesNotExist(__('That entity type does not exist.'));
        }

        return $this->found($entityType);
    }

    protected function relationshipType(Request $request, string $address): RelationshipType
    {
        $relationshipType = $this->user($request)->account->relationshipTypes()->where('uuid', $address)->first();

        if ($relationshipType === null) {
            throw $this->doesNotExist(__('That relationship type does not exist.'));
        }

        return $this->found($relationshipType);
    }

    protected function relationshipCategory(Request $request, string $address): RelationshipCategory
    {
        $relationshipCategory = $this->user($request)->account->relationshipCategories()->where('uuid', $address)->first();

        if ($relationshipCategory === null) {
            throw $this->doesNotExist(__('That relationship category does not exist.'));
        }

        return $this->found($relationshipCategory);
    }

    protected function lifeEventType(Request $request, string $address): LifeEventType
    {
        $lifeEventType = $this->user($request)->account->lifeEventTypes()->where('uuid', $address)->first();

        if ($lifeEventType === null) {
            throw $this->doesNotExist(__('That life event type does not exist.'));
        }

        return $this->found($lifeEventType);
    }

    protected function lifeEventCategory(Request $request, string $address): LifeEventCategory
    {
        $lifeEventCategory = $this->user($request)->account->lifeEventCategories()->where('uuid', $address)->first();

        if ($lifeEventCategory === null) {
            throw $this->doesNotExist(__('That life event category does not exist.'));
        }

        return $this->found($lifeEventCategory);
    }

    protected function log(Request $request, string $id): Log
    {
        if (preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
            throw $this->doesNotExist(__('That log entry does not exist.'));
        }

        $log = $this->user($request)->account->logs()->with('user')->find((int) $id);

        if ($log === null) {
            throw $this->doesNotExist(__('That log entry does not exist.'));
        }

        return $this->found($log);
    }

    protected function emailSent(Request $request, string $id): EmailSent
    {
        if (preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
            throw $this->doesNotExist(__('That email does not exist.'));
        }

        $emailSent = $this->user($request)->account->emailsSent()->find((int) $id);

        if ($emailSent === null) {
            throw $this->doesNotExist(__('That email does not exist.'));
        }

        return $this->found($emailSent);
    }

    protected function version(Template $template, int $number): StructureVersion
    {
        $version = $template->versions()->where('version_number', $number)->first();

        if ($version === null) {
            throw $this->doesNotExist(__('That version does not exist.'));
        }

        return $this->found($version);
    }

    protected function section(StructureVersion $version, string $id): StructureVersionSection
    {
        $section = $version->assembly()->where('structure_version_sections.id', $id)->first();

        if ($section === null) {
            throw $this->doesNotExist(__('That section does not exist.'));
        }

        return $this->found($section);
    }

    protected function field(SectionVersion $sectionVersion, string $id): Field
    {
        $field = $sectionVersion->fields()->whereKey($id)->first();

        if ($field === null) {
            throw $this->doesNotExist(__('That field does not exist.'));
        }

        return $this->found($field);
    }

    protected function page(StructureVersion $version, string $uuid): StructureVersionPage
    {
        $page = $version->pages()->where('uuid', $uuid)->first();

        if ($page === null) {
            throw $this->doesNotExist(__('That page does not exist.'));
        }

        return $this->found($page);
    }

    protected function reminder(Contact $contact, string $uuid): Reminder
    {
        $reminder = $contact->reminders()
            ->where('uuid', $uuid)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->first();

        if ($reminder === null) {
            throw $this->doesNotExist(__('That reminder does not exist.'));
        }

        return $this->found($reminder);
    }

    protected function relationship(Contact $contact, string $uuid): Relationship
    {
        $relationship = Relationship::query()
            ->forContact($contact)
            ->where('uuid', $uuid)
            ->with(['vault', 'relationshipType', 'contactA', 'contactB'])
            ->first();

        if ($relationship === null) {
            throw $this->doesNotExist(__('That relationship does not exist.'));
        }

        return $this->found($relationship);
    }

    protected function reminderOccurrence(Contact $contact, string $uuid): ReminderOccurrence
    {
        $occurrence = ReminderOccurrence::query()
            ->where('account_id', $contact->account_id)
            ->where('uuid', $uuid)
            ->whereIn('reminder_id', $contact->reminders()->select('id'))
            ->first();

        if ($occurrence === null) {
            throw $this->doesNotExist(__('That reminder date does not exist.'));
        }

        return $this->found($occurrence);
    }

    /**
     * @template TRecord of Model
     *
     * @param  TRecord  $record
     * @return TRecord
     */
    private function found(Model $record): Model
    {
        request()->attributes->set('mcp_subject', $record);

        return $record;
    }

    protected function doesNotExist(string $message): ValidationException
    {
        return ValidationException::withMessages(['address' => $message]);
    }
}
