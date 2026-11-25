<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\Actions\CreateLifeEvent;
use App\Actions\CreateTemplateChange;
use App\Actions\MergeContacts;
use App\Enums\AccountExportStatusEnum;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\LifeEventSourceEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\ChangelogEntries;
use App\Helpers\RecordKind;
use App\Helpers\StripeCatalogue;
use App\Jobs\ChangeTemplateOfRecords;
use App\Jobs\CreateDefaultTemplate;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Administrator;
use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use App\Models\ChangelogVote;
use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\ContactListColumn;
use App\Models\EmailSent;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\KnowledgeNote;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Location;
use App\Models\Log;
use App\Models\McpCall;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\Payment;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionFieldValue;
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
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StripeOrder;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Docs\ApiDocsSection;
use App\ViewModels\Docs\ApiDocsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\CollectionEngine;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class ApiDocsReturnFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_every_language_the_instance_ships_where_it_documents_the_locale(): void
    {
        $langPath = storage_path('framework/testing/docs-lang');

        if (! is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }

        file_put_contents($langPath.'/kl_GL.json', '{}');
        $this->app->useLangPath($langPath);

        $profile = include base_path('resources/docs/api/03-profile.php');
        $description = json_encode($profile);
        $locales = AvailableLocales::codes();

        unlink($langPath.'/kl_GL.json');

        $this->assertContains('kl_GL', $locales);

        foreach ($locales as $locale) {
            $this->assertStringContainsString($locale, (string) $description, $locale);
        }
    }

    #[Test]
    public function it_documents_every_field_every_endpoint_returns(): void
    {
        $problems = [];

        foreach ($this->endpoints() as $section) {
            if (! $section->answersWithFields()) {
                continue;
            }

            $problems = [...$problems, ...$this->check($section)];
        }

        $this->assertSame([], $problems, "The reference and the endpoints disagree:\n\n".implode("\n", $problems));
    }

    #[Test]
    public function every_endpoint_that_answers_with_a_body_documents_its_fields(): void
    {
        $missing = [];
        $unexpected = [];

        foreach ($this->endpoints() as $section) {
            $answersWithNothing = $section->responseStatus === 204;

            if (! $section->answersWithFields()) {
                if ($section->hasReturnFields()) {
                    $unexpected[] = $section->id;
                }

                continue;
            }

            if (! $answersWithNothing && ! $section->hasReturnFields()) {
                $missing[] = $section->id;
            }

            if ($answersWithNothing && $section->hasReturnFields()) {
                $unexpected[] = $section->id;
            }
        }

        $this->assertSame([], $unexpected, 'These endpoints answer with no body and document fields anyway: '.implode(', ', $unexpected));
    }

    #[Test]
    public function every_documented_example_has_the_shape_the_endpoint_answers_with(): void
    {
        $problems = [];

        foreach ($this->endpoints() as $section) {
            if ($section->response === null) {
                continue;
            }

            $response = $this->answer($section->id);

            if ($response === null || $response->status() !== $section->responseStatus) {
                continue;
            }

            $body = $response->content() === '' ? [] : ($response->json() ?? []);

            $answered = $this->paths($body, $section);
            $shown = $this->paths($section->response, $section);

            foreach (array_diff($shown, $answered) as $path) {
                $problems[] = "{$section->id}: the example shows `{$path}` and the answer does not carry it.";
            }

            foreach (array_diff($answered, $shown) as $path) {
                $problems[] = "{$section->id}: the answer carries `{$path}` and the example does not show it.";
            }
        }

        $this->assertSame([], $problems, "The examples and the endpoints disagree:\n\n".implode("\n", $problems));
    }

    /**
     * What a documented kind is allowed to come back as. A timestamp is a count
     * of seconds and a uuid is written out, so both reach the reader as one of
     * the kinds JSON has.
     */
    private const array ANSWERS_FOR = [
        'string' => ['string'],
        'uuid' => ['string'],
        'timestamp' => ['integer'],
        'integer' => ['integer'],
        'boolean' => ['boolean'],
        'object' => ['object'],
        'array' => ['array'],
    ];

    /**
     * The value a documented path names, or null when the answer does not reach
     * it. A path naming a record inside a list is read on the first record.
     *
     * @param  array<array-key, mixed>  $answer
     */
    private function valueAt(array $answer, string $path): mixed
    {
        $held = $answer;

        foreach (explode('.', $path) as $step) {
            if (str_ends_with($step, '[]')) {
                $step = mb_substr($step, 0, -2);

                if (! is_array($held) || ! is_array($held[$step] ?? null) || ($held[$step] === [])) {
                    return null;
                }

                $held = $held[$step][0];

                continue;
            }

            if (! is_array($held) || ! array_key_exists($step, $held)) {
                return null;
            }

            $held = $held[$step];
        }

        return $held;
    }

    private function kindOf(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_string($value) => 'string',
            is_array($value) && array_is_list($value) => 'array',
            is_array($value) => 'object',
            default => get_debug_type($value),
        };
    }

    /** @return list<string> */
    private function check(ApiDocsSection $section): array
    {
        $documented = array_column($section->returnFields, 'name');

        $response = $this->answer($section->id);

        if ($response === null) {
            return ["{$section->id}: nothing in this test knows how to call it. Add an arm to answer()."];
        }

        if ($response->status() !== $section->responseStatus) {
            return ["{$section->id}: its arm got {$response->status()} where the reference promises {$section->responseStatus}, so there is nothing worth comparing. Fix the arm.".PHP_EOL.$response->content()];
        }

        $body = $response->content() === '' ? [] : ($response->json() ?? []);

        $actual = $this->paths($body, $section);

        foreach ($documented as $field) {
            if (str_ends_with($field, '[]') && ! in_array($field, $actual, true)) {
                return ["{$section->id}: `{$field}` stands for a record in a list, and the list came back empty, so nothing inside a record was checked. Arrange at least one record in its arm."];
            }
        }

        $problems = [];

        foreach (array_diff($actual, $documented) as $field) {
            $problems[] = "{$section->id}: the answer carries `{$field}` and the reference does not mention it.";
        }

        foreach (array_diff($documented, $actual) as $field) {
            $problems[] = "{$section->id}: the reference documents `{$field}` and the answer does not carry it.";
        }

        return $problems;
    }

    /**
     * @param  array<array-key, mixed>  $answer
     * @return list<string>
     */
    private function paths(array $answer, ApiDocsSection $section): array
    {
        $opaque = array_column(array_filter($section->returnFields, fn (array $field): bool => $field['opaque'] ?? false), 'name');

        return $this->walk($answer, '', $opaque);
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @param  list<string>  $opaque
     * @return list<string>
     */
    private function walk(array $value, string $prefix, array $opaque): array
    {
        $paths = [];

        foreach ($value as $key => $child) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            $paths[] = $path;

            if (in_array($path, $opaque, true) || ! is_array($child) || $child === []) {
                continue;
            }

            if (array_is_list($child)) {
                $paths[] = "{$path}[]";

                foreach ($child as $record) {
                    if (is_array($record)) {
                        $paths = [...$paths, ...$this->walk($record, "{$path}[]", $opaque)];
                    }
                }

                continue;
            }

            $paths = [...$paths, ...$this->walk($child, $path, $opaque)];
        }

        return $paths;
    }

    /** @return list<ApiDocsSection> */
    private function endpoints(): array
    {
        $sections = [];

        foreach (new ApiDocsViewModel()->groups() as $group) {
            if ($group['guide']) {
                continue;
            }

            foreach ($group['sections'] as $section) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    private function candidatePairIn(Vault $vault): ContactDuplicateCandidate
    {
        [$one, $two] = $this->twoContactsToMergeIn($vault);

        return ContactDuplicateCandidate::factory()->create([
            'contact_id' => min($one->id, $two->id),
            'other_contact_id' => max($one->id, $two->id),
            'score' => 95,
            'reasons' => [
                ['kind' => 'field', 'field_key' => 'email', 'value' => 'jim@dundermifflin.com'],
                ['kind' => 'name'],
            ],
        ]);
    }

    /** @return array{0: Contact, 1: Contact} */
    private function twoContactsToMergeIn(Vault $vault): array
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $vault->account_id]);
        $email = Field::factory()->create([
            'account_id' => $vault->account_id,
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Email,
            'key' => 'email',
            'label' => 'Email',
            'is_identifying' => true,
        ]);
        $nickname = Field::factory()->create([
            'account_id' => $vault->account_id,
            'section_version_id' => $sectionVersion->id,
            'key' => 'what_they_go_by',
            'label' => 'What they go by',
        ]);

        $template = Template::factory()->create(['account_id' => $vault->account_id, 'name' => 'Basic contact']);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        StructureVersionSection::factory()->create([
            'account_id' => $vault->account_id,
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $version->status = VersionStatusEnum::Published;
        $version->save();

        $kept = Contact::factory()->create([
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);
        $absorbed = Contact::factory()->create([
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => 'Jimmy',
            'last_name' => 'Halpert',
        ]);

        $this->valueOf($kept, $email, 'jim@dundermifflin.com');
        $this->valueOf($absorbed, $email, 'jimmy@dundermifflin.com');
        $this->valueOf($absorbed, $nickname, 'Big Tuna');

        return [$kept, $absorbed];
    }

    private function valueOf(Contact $contact, Field $field, string $value): void
    {
        FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }

    private function answer(string $id): ?TestResponse
    {
        Queue::fake();

        return match ($id) {
            'status-show' => $this->json('GET', '/api/v1/status'),

            'search-index' => $this->asSomebody(function (User $user): TestResponse {
                resolve(EngineManager::class)->extend('meilisearch', fn (): CollectionEngine => new CollectionEngine);
                config(['scout.driver' => 'meilisearch']);

                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
                $employer = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Text, 'label' => 'Employer']);
                FieldValue::factory()->create(['valuable_type' => Contact::class, 'valuable_id' => $dwight->id, 'field_id' => $employer->id, 'value_string' => 'Schrute Farms']);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'Farms']);
            }),

            'register-store' => $this->json('POST', '/api/v1/register', [
                'first_name' => 'Michael',
                'last_name' => 'Scott',
                'email' => 'michael@dundermifflin.test',
                'password' => 'a-very-strong-password',
                'password_confirmation' => 'a-very-strong-password',
                'terms' => true,
            ]),

            'login-store' => $this->loggingIn(),

            'logout-destroy' => $this->withHeader('Authorization', 'Bearer '.User::factory()->create()->createToken('Dundie awards bot')->plainTextToken)
                ->json('DELETE', '/api/v1/logout'),

            'me-show' => $this->asSomebody(fn (): TestResponse => $this->json('GET', '/api/v1/me')),

            'billing-show' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                $this->subscribeAccount($user->account, 'price_monthly', stripeId: 'sub_docs_show');
                Payment::factory()->forSubscription()->create(['account_id' => $user->account_id]);
                StripeOrder::factory()->refunded()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/billing');
            }),

            'billing-payments-index' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                Payment::factory()->create(['account_id' => $user->account_id, 'stripe_id' => 'in_docs_index']);

                return $this->json('GET', '/api/v1/billing/payments');
            }),

            'billing-payments-show' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                Payment::factory()->create(['account_id' => $user->account_id, 'stripe_id' => 'in_docs_show']);

                return $this->json('GET', '/api/v1/billing/payments/in_docs_show');
            }),

            'billing-checkout-sessions-create' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();

                return $this->json('POST', '/api/v1/billing/checkout-sessions', [
                    'price_id' => 'price_lifetime',
                ]);
            }),

            'billing-subscription-update' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $client = $this->fakeStripe();
                $this->subscribeAccount($user->account, 'price_monthly', stripeId: 'sub_docs_update');
                Payment::factory()->forSubscription()->create(['account_id' => $user->account_id]);
                StripeOrder::factory()->refunded()->create(['account_id' => $user->account_id]);
                $client->respondTo('POST subscriptions/*', $this->stripeSubscription('price_yearly'));

                return $this->json('PUT', '/api/v1/billing/subscription', ['price_id' => 'price_yearly']);
            }),

            'billing-subscription-destroy' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                $this->subscribeAccount($user->account, 'price_monthly', stripeId: 'sub_docs_destroy');

                return $this->json('DELETE', '/api/v1/billing/subscription');
            }),

            'billing-subscription-cancellation-destroy' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                $this->subscribeAccount($user->account, 'price_monthly', 'active', now()->addWeek(), 'sub_docs_cancellation');
                Payment::factory()->forSubscription()->create(['account_id' => $user->account_id]);
                StripeOrder::factory()->refunded()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', '/api/v1/billing/subscription/cancellation');
            }),

            'billing-portal-sessions-create' => $this->asSomebody(function (User $user): TestResponse {
                StripeCatalogue::forget();
                $this->fakeStripe();
                $this->subscribeAccount($user->account, 'price_monthly', stripeId: 'sub_docs_portal');

                return $this->json('POST', '/api/v1/billing/portal-sessions');
            }),

            'me-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me', [
                'first_name' => 'Michael',
                'last_name' => 'Scott',
                'email' => 'jim@dundermifflin.test',
            ])),

            'me-timezone-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me/timezone', [
                'timezone' => 'Europe/Lisbon',
            ])),

            'me-vault-list-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me/vault-list', [
                'shows_vault_list' => false,
            ])),

            'me-contact-list-view-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me/contact-list-view', [
                'grouping' => 'favourites',
                'sort' => 'last-spoke',
                'direction' => 'desc',
            ])),

            'me-welcome-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me/welcome')),
            'me-full-access-offer-update' => $this->asSomebody(fn (User $user): TestResponse => $this->json('PUT', '/api/v1/me/full-access-offer')),

            'api-keys-index' => $this->asSomebody(function (User $user): TestResponse {
                $user->createToken('Dundie awards bot');

                return $this->json('GET', '/api/v1/api-keys');
            }),

            'api-keys-show' => $this->asSomebody(function (User $user): TestResponse {
                $key = $user->createToken('Dundie awards bot');

                return $this->json('GET', "/api/v1/api-keys/{$key->accessToken->id}");
            }),

            'api-keys-create' => $this->asSomebody(function (User $user): TestResponse {
                $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

                return $this->json('POST', '/api/v1/api-keys', [
                    'name' => 'Dundie awards bot',
                ]);
            }),

            'api-keys-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $key = $user->createToken('Dundie awards bot');

                return $this->json('DELETE', "/api/v1/api-keys/{$key->accessToken->id}");
            }),

            'logs-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->aLog($user);

                return $this->json('GET', '/api/v1/logs');
            }),

            'logs-show' => $this->asSomebody(function (User $user): TestResponse {
                $log = $this->aLog($user);

                return $this->json('GET', "/api/v1/logs/{$log->id}");
            }),

            'mcp-calls-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->anMcpCall($user);

                return $this->json('GET', '/api/v1/mcp-calls');
            }),

            'mcp-calls-show' => $this->asSomebody(function (User $user): TestResponse {
                $call = $this->anMcpCall($user);

                return $this->json('GET', "/api/v1/mcp-calls/{$call->uuid}");
            }),

            'emails-sent-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->anEmail($user);

                return $this->json('GET', '/api/v1/emails-sent');
            }),

            'emails-sent-show' => $this->asSomebody(function (User $user): TestResponse {
                $email = $this->anEmail($user);

                return $this->json('GET', "/api/v1/emails-sent/{$email->id}");
            }),

            'notifications-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->aNotification($user);

                return $this->json('GET', '/api/v1/notifications');
            }),

            'notifications-show' => $this->asSomebody(function (User $user): TestResponse {
                $notification = $this->aNotification($user);

                return $this->json('GET', "/api/v1/notifications/{$notification->uuid}");
            }),

            'notifications-read-update' => $this->asSomebody(function (User $user): TestResponse {
                $notification = $this->aNotification($user);

                return $this->json('PUT', "/api/v1/notifications/{$notification->uuid}/read");
            }),

            'notifications-read-all-update' => $this->asSomebody(function (User $user): TestResponse {
                $this->aNotification($user);

                return $this->json('PUT', '/api/v1/notifications/read');
            }),

            'notification-channels-index' => $this->asSomebody(fn (User $user): TestResponse => $this->json('GET', '/api/v1/notification-channels')),

            'notification-channels-show' => $this->asSomebody(fn (User $user): TestResponse => $this->json('GET', '/api/v1/notification-channels/telegram')),

            'notification-channels-update' => $this->asSomebody(function (User $user): TestResponse {
                $this->aConnectedTelegram($user);

                return $this->json('PUT', '/api/v1/notification-channels/telegram', ['is_enabled' => true]);
            }),

            'notification-channels-setup-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $this->aTextMessageAwaitingProof($user);

                return $this->json('DELETE', '/api/v1/notification-channels/sms/setup');
            }),

            'notification-channels-proof-store' => $this->asSomebody(function (User $user): TestResponse {
                $this->aTextMessageAwaitingProof($user);

                return $this->json('POST', '/api/v1/notification-channels/sms/proof');
            }),

            'notification-channels-proof-update' => $this->asSomebody(function (User $user): TestResponse {
                $this->aTextMessageAwaitingProof($user)->update([
                    'proof_code_hash' => Hash::make('123456'),
                    'proof_expires_at' => now()->addMinutes(10),
                ]);

                return $this->json('PUT', '/api/v1/notification-channels/sms/proof', ['code' => '123456']);
            }),

            'notification-channels-test-store' => $this->asSomebody(function (User $user): TestResponse {
                NotificationFacade::fake();
                $this->aConnectedTelegram($user);

                return $this->json('POST', '/api/v1/notification-channels/telegram/test');
            }),

            'notification-preferences-show' => $this->asSomebody(function (User $user): TestResponse {
                NotificationPreference::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/notification-preferences');
            }),

            'notification-preferences-update' => $this->asSomebody(function (User $user): TestResponse {
                NotificationPreference::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', '/api/v1/notification-preferences', [
                    'quiet_hours_are_enabled' => true,
                    'quiet_hours_start' => '22:00',
                    'quiet_hours_end' => '08:00',
                    'delivery_mode' => 'immediate',
                    'timezone' => 'America/New_York',
                ]);
            }),

            'vaults-index' => $this->asSomebody(function (User $user): TestResponse {
                Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/vaults');
            }),

            'vaults-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}");
            }),

            'vaults-create' => $this->asSomebody(function (User $user): TestResponse {
                new CreateDefaultTemplate(user: $user)->handle();

                return $this->json('POST', '/api/v1/vaults', [
                    'name' => 'Dunder Mifflin',
                ]);
            }),

            'vaults-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}", [
                    'name' => 'Scranton',
                    'description' => null,
                    'colour' => '#9a3f2b',
                ]);
            }),

            'templates-index' => $this->asSomebody(function (User $user): TestResponse {
                Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/templates');
            }),

            'templates-show' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/templates/{$template->uuid}");
            }),

            'templates-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/templates', [
                'name' => 'Wine bottle',
                'applies_to' => 'contact',
            ])),

            'templates-update' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}", [
                    'name' => 'Person',
                    'description' => 'Anybody the family knows.',
                ]);
            }),

            'templates-destroy' => $this->asSomebody(function (User $user): TestResponse {
                Template::factory()->published()->create(['account_id' => $user->account_id]);
                $template = Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/templates/{$template->uuid}");
            }),

            'templates-activation-update' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/activation", ['is_active' => false]);
            }),

            'templates-position-update' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/position", ['position' => 1]);
            }),

            'templates-duplicate-create' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

                return $this->json('POST', "/api/v1/templates/{$template->uuid}/duplicate");
            }),

            'template-versions-index' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

                return $this->json('GET', "/api/v1/templates/{$template->uuid}/versions");
            }),

            'template-versions-create' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
                $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
                $template->structure->update(['published_version_id' => $version->id]);

                return $this->json('POST', "/api/v1/templates/{$template->uuid}/versions");
            }),

            'template-versions-publication-update' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/publication", [
                    'change_note' => 'The first one.',
                ]);
            }),

            'template-versions-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

                return $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1");
            }),

            'template-versions-show' => $this->asSomebody(function (User $user): TestResponse {
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
                $sectionVersion = SectionVersion::factory()->create([
                    'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
                ]);
                StructureVersionSection::factory()->create([
                    'structure_version_id' => $version->id,
                    'structure_version_page_id' => $version->pages()->firstOrFail()->id,
                    'section_version_id' => $sectionVersion->id,
                ]);
                Field::factory()->create(['section_version_id' => $sectionVersion->id]);

                return $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/1");
            }),

            'template-version-pages-create' => $this->asSomebody(function (User $user): TestResponse {
                [$template] = $this->aDraft($user);

                return $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/pages", ['label' => 'Medical']);
            }),

            'template-version-pages-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, $version] = $this->aDraft($user);
                $page = $version->pages()->sole();

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}", ['label' => 'Health']);
            }),

            'template-version-pages-destroy' => $this->asSomebody(function (User $user): TestResponse {
                [$template, $version] = $this->aDraft($user);
                $page = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);

                return $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}");
            }),

            'template-version-pages-position-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, $version] = $this->aDraft($user);
                $page = $version->pages()->sole();

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/pages/{$page->uuid}/position", ['position' => 1]);
            }),

            'template-version-sections-create' => $this->asSomebody(function (User $user): TestResponse {
                [$template] = $this->aDraft($user);
                $section = Section::factory()->create(['account_id' => $user->account_id]);
                $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
                $section->update(['published_version_id' => $sectionVersion->id]);

                return $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", [
                    'section_id' => $section->uuid,
                    'title_override' => 'Where they live',
                ]);
            }),

            'template-version-sections-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}", [
                    'title_override' => 'Where they live',
                ]);
            }),

            'template-version-sections-destroy' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);

                return $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}");
            }),

            'template-version-sections-position-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/position", ['position' => 1]);
            }),

            'template-version-sections-page-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, $version, $row] = $this->aPlacement($user);
                $page = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/page", [
                    'page_id' => $page->uuid,
                ]);
            }),

            'fields-create' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);

                return $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/fields", [
                    'type' => 'text',
                    'label' => 'Nickname',
                ]);
            }),

            'fields-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);
                $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/fields/{$field->id}", [
                    'label' => 'Street name',
                    'help_text' => null,
                    'placeholder' => null,
                ]);
            }),

            'fields-destroy' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);
                $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

                return $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/fields/{$field->id}");
            }),

            'value-lists-index' => $this->asSomebody(function (User $user): TestResponse {
                $list = ValueList::factory()->create(['account_id' => $user->account_id]);
                ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);

                return $this->json('GET', '/api/v1/value-lists');
            }),

            'countries-index' => $this->asSomebody(function (User $user): TestResponse {
                $list = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
                ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => null, 'country_code' => 'JP']);

                return $this->json('GET', '/api/v1/countries');
            }),

            'fields-position-update' => $this->asSomebody(function (User $user): TestResponse {
                [$template, , $row] = $this->aPlacement($user);
                $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

                return $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/fields/{$field->id}/position", ['position' => 1]);
            }),

            'entity-types-index' => $this->asSomebody(function (User $user): TestResponse {
                EntityType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/entity-types');
            }),

            'entity-types-show' => $this->asSomebody(function (User $user): TestResponse {
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/entity-types/{$entityType->uuid}");
            }),

            'entity-types-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/entity-types', [
                'name_singular' => 'Animal',
                'name_plural' => 'Animals',
            ])),

            'entity-types-update' => $this->asSomebody(function (User $user): TestResponse {
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}", [
                    'name_singular' => 'Pet',
                    'name_plural' => 'Pets',
                    'description' => null,
                    'icon' => 'profile',
                    'colour' => '#4f8a49',
                ]);
            }),

            'entity-types-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/entity-types/{$entityType->uuid}");
            }),

            'entity-types-activation' => $this->asSomebody(function (User $user): TestResponse {
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/activation", ['is_active' => false]);
            }),

            'entity-types-position' => $this->asSomebody(function (User $user): TestResponse {
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/entity-types/{$entityType->uuid}/position", ['position' => 1]);
            }),

            'entities-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
                $version = $this->entityDefaultCarrying($vault, $entityType, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
                $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities", ['name' => 'Mose’s goat', 'fields' => [$version->inputFields()->firstOrFail()->id => 'Goat']]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities");
            }),

            'entities-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
                $version = $this->entityDefaultCarrying($vault, $entityType, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities", ['name' => 'Mose’s goat', 'fields' => [$version->inputFields()->firstOrFail()->id => 'Goat']]);
            }),

            'contacts-entities-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
                $version = $this->entityDefaultCarrying($vault, $entityType, ['People' => ['owner' => FieldTypeEnum::Relation]]);
                $owner = $version->inputFields()->firstOrFail();
                $owner->update(['relation_target' => RelationTargetEnum::Contact]);
                $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
                $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities", ['name' => 'Mose’s goat', 'fields' => [$owner->id => $dwight->uuid]]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/entities");
            }),

            'entities-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
                $version = $this->entityDefaultCarrying($vault, $entityType, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
                $species = $version->inputFields()->firstOrFail();
                $id = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities", ['name' => 'Mose’s goat', 'fields' => [$species->id => 'Goat']])->json('data.id');

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/entities/{$id}");
            }),

            'entities-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
                $version = $this->entityDefaultCarrying($vault, $entityType, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
                $species = $version->inputFields()->firstOrFail();
                $id = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/entity-types/{$entityType->uuid}/entities", ['name' => 'Mose’s goat'])->json('data.id');

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/entities/{$id}", ['fields' => [$species->id => 'Sheep']]);
            }),

            'life-event-categories-index' => $this->asSomebody(function (User $user): TestResponse {
                LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/life-event-categories');
            }),

            'life-event-categories-show' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}");
            }),

            'life-event-categories-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/life-event-categories', [
                'name' => 'Volunteering',
            ])),

            'life-event-categories-update' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}", ['name' => 'Bénévolat']);
            }),

            'life-event-categories-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/life-event-categories/{$category->uuid}");
            }),

            'life-event-categories-activation' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/activation", ['is_active' => false]);
            }),

            'life-event-categories-position' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/life-event-categories/{$category->uuid}/position", ['position' => 1]);
            }),

            'life-event-categories-types' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('GET', "/api/v1/life-event-categories/{$category->uuid}/types");
            }),

            'life-event-types-index' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('GET', '/api/v1/life-event-types');
            }),

            'life-event-types-show' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('GET', "/api/v1/life-event-types/{$type->uuid}");
            }),

            'life-event-types-create' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', '/api/v1/life-event-types', [
                    'life_event_category_id' => $category->uuid,
                    'name' => 'Board game night',
                ]);
            }),

            'life-event-types-update' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}", [
                    'life_event_category_id' => $category->uuid,
                    'name' => 'Games evening',
                ]);
            }),

            'life-event-types-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('DELETE', "/api/v1/life-event-types/{$type->uuid}");
            }),

            'life-event-types-activation' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

                return $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/activation", ['is_active' => false]);
            }),

            'life-event-types-position' => $this->asSomebody(function (User $user): TestResponse {
                $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
                $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/position", ['position' => 1]);
            }),

            'life-event-types-fields' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->lifeEventTypeWithAField($user);

                return $this->json('GET', "/api/v1/life-event-types/{$type->uuid}/fields");
            }),

            'life-event-types-fields-update' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->lifeEventTypeWithAField($user);

                return $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/fields", [
                    'groups' => [
                        ['name' => 'Where', 'fields' => [['label' => 'Venue', 'type' => 'text', 'is_required' => true]]],
                    ],
                ]);
            }),

            'life-events-index' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);

                return $this->json('GET', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-events");
            }),

            'life-event-slices-index' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);

                return $this->json('GET', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-event-slices");
            }),

            'life-events-show' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);

                return $this->json('GET', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-events/{$lifeEvent->uuid}");
            }),

            'life-events-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
                $type = $this->lifeEventTypeWithAField($user)->refresh();
                $venue = $type->structure->publishedVersion->inputFields()->firstOrFail();

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/life-events", [
                    'life_event_type' => $type->uuid,
                    'happened_on' => '2026-10-09',
                    'participants' => [$contact->uuid],
                    'fields' => [$venue->id => 'Poor Richard’s'],
                ]);
            }),

            'life-events-update' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);

                return $this->json('PUT', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-events/{$lifeEvent->uuid}", [
                    'life_event_type' => $lifeEvent->lifeEventType->uuid,
                    'happened_on' => '2026-10-10',
                    'participants' => [$lifeEvent->participants->firstOrFail()->participant->uuid],
                ]);
            }),

            'life-events-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);

                return $this->json('DELETE', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-events/{$lifeEvent->uuid}");
            }),

            'life-events-restoration-update' => $this->asSomebody(function (User $user): TestResponse {
                $lifeEvent = $this->aLifeEventWithAValue($user);
                $lifeEvent->delete();

                return $this->json('PUT', "/api/v1/vaults/{$lifeEvent->vault->uuid}/life-events/{$lifeEvent->uuid}/restoration");
            }),

            'relationship-categories-index' => $this->asSomebody(function (User $user): TestResponse {
                RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/relationship-categories');
            }),

            'relationship-categories-show' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}");
            }),

            'relationship-categories-create' => $this->asSomebody(fn (): TestResponse => $this->json('POST', '/api/v1/relationship-categories', [
                'name' => 'Neighbourhood',
            ])),

            'relationship-categories-update' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}", ['name' => 'Voisinage']);
            }),

            'relationship-categories-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/relationship-categories/{$category->uuid}");
            }),

            'relationship-categories-activation' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/activation", ['is_active' => false]);
            }),

            'relationship-categories-position' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/relationship-categories/{$category->uuid}/position", ['position' => 1]);
            }),

            'relationship-types-by-category' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
                RelationshipType::factory()->create([
                    'account_id' => $user->account_id,
                    'relationship_category_id' => $category->id,
                ]);

                return $this->json('GET', "/api/v1/relationship-categories/{$category->uuid}/types");
            }),

            'locations-index' => $this->asSomebody(function (User $user): TestResponse {
                $scranton = Location::factory()->create(['account_id' => $user->account_id, 'address' => '1725 Slough Avenue']);
                Location::factory()->inside($scranton)->create(['emoji' => '📦']);

                return $this->json('GET', '/api/v1/locations');
            }),

            'locations-show' => $this->asSomebody(function (User $user): TestResponse {
                $scranton = Location::factory()->create(['account_id' => $user->account_id, 'address' => '1725 Slough Avenue']);
                $warehouse = Location::factory()->inside($scranton)->create(['emoji' => '📦']);

                return $this->json('GET', "/api/v1/locations/{$warehouse->uuid}");
            }),

            'locations-create' => $this->asSomebody(function (User $user): TestResponse {
                $scranton = Location::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', '/api/v1/locations', [
                    'name' => 'Warehouse',
                    'parent_id' => $scranton->uuid,
                    'emoji' => '📦',
                    'address' => '1725 Slough Avenue',
                ]);
            }),

            'locations-update' => $this->asSomebody(function (User $user): TestResponse {
                $warehouse = Location::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/locations/{$warehouse->uuid}", [
                    'name' => 'Annex',
                    'emoji' => '📦',
                    'address' => '1725 Slough Avenue',
                ]);
            }),

            'locations-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $annex = Location::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/locations/{$annex->uuid}");
            }),

            'possession-types-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->officeEquipment($user);

                return $this->json('GET', '/api/v1/possession-types');
            }),

            'possession-types-show' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('GET', "/api/v1/possession-types/{$type->uuid}");
            }),

            'possession-types-create' => $this->asSomebody(fn (User $user): TestResponse => $this->json('POST', '/api/v1/possession-types', [
                'name' => 'Office equipment',
                'colour' => '#2f7f86',
            ])),

            'possession-types-update' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('PUT', "/api/v1/possession-types/{$type->uuid}", [
                    'name' => 'Office equipment',
                    'colour' => '#2f7f86',
                ]);
            }),

            'possession-types-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}");
            }),

            'possession-types-export' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('GET', "/api/v1/possession-types/{$type->uuid}/export");
            }),

            'possession-types-import' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $file = $this->json('GET', "/api/v1/possession-types/{$type->uuid}/export")->getContent();

                return $this->json('POST', '/api/v1/possession-types/import', ['file' => $file]);
            }),

            'possession-field-groups-create' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('POST', "/api/v1/possession-types/{$type->uuid}/groups", ['name' => 'Warranty']);
            }),

            'possession-field-groups-update' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $group = $type->groups()->firstOrFail();

                return $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}", ['name' => 'Bought']);
            }),

            'possession-field-groups-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $group = $type->groups()->firstOrFail();

                return $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}");
            }),

            'possession-field-groups-position' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $group = $type->groups()->firstOrFail();

                return $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/groups/{$group->uuid}/position", ['position' => 1]);
            }),

            'possession-fields-create' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);

                return $this->json('POST', "/api/v1/possession-types/{$type->uuid}/fields", [
                    'name' => 'Warranty end',
                    'kind' => 'date',
                ]);
            }),

            'possession-fields-update' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $field = $type->standaloneFields()->firstOrFail();

                return $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}", [
                    'name' => 'Condition',
                    'kind' => 'select',
                    'options' => ['New', 'Used'],
                ]);
            }),

            'possession-fields-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $field = $type->standaloneFields()->firstOrFail();

                return $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}");
            }),

            'possession-fields-position' => $this->asSomebody(function (User $user): TestResponse {
                $type = $this->officeEquipment($user);
                $field = $type->standaloneFields()->firstOrFail();

                return $this->json('PUT', "/api/v1/possession-types/{$type->uuid}/fields/{$field->uuid}/position", ['position' => 1]);
            }),

            'possessions-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions");
            }),

            'possessions-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                PossessionValuation::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}");
            }),

            'possessions-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $type = $this->officeEquipment($user);
                $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton']);

                return $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions", [
                    'type_id' => $type->uuid,
                    'name' => 'World’s Best Boss mug',
                    'description' => 'Bought by Michael for Michael, from Spencer Gifts.',
                    'location_id' => $scranton->uuid,
                    'identifier' => 'WBB-0001',
                    'is_featured' => true,
                    'declared_value' => 12.99,
                    'declared_value_currency' => 'usd',
                    'tags' => ['insured'],
                ]);
            }),

            'possessions-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}", [
                    'name' => 'World’s Best Boss mug',
                    'description' => 'Bought by Michael for Michael, from Spencer Gifts.',
                    'location_id' => $mug->location->uuid,
                    'identifier' => 'WBB-0001',
                    'is_featured' => true,
                    'declared_value' => 12.99,
                    'declared_value_currency' => 'usd',
                    'tags' => ['insured'],
                ]);
            }),

            'possessions-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}");
            }),

            'possessions-position' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/position", ['position' => 1]);
            }),

            'possessions-values' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $serialNumber = $mug->possessionType->standaloneFields()->where('name', 'Serial number')->firstOrFail();

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/values", [
                    'values' => [$serialNumber->uuid => 'SG-2005-0324'],
                ]);
            }),

            'possession-tags-index' => $this->asSomebody(function (User $user): TestResponse {
                PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

                return $this->json('GET', '/api/v1/possession-tags');
            }),

            'possession-transactions-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->aPurchaseWithItsInvoice($user)->possession;

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions");
            }),

            'possession-transactions-show' => $this->asSomebody(function (User $user): TestResponse {
                $purchase = $this->aPurchaseWithItsInvoice($user);
                $mug = $purchase->possession;

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions/{$purchase->uuid}");
            }),

            'possession-transactions-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions", [
                    'type' => 'purchase',
                    'occurred_on' => '2005-03-14',
                    'counterparty' => 'Spencer Gifts',
                    'amount' => 12.99,
                    'currency' => 'usd',
                ]);
            }),

            'possession-transactions-update' => $this->asSomebody(function (User $user): TestResponse {
                $purchase = $this->aPurchaseWithItsInvoice($user);
                $mug = $purchase->possession;

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions/{$purchase->uuid}", ['shipping' => 6]);
            }),

            'possession-transactions-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $fee = PossessionTransaction::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions/{$fee->uuid}");
            }),

            'possession-transaction-documents-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $purchase = $this->aPurchaseWithItsInvoice($user);
                $mug = $purchase->possession;

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions/{$purchase->uuid}/documents", [
                    'file' => UploadedFile::fake()->createWithContent('gift-receipt.txt', 'Spencer Gifts, one mug.'),
                ]);
            }),

            'possession-transaction-documents-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $purchase = $this->aPurchaseWithItsInvoice($user);
                $mug = $purchase->possession;

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/transactions/{$purchase->uuid}/documents/{$purchase->documents->first()->uuid}");
            }),

            'possession-insurance-records-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                PossessionInsuranceRecord::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/insurance-records");
            }),

            'possession-insurance-records-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/insurance-records/{$record->uuid}");
            }),

            'possession-insurance-records-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/insurance-records", [
                    'insurer' => 'Sabre Insurance',
                    'status' => 'active',
                    'insured_value' => 20,
                    'currency' => 'usd',
                ]);
            }),

            'possession-insurance-records-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $mug->id]);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/insurance-records/{$record->uuid}", ['status' => 'expired']);
            }),

            'possession-insurance-records-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/insurance-records/{$record->uuid}");
            }),

            'possession-timeline-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->anAppraisalWithItsCertificate($user)->possession;
                PossessionLoan::factory()->returned()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/timeline");
            }),

            'possession-valuations-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->anAppraisalWithItsCertificate($user)->possession;

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations");
            }),

            'possession-valuations-show' => $this->asSomebody(function (User $user): TestResponse {
                $appraisal = $this->anAppraisalWithItsCertificate($user);
                $mug = $appraisal->possession;

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations/{$appraisal->uuid}");
            }),

            'possession-valuations-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations", [
                    'type' => 'appraisal',
                    'valued_on' => '2026-03-14',
                    'amount' => 20,
                    'currency' => 'usd',
                ]);
            }),

            'possession-provenance-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                PossessionProvenanceEvent::factory()->create(['possession_id' => $mug->id, 'possession_transaction_id' => PossessionTransaction::factory()->create(['possession_id' => $mug->id])->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/provenance");
            }),

            'possession-provenance-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/provenance/{$event->uuid}");
            }),

            'possession-provenance-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/provenance", [
                    'type' => 'gift',
                    'occurred_on' => '2005-12-25',
                ]);
            }),

            'possession-provenance-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $mug->id]);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/provenance/{$event->uuid}", [
                    'is_verified' => true,
                ]);
            }),

            'possession-provenance-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/provenance/{$event->uuid}");
            }),

            'possession-loans-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                PossessionLoan::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/loans");
            }),

            'possession-loans-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}");
            }),

            'possession-loans-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/loans", [
                    'direction' => 'lent',
                    'status' => 'active',
                    'contact_id' => Contact::factory()->create(['vault_id' => $mug->vault_id])->uuid,
                    'started_on' => '2024-06-12',
                ]);
            }),

            'possession-loans-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}", [
                    'purpose' => 'The beet harvest party',
                ]);
            }),

            'possession-loans-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}");
            }),

            'possession-location-stays-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                PossessionLocationStay::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/location-stays");
            }),

            'possession-location-stays-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $stay = PossessionLocationStay::factory()->create(['possession_id' => $mug->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/location-stays/{$stay->uuid}");
            }),

            'possession-location-stays-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/location-stays", [
                    'location_id' => Location::factory()->create(['account_id' => $user->account_id])->uuid,
                    'moved_on' => '2024-06-04',
                ]);
            }),

            'possession-location-stays-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $stay = PossessionLocationStay::factory()->create(['possession_id' => $mug->id]);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/location-stays/{$stay->uuid}", [
                    'reason' => 'On the desk',
                ]);
            }),

            'possession-location-stays-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $stay = PossessionLocationStay::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/location-stays/{$stay->uuid}");
            }),

            'possession-valuations-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $valuation = PossessionValuation::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations/{$valuation->uuid}");
            }),

            'possession-valuation-documents-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $appraisal = $this->anAppraisalWithItsCertificate($user);
                $mug = $appraisal->possession;

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations/{$appraisal->uuid}/documents", [
                    'file' => UploadedFile::fake()->createWithContent('listing.txt', 'A mug like this one sold for twenty dollars.'),
                ]);
            }),

            'possession-valuation-documents-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $appraisal = $this->anAppraisalWithItsCertificate($user);
                $mug = $appraisal->possession;

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/valuations/{$appraisal->uuid}/documents/{$appraisal->documents->first()->uuid}");
            }),

            'possession-maintenance-records-index' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $chipped = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Chipped']);
                PossessionMaintenanceRecord::factory()->create(['possession_id' => $mug->id, 'condition_before_id' => $chipped->id, 'condition_after_id' => $chipped->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/maintenance-records");
            }),

            'possession-maintenance-records-show' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $chipped = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Chipped']);
                $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $mug->id, 'condition_before_id' => $chipped->id, 'condition_after_id' => $chipped->id]);

                return $this->json('GET', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/maintenance-records/{$record->uuid}");
            }),

            'possession-maintenance-records-create' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $chipped = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Chipped']);

                return $this->json('POST', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/maintenance-records", [
                    'maintenance_type' => 'cleaning',
                    'title' => 'Hand wash',
                    'performed_on' => '2026-03-12',
                    'condition_before_id' => $chipped->uuid,
                    'condition_after_id' => $chipped->uuid,
                ]);
            }),

            'possession-maintenance-records-update' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $chipped = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Chipped']);
                $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $mug->id, 'condition_before_id' => $chipped->id, 'condition_after_id' => $chipped->id]);

                return $this->json('PUT', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/maintenance-records/{$record->uuid}", ['title' => 'Hand wash']);
            }),

            'possession-maintenance-records-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $mug = $this->worldsBestBossMug($user);
                $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $mug->id]);

                return $this->json('DELETE', "/api/v1/vaults/{$mug->vault->slug}/possessions/{$mug->uuid}/maintenance-records/{$record->uuid}");
            }),

            'knowledge-folders-index' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);
                KnowledgeList::factory()->create(['knowledge_folder_id' => $note->knowledge_folder_id]);

                return $this->json('GET', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/folders");
            }),

            'knowledge-folders-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/folders", ['name' => 'Warehouse']);
            }),

            'knowledge-folders-update' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);
                KnowledgeList::factory()->create(['knowledge_folder_id' => $note->knowledge_folder_id]);

                return $this->json('PUT', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/folders/{$note->folder->uuid}", ['name' => 'Annex']);
            }),

            'knowledge-folders-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);
                $warehouse = KnowledgeFolder::factory()->create(['vault_id' => $note->vault_id]);

                return $this->json('DELETE', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/folders/{$warehouse->uuid}");
            }),

            'knowledge-notes-index' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);

                return $this->json('GET', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/notes");
            }),

            'knowledge-notes-show' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);

                return $this->json('GET', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/notes/{$note->uuid}");
            }),

            'knowledge-notes-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/notes", ['title' => 'Dundies speech', 'body' => "## Best dressed\nPam"]);
            }),

            'knowledge-notes-update' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);

                return $this->json('PUT', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/notes/{$note->uuid}", ['title' => 'Dundies 2026']);
            }),

            'knowledge-notes-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);

                return $this->json('DELETE', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/notes/{$note->uuid}");
            }),

            'knowledge-search' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->dundiesSpeech($user);
                KnowledgeList::factory()->create([
                    'knowledge_folder_id' => $note->knowledge_folder_id,
                    'title' => 'Dundies nominees',
                    'description' => 'Who might win this year.',
                ]);

                return $this->json('GET', "/api/v1/vaults/{$note->folder->vault->slug}/knowledge/search?query=dundies");
            }),

            'knowledge-lists-index' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);

                return $this->json('GET', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists");
            }),

            'knowledge-lists-show' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);

                return $this->json('GET', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}");
            }),

            'knowledge-lists-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists", ['title' => 'Paper clients']);
            }),

            'knowledge-lists-update' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);

                return $this->json('PUT', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}", ['description' => 'Who buys from us.']);
            }),

            'knowledge-lists-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);

                return $this->json('DELETE', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}");
            }),

            'knowledge-list-columns-create' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);

                return $this->json('POST', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/columns", ['name' => 'City']);
            }),

            'knowledge-list-columns-update' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);
                $column = $list->columns()->firstOrFail();

                return $this->json('PUT', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/columns/{$column->uuid}", ['name' => 'Client']);
            }),

            'knowledge-list-columns-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);
                $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City']);

                return $this->json('DELETE', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/columns/{$city->uuid}");
            }),

            'knowledge-list-items-create' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);
                $column = $list->columns()->firstOrFail();

                return $this->json('POST', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/items", ['cells' => [$column->uuid => 'Harper Collins']]);
            }),

            'knowledge-list-items-update' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);
                $column = $list->columns()->firstOrFail();
                $item = $list->items()->firstOrFail();

                return $this->json('PUT', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/items/{$item->uuid}", ['cells' => [$column->uuid => 'Blue Cross']]);
            }),

            'knowledge-list-items-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $list = $this->paperClients($user);
                $item = $list->items()->firstOrFail();

                return $this->json('DELETE', "/api/v1/vaults/{$list->folder->vault->slug}/knowledge/lists/{$list->uuid}/items/{$item->uuid}");
            }),

            'possession-tags-show' => $this->asSomebody(function (User $user): TestResponse {
                $tag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

                return $this->json('GET', "/api/v1/possession-tags/{$tag->uuid}");
            }),

            'possession-tags-create' => $this->asSomebody(fn (User $user): TestResponse => $this->json('POST', '/api/v1/possession-tags', [
                'name' => 'Heirloom',
                'colour' => '#3f5fd0',
            ])),

            'possession-tags-update' => $this->asSomebody(function (User $user): TestResponse {
                $tag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);

                return $this->json('PUT', "/api/v1/possession-tags/{$tag->uuid}", [
                    'name' => 'Insured',
                    'colour' => '#2f7f86',
                ]);
            }),

            'possession-tags-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $tag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/possession-tags/{$tag->uuid}");
            }),

            'possession-conditions-index' => $this->asSomebody(function (User $user): TestResponse {
                PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New']);

                return $this->json('GET', '/api/v1/possession-conditions');
            }),

            'possession-conditions-show' => $this->asSomebody(function (User $user): TestResponse {
                $condition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Like new']);

                return $this->json('GET', "/api/v1/possession-conditions/{$condition->uuid}");
            }),

            'possession-conditions-create' => $this->asSomebody(fn (User $user): TestResponse => $this->json('POST', '/api/v1/possession-conditions', [
                'name' => 'Mint',
                'colour' => '#2f7f86',
                'icon' => 'star',
            ])),

            'possession-conditions-update' => $this->asSomebody(function (User $user): TestResponse {
                $condition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn']);

                return $this->json('PUT', "/api/v1/possession-conditions/{$condition->uuid}", [
                    'name' => 'Well loved',
                    'colour' => '#9a6a24',
                    'icon' => 'heart',
                ]);
            }),

            'possession-conditions-position' => $this->asSomebody(function (User $user): TestResponse {
                PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
                $condition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 2]);

                return $this->json('PUT', "/api/v1/possession-conditions/{$condition->uuid}/position", ['position' => 1]);
            }),

            'possession-conditions-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $condition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/possession-conditions/{$condition->uuid}");
            }),

            'relationship-types-index' => $this->asSomebody(function (User $user): TestResponse {
                RelationshipType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', '/api/v1/relationship-types');
            }),

            'relationship-types-show' => $this->asSomebody(function (User $user): TestResponse {
                $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('GET', "/api/v1/relationship-types/{$type->uuid}");
            }),

            'relationship-types-create' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', '/api/v1/relationship-types', [
                    'relationship_category_id' => $category->uuid,
                    'name' => 'Godparent / Godchild',
                    'role_a' => 'Godparent',
                    'role_b' => 'Godchild',
                    'is_symmetric' => false,
                ]);
            }),

            'relationship-types-update' => $this->asSomebody(function (User $user): TestResponse {
                $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
                $type = RelationshipType::factory()->create([
                    'account_id' => $user->account_id,
                    'relationship_category_id' => $category->id,
                ]);

                return $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}", [
                    'relationship_category_id' => $category->uuid,
                    'name' => 'Voisin',
                    'role_a' => 'Voisin',
                ]);
            }),

            'relationship-types-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/relationship-types/{$type->uuid}");
            }),

            'relationship-types-activation' => $this->asSomebody(function (User $user): TestResponse {
                $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/activation", ['is_active' => false]);
            }),

            'relationship-types-position' => $this->asSomebody(function (User $user): TestResponse {
                $type = RelationshipType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

                return $this->json('PUT', "/api/v1/relationship-types/{$type->uuid}/position", ['position' => 1]);
            }),

            'contacts-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $this->contactWithAPicture($vault)->update(['nickname' => 'Big Tuna']);
                ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Nickname]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts");
            }),

            'contacts-summaries-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-summaries");
            }),

            'contacts-sections-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                [$version, $field] = $this->categoriesTemplate($user);
                $contact = $this->contactWithAPicture($vault);
                $contact->update(['structure_version_id' => $version->id]);
                $section = StructureVersionSection::query()->where('structure_version_id', $version->id)->sole();

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/sections/{$section->id}", [
                    'new_choices' => [$field->id => ['Finer Things Club']],
                ]);
            }),

            'contact-categories-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                [, $field] = $this->categoriesTemplate($user);
                $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $field->value_list_id]);
                $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
                FieldValue::factory()->create(['valuable_type' => Contact::class, 'valuable_id' => $contact->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$item->id]]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-categories");
            }),

            'contact-list-columns-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $work = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']])['Work'];
                ContactListColumn::factory()->field($work, 'company')->create(['vault_id' => $vault->id, 'heading' => 'Employer']);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-list-columns");
            }),

            'contact-list-columns-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $work = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']])['Work'];

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contact-list-columns", [
                    'columns' => [
                        ['source' => 'field', 'section' => $work->uuid, 'field_key' => 'company', 'heading' => 'Employer', 'is_visible' => true],
                        ['source' => 'email', 'heading' => null, 'is_visible' => true],
                    ],
                ]);
            }),

            'contacts-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}");
            }),

            'contacts-me' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);
                $contact->update(['is_me' => true]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/me");
            }),

            'contacts-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = $this->vaultReadyForContacts($user);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", ['first_name' => 'Pam']);
            }),

            'contacts-name-representations-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name-representations");
            }),
            'contacts-name-representations-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name-representations", [
                    'name_representations' => [
                        ['kind' => 'written', 'given_name' => 'Pam', 'family_name' => 'Beesly'],
                        ['kind' => 'transliteration', 'language' => 'ja', 'script' => 'Kana', 'formatted_name' => 'パム・ビーズリー'],
                    ],
                    'display' => 0,
                    'nickname' => 'Pammy',
                    'maiden_name' => null,
                ]);
            }),
            'contacts-name-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", [
                    'prefix' => null,
                    'first_name' => 'Pam',
                    'middle_name' => null,
                    'last_name' => 'Halpert',
                    'suffix' => null,
                    'maiden_name' => 'Beesly',
                    'nickname' => 'Pammy',
                    'name_order' => 'given_first',
                ]);
            }),

            'duplicates-index' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $this->candidatePairIn($vault);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/duplicates");
            }),

            'duplicates-dismissal-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $candidate = $this->candidatePairIn($vault);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/duplicates/{$candidate->id}/dismissal");
            }),

            'contacts-merge-show' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                [$kept, $absorbed] = $this->twoContactsToMergeIn($vault);
                $household = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
                GroupMember::factory()->create(['group_id' => $household->id, 'contact_id' => $kept->id]);
                GroupMember::factory()->create(['group_id' => $household->id, 'contact_id' => $absorbed->id]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$kept->uuid}/merge/{$absorbed->uuid}");
            }),

            'contacts-merge-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                [$kept, $absorbed] = $this->twoContactsToMergeIn($vault);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$kept->uuid}/merge/{$absorbed->uuid}", [
                    'resolutions' => [['field_key' => 'email', 'row' => null, 'keep' => 'kept']],
                ]);
            }),

            'merges-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                [$kept, $absorbed] = $this->twoContactsToMergeIn($vault);
                $merge = new MergeContacts(
                    user: $user,
                    kept: $kept,
                    absorbed: $absorbed,
                    resolutions: [['field_key' => 'email', 'row' => null, 'keep' => 'kept']],
                )->execute();

                return $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/merges/{$merge->uuid}");
            }),

            'contacts-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}");
            }),

            'contacts-favourite-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true]);
            }),

            'contacts-partial-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/partial", ['partial' => true]);
            }),

            'contacts-avatar-update' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
                $this->holdACategory($contact);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar", [
                    'avatar' => UploadedFile::fake()->image('pam.jpg', 400, 400),
                ]);
            }),

            'contacts-avatar-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = $this->contactWithAPicture($vault);

                return $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/avatar");
            }),

            'contacts-template-version-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $template = Template::factory()->create(['account_id' => $user->account_id]);
                $version = StructureVersion::factory()->published()->create([
                    'structure_id' => $template->structure->id,
                    'account_id' => $user->account_id,
                    'version_number' => 1,
                ]);

                $template->structure->update(['published_version_id' => $version->id]);

                $file = File::factory()->create([
                    'account_id' => $vault->account_id,
                    'vault_id' => $vault->id,
                    'mime_type' => 'image/webp',
                ]);
                $contact = Contact::factory()->create([
                    'vault_id' => $vault->id,
                    'account_id' => $user->account_id,
                    'structure_version_id' => $version->id,
                    'avatar_file_id' => $file->id,
                ]);
                $this->holdACategory($contact);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-version", []);
            }),

            'record-kinds-statistics-show' => $this->asSomebody(function (User $user): TestResponse {
                $fixture = AnimalsVault::build($user);

                return $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics");
            }),
            'record-kinds-statistics-fields-show' => $this->asSomebody(function (User $user): TestResponse {
                $fixture = AnimalsVault::build($user);

                return $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics/fields", ['template' => $fixture->pet->uuid]);
            }),
            'record-kinds-template-change-preview-show' => $this->asSomebody(function (User $user): TestResponse {
                $fixture = AnimalsVault::build($user);
                $chip = FieldValue::factory()->detached()->create([
                    'account_id' => $user->account_id,
                    'vault_id' => $fixture->vault->id,
                    'valuable_type' => Entity::class,
                    'valuable_id' => $fixture->horses['Ulysse']->id,
                    'field_id' => $fixture->fields['microchip']->id,
                    'value_string' => '250777',
                ]);
                Reminder::factory()->for($chip, 'fieldValue')->create([
                    'remindable_type' => Entity::class,
                    'name' => 'Microchip renewal',
                    'is_active' => false,
                    'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved,
                    'deactivated_at' => now(),
                ]);
                TemplateChangeItem::factory()->create([
                    'template_change_id' => TemplateChange::factory()->create(['vault_id' => $fixture->vault->id])->id,
                    'record_type' => Entity::class,
                    'record_id' => $fixture->horses['Bijou']->id,
                    'from_version_id' => $fixture->horseV1->id,
                ]);

                return $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-change-preview", [
                    'from' => $fixture->horse->uuid,
                    'to' => $fixture->pet->uuid,
                ]);
            }),
            'record-kinds-template-changes-create' => $this->asSomebody(function (User $user): TestResponse {
                Queue::fake();
                $fixture = AnimalsVault::build($user);

                return $this->json('POST', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-changes", [
                    'from' => $fixture->horse->uuid,
                    'to' => $fixture->pet->uuid,
                ]);
            }),
            'template-changes-show' => $this->asSomebody(function (User $user): TestResponse {
                Queue::fake();
                $fixture = AnimalsVault::build($user);
                $change = new CreateTemplateChange(
                    user: $user,
                    kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
                    source: $fixture->horse,
                    versionNumbers: [],
                    target: $fixture->pet,
                )->execute();
                $fixture->horses['Bijou']->update(['structure_version_id' => $fixture->birdV1->id]);
                $ids = $change->items()->orderBy('id')->pluck('id');
                new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->last())->handle();

                return $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/template-changes/{$change->uuid}");
            }),
            'contacts-template-preview' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $from = Template::factory()->published()->create(['account_id' => $user->account_id])->refresh();
                $to = Template::factory()->published()->create(['account_id' => $user->account_id])->refresh();
                $contact = Contact::factory()->create([
                    'vault_id' => $vault->id,
                    'account_id' => $user->account_id,
                    'structure_version_id' => $from->structure->published_version_id,
                ]);
                $value = FieldValue::factory()->create([
                    'account_id' => $user->account_id,
                    'vault_id' => $vault->id,
                    'valuable_type' => Contact::class,
                    'valuable_id' => $contact->id,
                    'field_id' => Field::factory()->recurringDate()->create(['account_id' => $user->account_id])->id,
                    'value_string' => null,
                    'value_date' => '1979-03-25',
                ]);
                Reminder::factory()->for($value, 'fieldValue')->create();
                $returning = FieldValue::factory()->detached()->create([
                    'account_id' => $user->account_id,
                    'vault_id' => $vault->id,
                    'valuable_type' => Contact::class,
                    'valuable_id' => $contact->id,
                    'field_id' => $this->fieldCarriedBy($to->structure->publishedVersion, FieldTypeEnum::RecurringDate)->id,
                    'value_string' => null,
                    'value_date' => '1979-03-25',
                ]);
                Reminder::factory()->for($returning, 'fieldValue')->create([
                    'is_active' => false,
                    'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved,
                    'deactivated_at' => now(),
                ]);

                return $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template-preview?template={$to->uuid}");
            }),

            'contacts-template-update' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $from = Template::factory()->published()->create(['account_id' => $user->account_id])->refresh();
                $to = Template::factory()->published()->create(['account_id' => $user->account_id])->refresh();
                $file = File::factory()->create([
                    'account_id' => $vault->account_id,
                    'vault_id' => $vault->id,
                    'mime_type' => 'image/webp',
                ]);
                $contact = Contact::factory()->create([
                    'vault_id' => $vault->id,
                    'account_id' => $user->account_id,
                    'structure_version_id' => $from->structure->published_version_id,
                    'avatar_file_id' => $file->id,
                ]);
                $this->holdACategoryCarriedBy($contact, $to->structure->publishedVersion);

                return $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template", ['template' => $to->uuid]);
            }),

            'vaults-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

                return $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}");
            }),

            'reminders-index' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);

                return $this->json('GET', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminders");
            }),

            'reminders-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders", [
                    'name' => 'Ask how the art school application went',
                    'starts_on' => now()->addWeek()->toDateString(),
                    'cadence' => 'monthly',
                    'time' => '09:00',
                ]);
            }),

            'reminders-show' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);

                return $this->json('GET', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminders/{$reminder->uuid}");
            }),

            'reminders-update' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);

                return $this->json('PUT', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminders/{$reminder->uuid}", [
                    'name' => 'Ask how the art school application went',
                    'cadence' => 'yearly',
                    'time' => '10:00',
                ]);
            }),

            'reminders-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);

                return $this->json('DELETE', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminders/{$reminder->uuid}");
            }),

            'reminder-occurrences-index' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);
                ReminderOccurrence::factory()->past()->remembered()->create(['reminder_id' => $reminder->id]);

                return $this->json('GET', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminder-occurrences?category=history");
            }),

            'vault-reminders-index' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);

                return $this->json('GET', "/api/v1/vaults/{$reminder->vault->uuid}/reminders");
            }),

            'vault-reminder-occurrences-index' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);
                ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

                return $this->json('GET', "/api/v1/vaults/{$reminder->vault->uuid}/reminder-occurrences");
            }),

            'reminder-occurrences-answer' => $this->asSomebody(function (User $user): TestResponse {
                $reminder = $this->aManualReminder($user);
                $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

                return $this->json('PUT', "/api/v1/vaults/{$reminder->vault->uuid}/contacts/{$reminder->remindable->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", [
                    'answer' => 'remembered',
                ]);
            }),

            'relationships-index' => $this->asSomebody(function (User $user): TestResponse {
                $relationship = $this->aRelationship($user);

                return $this->json('GET', "/api/v1/vaults/{$relationship->vault->uuid}/contacts/{$relationship->contactA->uuid}/relationships");
            }),

            'relationships-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);
                $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
                $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
                $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$jim->uuid}/relationships", [
                    'relationship_type' => $type->uuid,
                    'other_contact' => $pam->uuid,
                ]);
            }),

            'relationships-show' => $this->asSomebody(function (User $user): TestResponse {
                $relationship = $this->aRelationship($user);

                return $this->json('GET', "/api/v1/vaults/{$relationship->vault->uuid}/contacts/{$relationship->contactA->uuid}/relationships/{$relationship->uuid}");
            }),

            'relationships-update' => $this->asSomebody(function (User $user): TestResponse {
                $relationship = $this->aRelationship($user);

                return $this->json('PUT', "/api/v1/vaults/{$relationship->vault->uuid}/contacts/{$relationship->contactA->uuid}/relationships/{$relationship->uuid}", [
                    'status' => 'ended',
                    'started_on' => '2007-05-10',
                    'ended_on' => '2008-01-15',
                ]);
            }),

            'relationships-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $relationship = $this->aRelationship($user);

                return $this->json('DELETE', "/api/v1/vaults/{$relationship->vault->uuid}/contacts/{$relationship->contactA->uuid}/relationships/{$relationship->uuid}");
            }),

            'groups-index' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups");
            }),

            'groups-show' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}");
            }),

            'groups-create' => $this->asSomebody(function (User $user): TestResponse {
                $vault = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups", [
                    'name' => 'Party planning committee',
                    'started_on' => '2005-01-01',
                    'started_precision' => 'year',
                ]);
            }),

            'groups-update' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('PUT', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}", [
                    'description' => 'Plans every party of the Scranton branch.',
                ]);
            }),

            'groups-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('DELETE', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}");
            }),

            'groups-restoration-update' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $group->delete();

                return $this->json('PUT', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/restoration");
            }),

            'group-members-index' => $this->asSomebody(function (User $user): TestResponse {
                $membership = $this->aGroupMembership($user);

                return $this->json('GET', "/api/v1/vaults/{$membership->group->vault->uuid}/groups/{$membership->group->uuid}/members");
            }),

            'group-members-create' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $angela = Contact::factory()->create(['vault_id' => $group->vault_id, 'account_id' => $user->account_id]);

                return $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/members", [
                    'contact' => $angela->uuid,
                    'role' => 'Chair',
                ]);
            }),

            'group-members-update' => $this->asSomebody(function (User $user): TestResponse {
                $membership = $this->aGroupMembership($user);

                return $this->json('PUT', "/api/v1/vaults/{$membership->group->vault->uuid}/groups/{$membership->group->uuid}/members/{$membership->uuid}", [
                    'left_on' => '2013-05-16',
                    'left_precision' => 'day',
                ]);
            }),

            'group-members-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $membership = $this->aGroupMembership($user);

                return $this->json('DELETE', "/api/v1/vaults/{$membership->group->vault->uuid}/groups/{$membership->group->uuid}/members/{$membership->uuid}");
            }),

            'group-life-events-index' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $contact = Contact::factory()->create(['vault_id' => $group->vault_id, 'account_id' => $user->account_id]);
                $type = $this->lifeEventTypeWithAField($user)->refresh();

                new CreateLifeEvent(
                    user: $user,
                    vault: $group->vault,
                    contact: $contact,
                    lifeEventType: $type,
                    happenedOn: CarbonImmutable::parse('2026-10-09'),
                    values: [$type->structure->publishedVersion->inputFields()->firstOrFail()->id => 'Party budget'],
                    createdFrom: LifeEventSourceEnum::Group,
                    group: $group,
                )->execute();

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/life-events");
            }),

            'group-life-events-create' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $contact = Contact::factory()->create(['vault_id' => $group->vault_id, 'account_id' => $user->account_id]);
                $type = $this->lifeEventTypeWithAField($user)->refresh();
                $agenda = $type->structure->publishedVersion->inputFields()->firstOrFail();

                return $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/life-events", [
                    'life_event_type' => $type->uuid,
                    'happened_on' => '2026-10-09',
                    'participants' => [$contact->uuid],
                    'fields' => [$agenda->id => 'Party budget'],
                ]);
            }),

            'group-notes-index' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->aGroupNote($user);

                return $this->json('GET', "/api/v1/vaults/{$note->group->vault->uuid}/groups/{$note->group->uuid}/notes");
            }),

            'group-notes-show' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->aGroupNote($user);

                return $this->json('GET', "/api/v1/vaults/{$note->group->vault->uuid}/groups/{$note->group->uuid}/notes/{$note->uuid}");
            }),

            'group-notes-create' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/notes", [
                    'body' => 'Angela keeps the budget in the blue binder.',
                ]);
            }),

            'group-notes-update' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->aGroupNote($user);

                return $this->json('PUT', "/api/v1/vaults/{$note->group->vault->uuid}/groups/{$note->group->uuid}/notes/{$note->uuid}", [
                    'body' => 'Phyllis keeps the budget now.',
                ]);
            }),

            'group-notes-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $note = $this->aGroupNote($user);

                return $this->json('DELETE', "/api/v1/vaults/{$note->group->vault->uuid}/groups/{$note->group->uuid}/notes/{$note->uuid}");
            }),

            'group-reminders-index' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders");
            }),

            'group-reminders-show' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}");
            }),

            'group-reminders-create' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);

                return $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders", [
                    'name' => 'Book the conference room for the birthday party',
                    'starts_on' => now()->addWeek()->toDateString(),
                    'cadence' => 'monthly',
                    'time' => '09:00',
                ]);
            }),

            'group-reminders-update' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

                return $this->json('PUT', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}", [
                    'name' => 'Book the conference room for the birthday party',
                    'cadence' => 'yearly',
                    'time' => '10:00',
                ]);
            }),

            'group-reminders-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

                return $this->json('DELETE', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}");
            }),

            'group-reminder-occurrences-index' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();
                ReminderOccurrence::factory()->past()->remembered()->create(['reminder_id' => $reminder->id]);

                return $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminder-occurrences?category=history");
            }),

            'group-reminder-occurrences-answer-update' => $this->asSomebody(function (User $user): TestResponse {
                $group = $this->aGroup($user);
                $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();
                $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

                return $this->json('PUT', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", [
                    'answer' => 'remembered',
                ]);
            }),

            'group-files-index' => $this->asSomebody(function (User $user): TestResponse {
                $attachment = $this->aGroupFile($user);

                return $this->json('GET', "/api/v1/vaults/{$attachment->group->vault->uuid}/groups/{$attachment->group->uuid}/files");
            }),

            'group-files-show' => $this->asSomebody(function (User $user): TestResponse {
                $attachment = $this->aGroupFile($user);

                return $this->json('GET', "/api/v1/vaults/{$attachment->group->vault->uuid}/groups/{$attachment->group->uuid}/files/{$attachment->uuid}");
            }),

            'group-files-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $group = $this->aGroup($user);

                return $this->json('POST', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/files", [
                    'file' => UploadedFile::fake()->createWithContent('office-picnic-schedule.txt', 'Games at noon.'),
                ]);
            }),

            'group-files-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $attachment = $this->aGroupFile($user);

                return $this->json('DELETE', "/api/v1/vaults/{$attachment->group->vault->uuid}/groups/{$attachment->group->uuid}/files/{$attachment->uuid}");
            }),

            'support-requests-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->aSupportRequest($user);

                return $this->json('GET', '/api/v1/support-requests');
            }),

            'support-requests-show' => $this->asSomebody(function (User $user): TestResponse {
                $supportRequest = $this->aSupportRequest($user);

                return $this->json('GET', "/api/v1/support-requests/{$supportRequest->uuid}");
            }),

            'support-requests-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                Administrator::factory()->create();

                return $this->json('POST', '/api/v1/support-requests', [
                    'category' => 'question',
                    'subject' => 'My conference room reminder never arrived',
                    'body' => 'I booked the conference room for the quarterly meeting, but the reminder never arrived.',
                    'files' => [UploadedFile::fake()->create('conference-room-booking.png', 10, 'image/png')],
                ]);
            }),

            'support-request-messages-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $supportRequest = $this->aSupportRequest($user);

                return $this->json('POST', "/api/v1/support-requests/{$supportRequest->uuid}/messages", [
                    'body' => 'Thank you, Jim. Could you also move the one for the Scranton Business Park meeting?',
                    'files' => [UploadedFile::fake()->create('conference-room-booking.png', 10, 'image/png')],
                ]);
            }),

            'support-request-solution-create' => $this->asSomebody(function (User $user): TestResponse {
                $supportRequest = $this->aSupportRequest($user);

                return $this->json('POST', "/api/v1/support-requests/{$supportRequest->uuid}/solution");
            }),

            'admin-support-requests-index' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $this->aSupportRequest(User::factory()->create());

                return $this->withToken($token)->json('GET', '/api/v1/admin/support-requests');
            }),

            'admin-support-requests-show' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $supportRequest = $this->aSupportRequest(User::factory()->create());

                return $this->withToken($token)->json('GET', "/api/v1/admin/support-requests/{$supportRequest->uuid}");
            }),

            'admin-support-request-replies-create' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $supportRequest = $this->aSupportRequest(User::factory()->create());

                return $this->withToken($token)->json('POST', "/api/v1/admin/support-requests/{$supportRequest->uuid}/replies", [
                    'body' => 'Hi Michael. We have moved the reminder to tomorrow at nine.',
                ]);
            }),

            'admin-support-request-notes-create' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $supportRequest = $this->aSupportRequest(User::factory()->create());

                return $this->withToken($token)->json('POST', "/api/v1/admin/support-requests/{$supportRequest->uuid}/notes", [
                    'body' => 'Check with Pam before closing.',
                ]);
            }),

            'admin-support-request-status-update' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $supportRequest = $this->aSupportRequest(User::factory()->create());

                return $this->withToken($token)->json('PUT', "/api/v1/admin/support-requests/{$supportRequest->uuid}/status", [
                    'status' => 'solved',
                ]);
            }),

            'admin-agent-feedback-index' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                AgentFeedbackReport::factory()->create(['user_id' => $administrator->id, 'account_id' => $administrator->account_id, 'proposed_code' => 'return true;']);

                return $this->withToken($token)->json('GET', '/api/v1/admin/agent-feedback');
            }),

            'admin-agent-feedback-show' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $report = AgentFeedbackReport::factory()->create(['user_id' => $administrator->id, 'account_id' => $administrator->account_id]);
                AgentFeedbackNote::factory()->create(['agent_feedback_report_id' => $report->id]);

                return $this->withToken($token)->json('GET', "/api/v1/admin/agent-feedback/{$report->uuid}");
            }),

            'agent-feedback-create' => $this->asSomebody(fn (User $user): TestResponse => $this->json('POST', '/api/v1/agent-feedback', [
                'kind' => 'bug',
                'severity' => 'minor',
                'title' => 'Search ignores accents',
                'observed' => 'Helene does not find Hélène.',
                'expected' => 'It does.',
                'proposed_fix' => 'Fold accents on both sides.',
            ])),

            'admin-agent-feedback-notes-create' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $report = AgentFeedbackReport::factory()->create(['user_id' => $administrator->id, 'account_id' => $administrator->account_id]);

                return $this->withToken($token)->json('POST', "/api/v1/admin/agent-feedback/{$report->uuid}/notes", ['body' => 'Planned.']);
            }),

            'admin-agent-feedback-status-update' => $this->asAnAdministrator(function (User $administrator, string $token): TestResponse {
                $report = AgentFeedbackReport::factory()->create(['user_id' => $administrator->id, 'account_id' => $administrator->account_id]);

                return $this->withToken($token)->json('PUT', "/api/v1/admin/agent-feedback/{$report->uuid}/status", ['status' => 'accepted']);
            }),

            'contact-groups-index' => $this->asSomebody(function (User $user): TestResponse {
                $membership = $this->aGroupMembership($user);

                return $this->json('GET', "/api/v1/vaults/{$membership->group->vault->uuid}/contacts/{$membership->contact->uuid}/groups");
            }),

            'account-imports-show' => $this->asSomebody(function (User $user): TestResponse {
                $import = $this->anImportOfAFileImportedBefore($user, [
                    'status' => AccountImportStatusEnum::Completed,
                    'source_format' => AccountImportSourceEnum::MonicaFour,
                    'summary' => ['contacts' => ['found' => 5, 'left_out' => 0]],
                    'ended_at' => now(),
                ]);
                AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported]);

                return $this->json('GET', "/api/v1/imports/{$import->uuid}");
            }),

            'account-imports-create' => $this->asSomebody(function (User $user): TestResponse {
                Storage::fake('local');
                $file = '{"version": "1.0-preview.1"}';
                $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
                AccountImport::factory()->create([
                    'user_id' => $user->id,
                    'status' => AccountImportStatusEnum::Completed,
                    'source_checksum' => hash('sha256', $file),
                    'vault_id' => $scranton->id,
                ]);

                return $this->json('POST', '/api/v1/imports', [
                    'file' => UploadedFile::fake()->createWithContent('monica.json', $file),
                ]);
            }),

            'account-imports-update' => $this->asSomebody(function (User $user): TestResponse {
                $import = $this->anImportOfAFileImportedBefore($user, [
                    'status' => AccountImportStatusEnum::Read,
                    'source_format' => AccountImportSourceEnum::MonicaFour,
                    'summary' => ['contacts' => ['found' => 5, 'left_out' => 0]],
                ]);
                $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('PUT', "/api/v1/imports/{$import->uuid}", [
                    'vault' => $stamford->uuid,
                    'me_contact' => null,
                ]);
            }),

            'account-imports-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $import = $this->anImportOfAFileImportedBefore($user, [
                    'status' => AccountImportStatusEnum::Writing,
                    'source_format' => AccountImportSourceEnum::MonicaFour,
                    'summary' => ['contacts' => ['found' => 5, 'left_out' => 0]],
                ]);

                return $this->json('DELETE', "/api/v1/imports/{$import->uuid}");
            }),

            'account-imports-contacts-index' => $this->asSomebody(function (User $user): TestResponse {
                $import = AccountImport::factory()->create(['user_id' => $user->id, 'status' => AccountImportStatusEnum::Read]);
                AccountImportItem::factory()->create([
                    'account_import_id' => $import->id,
                    'kind' => 'contact',
                    'payload' => ['properties' => ['first_name' => 'Dwight', 'last_name' => 'Schrute']],
                ]);

                return $this->json('GET', "/api/v1/imports/{$import->uuid}/contacts");
            }),

            'account-imports-report-index' => $this->asSomebody(function (User $user): TestResponse {
                $import = AccountImport::factory()->create(['user_id' => $user->id, 'status' => AccountImportStatusEnum::Completed, 'ended_at' => now()]);
                AccountImportItem::factory()->create([
                    'account_import_id' => $import->id,
                    'kind' => 'reminder',
                    'outcome' => AccountImportOutcomeEnum::LeftOut,
                    'reason' => AccountImportReasonEnum::OneTimeReminderInThePast->value,
                ]);

                return $this->json('GET', "/api/v1/imports/{$import->uuid}/report");
            }),

            'account-exports-index' => $this->asSomebody(function (User $user): TestResponse {
                $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
                AccountExport::factory()->create([
                    'user_id' => $user->id,
                    'vault_ids' => [$scranton->id],
                    'status' => AccountExportStatusEnum::Ready,
                    'size_in_bytes' => 48211,
                    'ready_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]);

                return $this->json('GET', '/api/v1/exports');
            }),

            'account-exports-show' => $this->asSomebody(function (User $user): TestResponse {
                $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
                $export = AccountExport::factory()->create([
                    'user_id' => $user->id,
                    'vault_ids' => [$scranton->id],
                    'status' => AccountExportStatusEnum::Ready,
                    'size_in_bytes' => 48211,
                    'ready_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]);

                return $this->json('GET', "/api/v1/exports/{$export->uuid}");
            }),

            'account-exports-create' => $this->asSomebody(function (User $user): TestResponse {
                $user->withAccessToken($user->createToken('Michael’s script', ['sensitive-fields'])->accessToken);
                Vault::factory()->create(['account_id' => $user->account_id]);

                return $this->json('POST', '/api/v1/exports');
            }),

            'changelog-entries-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid'), screenshotDirectory: base_path('tests/Fixtures/Changelog/Screenshots/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                return $this->json('GET', '/api/v1/changelog-entries');
            }),

            'changelog-entries-seen' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                return $this->json('PUT', '/api/v1/changelog-entries/seen');
            }),

            'changelog-entries-vote-update' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                return $this->json('PUT', '/api/v1/changelog-entries/01M7NBFWK5XNH908RFDGEAJXKN/vote', ['direction' => 'up']);
            }),

            'changelog-entries-vote-destroy' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                ChangelogVote::factory()->create(['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);

                return $this->json('DELETE', '/api/v1/changelog-entries/01M7NBFWK5XNH908RFDGEAJXKN/vote');
            }),

            'changelog-entries-comments-index' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                $comment = ChangelogComment::factory()->create(['user_id' => $user->id, 'entry_id' => '01M7NBFWK5XNH908RFDGEAJXKN']);
                ChangelogCommentReply::factory()->create(['changelog_comment_id' => $comment->id]);

                return $this->json('GET', '/api/v1/changelog-entries/01M7NBFWK5XNH908RFDGEAJXKN/comments');
            }),

            'changelog-entries-comments-create' => $this->asSomebody(function (User $user): TestResponse {
                $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid')));
                CarbonImmutable::setTestNow('2026-11-17 12:00:00');

                return $this->json('POST', '/api/v1/changelog-entries/01M7NBFWK5XNH908RFDGEAJXKN/comments', [
                    'body' => 'Can we book it for the Dundies?',
                    'is_anonymous' => true,
                ]);
            }),

            default => null,
        };
    }

    /** @param array<string, mixed> $attributes */
    private function anImportOfAFileImportedBefore(User $user, array $attributes): AccountImport
    {
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $import = AccountImport::factory()->create(['user_id' => $user->id, ...$attributes]);
        AccountImport::factory()->create([
            'user_id' => $user->id,
            'status' => AccountImportStatusEnum::Completed,
            'source_checksum' => $import->source_checksum,
            'vault_id' => $scranton->id,
        ]);

        return $import;
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $vault;
    }

    private function contactWithAPicture(Vault $vault): Contact
    {
        $file = File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'mime_type' => 'image/webp',
        ]);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'avatar_file_id' => $file->id,
        ]);

        $this->holdACategory($contact);

        return $contact;
    }

    private function holdACategory(Contact $contact): void
    {
        $list = $this->categoriesOf($contact->account_id);
        $item = ValueListItem::factory()->create(['account_id' => $contact->account_id, 'value_list_id' => $list->id]);
        $field = Field::factory()->create(['account_id' => $contact->account_id, 'type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_list_item_ids' => [$item->id],
        ]);
    }

    private function fieldCarriedBy(StructureVersion $version, FieldTypeEnum $type, ?int $valueListId = null): Field
    {
        $version->update(['status' => VersionStatusEnum::Draft]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
            'type' => $type,
            'value_list_id' => $valueListId,
        ]);
        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $sectionVersion->update(['status' => VersionStatusEnum::Published]);
        $version->update(['status' => VersionStatusEnum::Published]);

        return $field;
    }

    private function holdACategoryCarriedBy(Contact $contact, StructureVersion $version): void
    {
        $list = $this->categoriesOf($contact->account_id);
        $item = ValueListItem::factory()->create(['account_id' => $contact->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldCarriedBy($version, FieldTypeEnum::Multiselect, $list->id);

        FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_list_item_ids' => [$item->id],
        ]);
    }

    private function categoriesOf(int $accountId): ValueList
    {
        return ValueList::query()->where('account_id', $accountId)->where('key', ValueListEnum::ContactCategories->value)->first()
            ?? ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $accountId]);
    }

    /** @return array{Template, StructureVersion} */
    private function aDraft(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        return [$template, $version];
    }

    /** @return array{Template, StructureVersion, StructureVersionSection} */
    private function aPlacement(User $user): array
    {
        [$template, $version] = $this->aDraft($user);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ])->id,
        ]);

        return [$template, $version, $row];
    }

    private function aNotification(User $user): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::InApp,
            'sent_at' => now(),
        ]);
    }

    private function aConnectedTelegram(User $user): NotificationChannel
    {
        $channel = $this->aChannel($user, NotificationChannelEnum::Telegram);

        $channel->fill([
            'is_enabled' => true,
            'destination' => '918273645',
            'proven_at' => now(),
        ])->save();

        return $channel;
    }

    private function aTextMessageAwaitingProof(User $user): NotificationChannel
    {
        $channel = $this->aChannel($user, NotificationChannelEnum::Sms);

        $channel->fill([
            'destination' => '+15705550172',
        ])->save();

        return $channel;
    }

    private function aChannel(User $user, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', $channel->value)
            ->firstOrFail();
    }

    private function lifeEventTypeWithAField(User $user): LifeEventType
    {
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        $this->json('PUT', "/api/v1/life-event-types/{$type->uuid}/fields", [
            'groups' => [
                ['name' => 'Where', 'fields' => [['label' => 'Venue', 'type' => 'text', 'is_required' => true]]],
            ],
        ]);

        return $type;
    }

    private function aLifeEventWithAValue(User $user): LifeEvent
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $type = $this->lifeEventTypeWithAField($user)->refresh();
        $venue = $type->structure->publishedVersion->inputFields()->firstOrFail();

        return new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEventType: $type,
            happenedOn: CarbonImmutable::parse('2026-10-09'),
            values: [$venue->id => 'Poor Richard’s'],
        )->execute();
    }

    private function aManualReminder(User $user): Reminder
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        return Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();
    }

    private function aRelationship(User $user): Relationship
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
    }

    private function aGroup(User $user): Group
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Group::factory()->create(['vault_id' => $vault->id]);
    }

    private function aGroupNote(User $user): GroupNote
    {
        return GroupNote::factory()->create(['group_id' => $this->aGroup($user)->id]);
    }

    private function aGroupFile(User $user): GroupFile
    {
        return GroupFile::factory()->create(['group_id' => $this->aGroup($user)->id]);
    }

    private function aGroupMembership(User $user): GroupMember
    {
        return GroupMember::factory()->create(['group_id' => $this->aGroup($user)->id, 'role' => 'Chair']);
    }

    /** @param  callable(User): TestResponse  $call */
    /** @return array{0: StructureVersion, 1: Field} */
    private function categoriesTemplate(User $user): array
    {
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Multiselect, 'label' => 'Category']]);
        $list = $this->categoriesOf($user->account_id);
        $field = Field::query()
            ->whereIn('section_version_id', StructureVersionSection::query()->where('structure_version_id', $version->id)->select('section_version_id'))
            ->sole();
        $field->update(['value_list_id' => $list->id]);

        return [$version, $field->refresh()];
    }

    private function asSomebody(callable $call): TestResponse
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $call($user);
    }

    private function asAnAdministrator(callable $call): TestResponse
    {
        $user = User::factory()->create();

        Administrator::factory()->create(['user_id' => $user->id]);

        $this->app['auth']->forgetGuards();

        return $call($user, $user->createToken('Dundie awards bot', ['*', 'administration'])->plainTextToken);
    }

    private function aSupportRequest(User $user): SupportRequest
    {
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        SupportMessage::factory()->create([
            'support_request_id' => $supportRequest->id,
            'author_user_id' => $user->id,
            'author_name' => $user->name,
        ]);
        SupportStatusChange::factory()->create(['support_request_id' => $supportRequest->id]);
        SupportMessage::factory()->reply()->create(['support_request_id' => $supportRequest->id]);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id]);
        SupportAttachment::factory()->create([
            'support_message_id' => $supportRequest->messages()->oldest('id')->firstOrFail()->id,
        ]);

        return $supportRequest;
    }

    private function loggingIn(): TestResponse
    {
        User::factory()->create([
            'email' => 'dwight@schrutefarms.test',
            'password' => Hash::make('a-very-strong-password'),
        ]);

        return $this->json('POST', '/api/v1/login', [
            'email' => 'dwight@schrutefarms.test',
            'password' => 'a-very-strong-password',
        ]);
    }

    private function aLog(User $user): Log
    {
        return Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'parameters' => ['name' => 'Dunder Mifflin'],
        ]);
    }

    private function anMcpCall(User $user): McpCall
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        return McpCall::factory()->invalidParameters()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'subject_type' => $contact->getMorphClass(),
            'subject_id' => $contact->id,
            'response' => '{"content":[],"isError":true}',
        ]);
    }

    private function anEmail(User $user): EmailSent
    {
        return EmailSent::factory()->delivered()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);
    }

    private function officeEquipment(User $user): PossessionType
    {
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($group)->create([
            'name' => 'Payment method',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['Cash', 'Card'],
        ]);
        PossessionField::factory()->create([
            'possession_type_id' => $type->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used', 'Broken'],
        ]);
        PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Serial number', 'position' => 2]);

        return $type;
    }

    private function dundiesSpeech(User $user): KnowledgeNote
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);

        return KnowledgeNote::factory()->create([
            'knowledge_folder_id' => $folder->id,
            'title' => 'Dundies speech',
            'body' => "## Best dressed\nPam, again.",
        ]);
    }

    private function paperClients(User $user): KnowledgeList
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office'])->id,
            'title' => 'Paper clients',
            'description' => 'Who buys from the Scranton branch.',
        ]);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $name->id, 'value' => 'Blue Cross']);

        return $list;
    }

    private function worldsBestBossMug(User $user): Possession
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = $this->officeEquipment($user);
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton']);
        $mug = Possession::factory()->featured(1)->create([
            'vault_id' => $vault->id,
            'possession_type_id' => $type->id,
            'current_location_id' => $scranton->id,
            'name' => 'World’s Best Boss mug',
            'description' => 'Bought by Michael for Michael, from Spencer Gifts.',
            'identifier' => 'WBB-0001',
            'declared_value' => '12.99',
            'declared_value_currency' => 'usd',
        ]);
        PossessionTransaction::factory()->create([
            'possession_id' => $mug->id,
            'type' => PossessionTransactionTypeEnum::GiftReceived,
            'occurred_on' => '2005-03-24',
            'counterparty' => 'Jim Halpert',
            'amount' => null,
            'currency' => null,
        ]);
        $mug->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));

        PossessionFieldValue::factory()->create([
            'possession_id' => $mug->id,
            'possession_field_id' => PossessionField::query()->where('possession_type_id', $type->id)->where('name', 'Payment method')->firstOrFail()->id,
            'value' => 'Card',
        ]);
        PossessionFieldValue::factory()->create([
            'possession_id' => $mug->id,
            'possession_field_id' => PossessionField::query()->where('possession_type_id', $type->id)->where('name', 'Condition')->firstOrFail()->id,
            'value' => 'Used',
        ]);

        return $mug;
    }

    private function anAppraisalWithItsCertificate(User $user): PossessionValuation
    {
        $mug = $this->worldsBestBossMug($user);
        $appraisal = PossessionValuation::factory()->create(['possession_id' => $mug->id]);
        $appraisal->documents()->attach(File::factory()->used(1)->create([
            'account_id' => $user->account_id,
            'vault_id' => $mug->vault_id,
            'original_name' => 'Appraisal certificate.pdf',
            'mime_type' => 'application/pdf',
        ]));

        return $appraisal->load('documents');
    }

    private function aPurchaseWithItsInvoice(User $user): PossessionTransaction
    {
        $mug = $this->worldsBestBossMug($user);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $mug->id]);
        $purchase->documents()->attach(File::factory()->used(1)->create([
            'account_id' => $user->account_id,
            'vault_id' => $mug->vault_id,
            'original_name' => 'Invoice 4021.pdf',
            'mime_type' => 'application/pdf',
        ]));

        return $purchase->load('documents');
    }
}
