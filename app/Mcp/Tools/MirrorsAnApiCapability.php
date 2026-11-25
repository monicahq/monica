<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ApiKeyScopeEnum;
use App\Helpers\RecordKind;
use App\Helpers\SupportAvailability;
use App\Http\Resources\GroupFileResource;
use App\Http\Resources\GroupMembershipResource;
use App\Http\Resources\GroupNoteResource;
use App\Http\Resources\LifeEventResource;
use App\Models\AgentFeedbackReport;
use App\Models\Contact;
use App\Models\EmailSent;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\KnowledgeNote;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Location;
use App\Models\Log;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionType;
use App\Models\PossessionValuation;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\SupportRequest;
use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Pagination\PaginationState;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
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

    /**
     * Answers through the API controller method this tool mirrors, so the endpoint and the tool run
     * one implementation: its validation, its paging and its update semantics. The call skips the
     * route's middleware, which the MCP route already ran, except the two global ones that reshape
     * input, and keeps the MCP request as the current
     * one, so the account log still records the change as arriving from an assistant. A validation
     * refusal reaches the assistant word for word; a single record comes back without the `data` wrapper, as every other tool returns one, and an
     * answer without a JSON body comes back empty.
     */
    protected function answerThroughApi(Request $request, int|string ...$routeArguments): Response
    {
        $route = Route::getRoutes()->getByName($this->mirrors);
        [$controller, $method] = explode('@', $route->getActionName());

        $httpRequest = HttpRequest::create('/', $route->methods()[0], $request->all());
        $httpRequest->setUserResolver(fn (): User => $this->user($request));
        new TrimStrings()->handle($httpRequest, fn (HttpRequest $request): HttpRequest => $request);
        new ConvertEmptyStringsToNull()->handle($httpRequest, fn (HttpRequest $request): HttpRequest => $request);

        Paginator::currentPageResolver(function (string $pageName = 'page') use ($httpRequest): int {
            $page = $httpRequest->input($pageName);

            return filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1 ? (int) $page : 1;
        });

        try {
            $answer = app($controller)->{$method}($httpRequest, ...$routeArguments);
        } catch (ModelNotFoundException) {
            throw $this->doesNotExist(__('That record does not exist.'));
        } finally {
            PaginationState::resolveUsing(app());
        }

        if (! $answer instanceof JsonResponse) {
            return Response::text('');
        }

        $body = $answer->getData(true);

        if ($answer->getStatusCode() >= 400) {
            return Response::error((string) ($body['message'] ?? __('The request could not be completed.')));
        }

        return Response::json(array_keys($body) === ['data'] ? $body['data'] : $body);
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
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That life event does not exist.'));
        }

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

    protected function group(Vault $vault, string $address, bool $withTrashed = false): Group
    {
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That group does not exist.'));
        }

        $group = Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->when($withTrashed, fn (Builder $query): Builder => $query->withTrashed())
            ->with('vault')
            ->withCount('currentMembers')
            ->first();

        if ($group === null) {
            throw $this->doesNotExist(__('That group does not exist.'));
        }

        return $this->found($group);
    }

    protected function groupMembership(Group $group, string $address): GroupMember
    {
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That membership does not exist.'));
        }

        $membership = GroupMember::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->whereHas('contact')
            ->with(GroupMembershipResource::RELATIONS)
            ->first();

        if ($membership === null) {
            throw $this->doesNotExist(__('That membership does not exist.'));
        }

        return $this->found($membership);
    }

    protected function groupNote(Group $group, string $address): GroupNote
    {
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That note does not exist.'));
        }

        $note = GroupNote::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->with(GroupNoteResource::RELATIONS)
            ->first();

        if ($note === null) {
            throw $this->doesNotExist(__('That note does not exist.'));
        }

        return $this->found($note);
    }

    protected function groupFile(Group $group, string $address): GroupFile
    {
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That file does not exist.'));
        }

        $attachment = GroupFile::query()
            ->where('group_id', $group->id)
            ->where('uuid', $address)
            ->with(GroupFileResource::RELATIONS)
            ->first();

        if ($attachment === null) {
            throw $this->doesNotExist(__('That file does not exist.'));
        }

        return $this->found($attachment);
    }

    protected function template(Request $request, string $address): Template
    {
        $template = Str::isUuid($address) ? $this->user($request)->account->templates()->where('uuid', $address)->first() : null;

        if ($template === null) {
            throw $this->doesNotExist(__('That template does not exist.'));
        }

        return $this->found($template);
    }

    protected function entityType(Request $request, string $address): EntityType
    {
        $entityType = Str::isUuid($address) ? $this->user($request)->account->entityTypes()->where('uuid', $address)->first() : null;

        if ($entityType === null) {
            throw $this->doesNotExist(__('That entity type does not exist.'));
        }

        return $this->found($entityType);
    }

    protected function recordKind(Request $request, string $address): string
    {
        if ($address === RecordKind::CONTACTS) {
            return $address;
        }

        return $this->entityType($request, $address)->uuid;
    }

    protected function templateChange(Vault $vault, string $address): TemplateChange
    {
        $templateChange = Str::isUuid($address) ? TemplateChange::query()->where('vault_id', $vault->id)->where('uuid', $address)->first() : null;

        if ($templateChange === null) {
            throw $this->doesNotExist(__('That change of template does not exist.'));
        }

        return $this->found($templateChange);
    }

    protected function entity(Vault $vault, string $address): Entity
    {
        $entity = Str::isUuid($address) ? Entity::query()->where('vault_id', $vault->id)->where('uuid', $address)->first() : null;

        if ($entity === null) {
            throw $this->doesNotExist(__('That record does not exist.'));
        }

        return $this->found($entity);
    }

    protected function location(Request $request, string $address): Location
    {
        $location = $this->user($request)->account->locations()->where('uuid', $address)->first();

        if ($location === null) {
            throw $this->doesNotExist(__('That location does not exist.'));
        }

        return $this->found($location);
    }

    protected function possessionType(Request $request, string $address): PossessionType
    {
        $possessionType = $this->user($request)->account->possessionTypes()->where('uuid', $address)->first();

        if ($possessionType === null) {
            throw $this->doesNotExist(__('That possession type does not exist.'));
        }

        return $this->found($possessionType);
    }

    protected function possessionTag(Request $request, string $address): PossessionTag
    {
        $possessionTag = $this->user($request)->account->possessionTags()->where('uuid', $address)->first();

        if ($possessionTag === null) {
            throw $this->doesNotExist(__('That possession tag does not exist.'));
        }

        return $this->found($possessionTag);
    }

    protected function possessionCondition(Request $request, string $address): PossessionCondition
    {
        $possessionCondition = $this->user($request)->account->possessionConditions()->where('uuid', $address)->first();

        if ($possessionCondition === null) {
            throw $this->doesNotExist(__('That possession condition does not exist.'));
        }

        return $this->found($possessionCondition);
    }

    protected function possessionFieldGroup(PossessionType $possessionType, string $address): PossessionFieldGroup
    {
        $group = $possessionType->groups()->where('uuid', $address)->first();

        if ($group === null) {
            throw $this->doesNotExist(__('That group does not exist.'));
        }

        return $this->found($group);
    }

    protected function possessionField(PossessionType $possessionType, string $address): PossessionField
    {
        $field = $possessionType->fields()->where('uuid', $address)->first();

        if ($field === null) {
            throw $this->doesNotExist(__('That field does not exist.'));
        }

        return $this->found($field);
    }

    protected function possession(Vault $vault, string $address): Possession
    {
        $possession = $vault->possessions()->where('uuid', $address)->first();

        if ($possession === null) {
            throw $this->doesNotExist(__('That possession does not exist.'));
        }

        return $this->found($possession);
    }

    protected function possessionTransaction(Possession $possession, string $address): PossessionTransaction
    {
        $transaction = $possession->transactions()->where('uuid', $address)->first();

        if ($transaction === null) {
            throw $this->doesNotExist(__('That transaction does not exist.'));
        }

        return $this->found($transaction);
    }

    protected function possessionValuation(Possession $possession, string $address): PossessionValuation
    {
        $valuation = $possession->valuations()->where('uuid', $address)->first();

        if ($valuation === null) {
            throw $this->doesNotExist(__('That valuation does not exist.'));
        }

        return $this->found($valuation);
    }

    protected function possessionProvenanceEvent(Possession $possession, string $address): PossessionProvenanceEvent
    {
        $event = $possession->provenanceEvents()->where('uuid', $address)->first();

        if ($event === null) {
            throw $this->doesNotExist(__('That provenance event does not exist.'));
        }

        return $this->found($event);
    }

    protected function possessionLoan(Possession $possession, string $address): PossessionLoan
    {
        $loan = $possession->loans()->where('uuid', $address)->first();

        if ($loan === null) {
            throw $this->doesNotExist(__('That loan does not exist.'));
        }

        return $this->found($loan);
    }

    protected function possessionLocationStay(Possession $possession, string $address): PossessionLocationStay
    {
        $stay = $possession->locationStays()->where('uuid', $address)->first();

        if ($stay === null) {
            throw $this->doesNotExist(__('That stay does not exist.'));
        }

        return $this->found($stay);
    }

    protected function possessionInsuranceRecord(Possession $possession, string $address): PossessionInsuranceRecord
    {
        $record = $possession->insuranceRecords()->where('uuid', $address)->first();

        if ($record === null) {
            throw $this->doesNotExist(__('That insurance record does not exist.'));
        }

        return $this->found($record);
    }

    protected function possessionMaintenanceRecord(Possession $possession, string $address): PossessionMaintenanceRecord
    {
        $record = $possession->maintenanceRecords()->where('uuid', $address)->first();

        if ($record === null) {
            throw $this->doesNotExist(__('That maintenance record does not exist.'));
        }

        return $this->found($record);
    }

    protected function knowledgeFolder(Vault $vault, string $address): KnowledgeFolder
    {
        $folder = $vault->knowledgeFolders()->where('uuid', $address)->first();

        if ($folder === null) {
            throw $this->doesNotExist(__('That folder does not exist.'));
        }

        return $this->found($folder);
    }

    protected function knowledgeNote(Vault $vault, string $address): KnowledgeNote
    {
        $note = KnowledgeNote::query()->where('vault_id', $vault->id)->where('uuid', $address)->first();

        if ($note === null) {
            throw $this->doesNotExist(__('That note does not exist.'));
        }

        return $this->found($note);
    }

    protected function knowledgeList(Vault $vault, string $address): KnowledgeList
    {
        $list = KnowledgeList::query()->where('vault_id', $vault->id)->where('uuid', $address)->first();

        if ($list === null) {
            throw $this->doesNotExist(__('That list does not exist.'));
        }

        return $this->found($list);
    }

    protected function knowledgeListColumn(KnowledgeList $list, string $address): KnowledgeListColumn
    {
        $column = $list->columns()->where('uuid', $address)->first();

        if ($column === null) {
            throw $this->doesNotExist(__('That column does not exist.'));
        }

        return $this->found($column);
    }

    protected function knowledgeListItem(KnowledgeList $list, string $address): KnowledgeListItem
    {
        $item = $list->items()->where('uuid', $address)->first();

        if ($item === null) {
            throw $this->doesNotExist(__('That item does not exist.'));
        }

        return $this->found($item);
    }

    protected function relationshipType(Request $request, string $address): RelationshipType
    {
        $relationshipType = Str::isUuid($address) ? $this->user($request)->account->relationshipTypes()->where('uuid', $address)->first() : null;

        if ($relationshipType === null) {
            throw $this->doesNotExist(__('That relationship type does not exist.'));
        }

        return $this->found($relationshipType);
    }

    protected function relationshipCategory(Request $request, string $address): RelationshipCategory
    {
        $relationshipCategory = Str::isUuid($address) ? $this->user($request)->account->relationshipCategories()->where('uuid', $address)->first() : null;

        if ($relationshipCategory === null) {
            throw $this->doesNotExist(__('That relationship category does not exist.'));
        }

        return $this->found($relationshipCategory);
    }

    protected function lifeEventType(Request $request, string $address): LifeEventType
    {
        $lifeEventType = Str::isUuid($address) ? $this->user($request)->account->lifeEventTypes()->where('uuid', $address)->first() : null;

        if ($lifeEventType === null) {
            throw $this->doesNotExist(__('That life event type does not exist.'));
        }

        return $this->found($lifeEventType);
    }

    protected function lifeEventCategory(Request $request, string $address): LifeEventCategory
    {
        $lifeEventCategory = Str::isUuid($address) ? $this->user($request)->account->lifeEventCategories()->where('uuid', $address)->first() : null;

        if ($lifeEventCategory === null) {
            throw $this->doesNotExist(__('That life event category does not exist.'));
        }

        return $this->found($lifeEventCategory);
    }

    protected function valueList(Request $request, string $id): ValueList
    {
        $valueList = preg_match('/^[1-9]\d*$/', $id) === 1
            ? ValueList::query()
                ->where('account_id', $this->user($request)->account_id)
                ->where('is_shared', true)
                ->whereKey($id)
                ->first()
            : null;

        if ($valueList === null) {
            throw $this->doesNotExist(__('That value list does not exist.'));
        }

        return $this->found($valueList);
    }

    protected function valueListItem(ValueList $valueList, string $id): ValueListItem
    {
        $item = preg_match('/^[1-9]\d*$/', $id) === 1 ? $valueList->items()->whereKey($id)->first() : null;

        if ($item === null) {
            throw $this->doesNotExist(__('That value does not exist.'));
        }

        return $this->found($item);
    }

    protected function log(Request $request, string $id): Log
    {
        if (preg_match('/^[1-9]\d*$/', $id) !== 1) {
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
        if (preg_match('/^[1-9]\d*$/', $id) !== 1) {
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
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That page does not exist.'));
        }

        $page = $version->pages()->where('uuid', $uuid)->first();

        if ($page === null) {
            throw $this->doesNotExist(__('That page does not exist.'));
        }

        return $this->found($page);
    }

    protected function reminder(Contact $contact, string $uuid): Reminder
    {
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That reminder does not exist.'));
        }

        $reminder = $contact->reminders()
            ->where('uuid', $uuid)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->first();

        if ($reminder === null) {
            throw $this->doesNotExist(__('That reminder does not exist.'));
        }

        return $this->found($reminder);
    }

    protected function groupReminder(Group $group, string $uuid): Reminder
    {
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That reminder does not exist.'));
        }

        $reminder = $group->reminders()
            ->where('uuid', $uuid)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->first();

        if ($reminder === null) {
            throw $this->doesNotExist(__('That reminder does not exist.'));
        }

        return $this->found($reminder);
    }

    protected function groupReminderOccurrence(Group $group, string $uuid): ReminderOccurrence
    {
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That reminder date does not exist.'));
        }

        $occurrence = ReminderOccurrence::query()
            ->where('account_id', $group->account_id)
            ->where('uuid', $uuid)
            ->whereIn('reminder_id', $group->reminders()->select('id'))
            ->first();

        if ($occurrence === null) {
            throw $this->doesNotExist(__('That reminder date does not exist.'));
        }

        return $this->found($occurrence);
    }

    protected function relationship(Contact $contact, string $uuid): Relationship
    {
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That relationship does not exist.'));
        }

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
        if (! Str::isUuid($uuid)) {
            throw $this->doesNotExist(__('That reminder date does not exist.'));
        }

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

    protected function supportRequest(Request $request, string $address, bool $acrossAccounts = false): SupportRequest
    {
        if (! Str::isUuid($address)) {
            throw $this->doesNotExist(__('That support request does not exist.'));
        }

        $supportRequest = SupportRequest::query()
            ->where('uuid', $address)
            ->when(! $acrossAccounts, fn (Builder $query): Builder => $query->where('account_id', $this->user($request)->account_id))
            ->first();

        if ($supportRequest === null) {
            throw $this->doesNotExist(__('That support request does not exist.'));
        }

        return $this->found($supportRequest);
    }

    protected function agentFeedbackReport(string $address): AgentFeedbackReport
    {
        $report = Str::isUuid($address) ? AgentFeedbackReport::query()->where('uuid', $address)->first() : null;

        if ($report === null) {
            throw $this->doesNotExist(__('That report does not exist.'));
        }

        return $this->found($report);
    }

    /**
     * Whether the presented key may use the administrators' support tools: support is switched on
     * and the key administers the instance.
     */
    protected function administers(Request $request): bool
    {
        return SupportAvailability::isEnabled() && $this->administersInstance($request);
    }

    /**
     * Whether the presented key belongs to an administrator and carries the administration scope by
     * name, which a connected assistant never does.
     */
    protected function administersInstance(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdministrator()) {
            return false;
        }

        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken
            && in_array(ApiKeyScopeEnum::Administration->value, $token->abilities ?? [], true);
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
