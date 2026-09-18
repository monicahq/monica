<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Actions\ExportContactVCard;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Log;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class AddressBookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->key = $this->user->createToken('Dwight’s phone')->plainTextToken;
    }

    #[Test]
    public function it_offers_one_address_book_per_vault_named_after_the_vault(): void
    {
        $scranton = Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Scranton']);
        $utica = Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Utica']);

        $response = $this->dav('PROPFIND', 'dav/addressbooks/'.$this->user->email.'/', depth: '1');

        $response->assertStatus(207);
        $response->assertSee($scranton->uuid, escape: false);
        $response->assertSee($utica->uuid, escape: false);
    }

    #[Test]
    public function it_holds_exactly_the_people_the_everyone_grouping_shows(): void
    {
        $vault = $this->vault();
        $everybody = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        $me = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Michael', 'is_me' => true]));
        $archived = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Devon', 'is_archived' => true]));
        $partial = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Mose', 'is_partial' => true]));

        $response = $this->dav('PROPFIND', $this->book($vault), depth: '1');

        $response->assertStatus(207);
        $response->assertSee($everybody->uuid.'.vcf', escape: false);
        $response->assertSee($me->uuid.'.vcf', escape: false);
        $response->assertDontSee($archived->uuid.'.vcf', escape: false);
        $response->assertDontSee($partial->uuid.'.vcf', escape: false);
    }

    #[Test]
    public function it_hands_a_card_carrying_the_name_the_nickname_and_the_values_with_their_labels(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Work email'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
            ['type' => FieldTypeEnum::Number, 'label' => 'Beets grown'],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, [
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'nickname' => 'Beet farmer',
            'structure_version_id' => $version->id,
        ]));
        $this->value($contact, $version, 0, ['value_string' => 'dwight@dundermifflin.test']);
        $this->value($contact, $version, 1, ['value_string' => '+15705551212']);
        $this->value($contact, $version, 2, ['value_string' => null, 'value_number' => '4000']);

        $response = $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf');

        $response->assertOk();
        $card = $response->getContent();
        $this->assertStringContainsString('N:Schrute;Dwight;;;', (string) $card);
        $this->assertStringContainsString('NICKNAME:Beet farmer', (string) $card);
        $this->assertStringContainsString('dwight@dundermifflin.test', (string) $card);
        $this->assertStringContainsString('Work email', (string) $card);
        $this->assertStringContainsString('+15705551212', (string) $card);
        $this->assertStringNotContainsString('4000', (string) $card);
    }

    #[Test]
    public function it_hands_a_birthday_whose_year_is_unknown_without_a_year(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, ['structure_version_id' => $version->id]));
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '1900-02-03',
            'year_is_unknown' => true,
        ]);

        $threeOh = $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf');

        $threeOh->assertOk();
        $this->assertStringContainsString('VERSION:3.0', (string) $threeOh->getContent());
        $this->assertStringContainsString('BDAY;X-APPLE-OMIT-YEAR=1604:1604-02-03', (string) $threeOh->getContent());

        $fourOh = $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf', accept: 'text/vcard; version=4.0');

        $fourOh->assertOk();
        $this->assertStringContainsString('BDAY:--0203', (string) $fourOh->getContent());
    }

    #[Test]
    public function it_keeps_a_sensitive_value_from_a_key_that_was_not_minted_to_reach_it(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, ['structure_version_id' => $version->id]));
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $response = $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf');

        $response->assertOk();
        $this->assertStringNotContainsString('+15705559999', (string) $response->getContent());
    }

    #[Test]
    public function it_hands_a_sensitive_value_to_a_key_minted_to_reach_it(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, ['structure_version_id' => $version->id]));
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $this->key = $this->user->createToken('Dwight’s phone', ['sensitive-fields'])->plainTextToken;

        $response = $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf');

        $response->assertOk();
        $this->assertStringContainsString('+15705559999', (string) $response->getContent());
    }

    #[Test]
    public function it_tells_a_device_only_what_changed_since_it_last_looked(): void
    {
        $vault = $this->vault();
        $stayed = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Stanley']));

        $this->travel(2)->seconds();

        Contact::factory()->create($this->inVault($vault, ['first_name' => 'Kevin']));

        $token = $this->syncToken($vault);

        $this->travel(2)->seconds();

        $renamed = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Andrew']));
        $removed = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Roy']));
        $removed->delete();

        $response = $this->dav('REPORT', $this->book($vault), depth: '1', body: <<<XML
            <?xml version="1.0"?>
            <d:sync-collection xmlns:d="DAV:">
              <d:sync-token>$token</d:sync-token>
              <d:sync-level>1</d:sync-level>
              <d:prop><d:getetag/></d:prop>
            </d:sync-collection>
            XML);

        $response->assertStatus(207);
        $response->assertSee($renamed->uuid.'.vcf', escape: false);
        $response->assertSee($removed->uuid.'.vcf', escape: false);
        $response->assertDontSee($stayed->uuid.'.vcf', escape: false);
    }

    #[Test]
    public function it_answers_not_found_for_a_vault_of_another_account(): void
    {
        $stranger = User::factory()->create();
        $theirs = Vault::factory()->create(['account_id' => $stranger->account_id]);

        $response = $this->dav('PROPFIND', 'dav/addressbooks/'.$this->user->email.'/'.$theirs->uuid.'/', depth: '1');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_writes_nothing_to_the_journal_when_a_device_reads(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));
        $before = Log::query()->count();

        $this->dav('PROPFIND', $this->book($vault), depth: '1');
        $this->dav('GET', $this->book($vault).$contact->uuid.'.vcf');

        $this->assertEquals($before, Log::query()->count());
        $this->assertNull($contact->refresh()->last_consulted_at);
    }

    #[Test]
    public function it_refuses_to_make_or_remove_an_address_book(): void
    {
        $vault = $this->vault();

        $this->dav('MKCOL', 'dav/addressbooks/'.$this->user->email.'/new-vault/')->assertStatus(403);
        $this->dav('DELETE', $this->book($vault))->assertStatus(403);
    }

    private function vault(string $name = 'Scranton'): Vault
    {
        return Vault::factory()->create(['account_id' => $this->user->account_id, 'name' => $name]);
    }

    private function book(Vault $vault): string
    {
        return 'dav/addressbooks/'.$this->user->email.'/'.$vault->uuid.'/';
    }

    private function syncToken(Vault $vault): string
    {
        $response = $this->dav('PROPFIND', $this->book($vault), body: <<<'XML'
            <?xml version="1.0"?>
            <d:propfind xmlns:d="DAV:"><d:prop><d:sync-token/></d:prop></d:propfind>
            XML);

        preg_match('#<d:sync-token>([^<]+)</d:sync-token>#', (string) $response->getContent(), $matches);

        return $matches[1] ?? '';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function inVault(Vault $vault, array $attributes = []): array
    {
        return [
            ...$attributes,
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
        ];
    }

    /** @param  array<string, mixed>  $attributes */
    private function value(Contact $contact, StructureVersion $version, int $index, array $attributes): FieldValue
    {
        $field = $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->all()[$index];

        return FieldValue::factory()->create([
            ...$attributes,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
        ]);
    }

    private function dav(string $method, string $uri, string $depth = '0', string $body = '', ?string $accept = null, string $contentType = 'application/xml', ?string $ifMatch = null): TestResponse
    {
        return $this->call($method, $uri, [], [], [], array_filter([
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->user->email.':'.$this->key),
            'HTTP_DEPTH' => $depth,
            'CONTENT_TYPE' => $contentType,
            'HTTP_ACCEPT' => $accept,
            'HTTP_IF_MATCH' => $ifMatch,
        ]), $body);
    }

    #[Test]
    public function it_creates_a_contact_from_a_card_a_device_saves(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $response = $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $this->card(), contentType: 'text/vcard');

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'dav_uri' => 'dwight.vcf',
        ]);
    }

    #[Test]
    public function it_keeps_what_the_structure_has_no_field_for_and_reads_it_back(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $this->card(), contentType: 'text/vcard')
            ->assertStatus(201);

        $read = $this->dav('GET', $this->book($vault).'dwight.vcf');

        $read->assertOk();
        $this->assertStringContainsString('Schrute Farms', (string) $read->getContent());
        $this->assertStringContainsString('+15705551212', (string) $read->getContent());
    }

    #[Test]
    public function it_writes_a_change_a_device_makes_and_journals_it_as_the_device(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Dwite']));

        $response = $this->dav('PUT', $this->book($vault).$contact->uuid.'.vcf', body: $this->card(), contentType: 'text/vcard');

        $response->assertStatus(204);
        $this->assertEquals('Dwight', $contact->refresh()->first_name);
        $this->assertDatabaseHas('logs', [
            'account_id' => $this->user->account_id,
            'source' => 'dav',
            'source_detail' => 'Dwight’s phone',
        ]);
    }

    #[Test]
    public function a_card_coming_back_under_an_address_it_never_had_updates_the_contact_it_names(): void
    {
        $vault = $this->vault();
        $version = Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, [
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'structure_version_id' => $version->id,
        ]));

        $returning = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:{$contact->uuid}\r\nN:Schrute;Dwight;Kurt;;\r\nFN:Dwight Kurt Schrute\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'a-name-the-phone-chose.vcf', body: $returning, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
        $this->assertSame('Kurt', $contact->refresh()->middle_name);
    }

    #[Test]
    public function a_card_naming_a_contact_of_another_vault_makes_a_new_one_and_leaves_that_contact_alone(): void
    {
        $scranton = $this->vault();
        $utica = $this->vault('Utica');
        Structure::asTheDefaultOf($this->user, $scranton, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $elsewhere = Contact::factory()->create($this->inVault($utica, ['first_name' => 'Dwight', 'last_name' => 'Schrute']));

        $returning = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:{$elsewhere->uuid}\r\nN:Schrute;Dwight;Kurt;;\r\nFN:Dwight Kurt Schrute\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($scranton).'a-name-the-phone-chose.vcf', body: $returning, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $scranton->id)->count());
        $this->assertSame(1, Contact::query()->where('vault_id', $utica->id)->count());
        $this->assertNull($elsewhere->refresh()->middle_name);
    }

    #[Test]
    public function a_card_this_very_product_exported_comes_home_to_the_contact_it_came_from(): void
    {
        $vault = $this->vault();
        $version = Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, [
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'structure_version_id' => $version->id,
        ]));

        $exported = new ExportContactVCard(user: $this->user, contact: $contact, reachesSensitiveFields: true)->execute();

        $this->dav('PUT', $this->book($vault).'whatever-the-phone-called-it.vcf', body: $exported, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
        $this->assertSame('Dwight', $contact->refresh()->first_name);
        $this->assertSame('Schrute', $contact->last_name);
    }

    #[Test]
    public function a_card_naming_nobody_in_the_vault_still_makes_a_contact(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $this->card(), contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_card_with_no_name_at_all(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'nobody.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:nobody\r\nEND:VCARD\r\n");

        $response->assertStatus(415);
        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function it_refuses_a_card_whose_name_is_empty(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'nobody.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:nobody\r\nFN: \r\nN:;;;;\r\nEND:VCARD\r\n");

        $response->assertStatus(400);
        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function it_refuses_a_body_that_is_not_a_card(): void
    {
        $vault = $this->vault();

        $response = $this->dav('PUT', $this->book($vault).'nonsense.vcf', contentType: 'text/vcard', body: 'this is not a card');

        $response->assertStatus(415);
    }

    #[Test]
    public function it_refuses_a_save_over_a_card_that_moved_since_the_device_read_it(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $response = $this->dav(
            'PUT',
            $this->book($vault).$contact->uuid.'.vcf',
            body: $this->card(),
            contentType: 'text/vcard',
            ifMatch: '"an-etag-from-last-week"',
        );

        $response->assertStatus(412);
        $this->assertNotEquals('Dwight', $contact->refresh()->first_name);
    }

    #[Test]
    public function it_removes_a_contact_whose_card_a_device_deletes(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $response = $this->dav('DELETE', $this->book($vault).$contact->uuid.'.vcf');

        $response->assertStatus(204);
        $this->assertSoftDeleted($contact);
    }

    #[Test]
    public function it_refuses_to_remove_the_contact_representing_the_person(): void
    {
        $vault = $this->vault();
        $me = Contact::factory()->create($this->inVault($vault, ['is_me' => true]));

        $response = $this->dav('DELETE', $this->book($vault).$me->uuid.'.vcf');

        $response->assertStatus(403);
        $this->assertNotSoftDeleted($me);
    }

    #[Test]
    public function it_leaves_a_sensitive_field_as_it_was_when_the_key_cannot_reach_it(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create($this->inVault($vault, ['structure_version_id' => $version->id]));
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $this->dav('PUT', $this->book($vault).$contact->uuid.'.vcf', body: $this->card(), contentType: 'text/vcard')
            ->assertStatus(204);

        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $contact->id,
            'value_string' => '+15705559999',
        ]);
    }

    private function card(): string
    {
        return "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nORG:Schrute Farms\r\nTEL:+15705551212\r\nEND:VCARD\r\n";
    }

    #[Test]
    public function it_moves_a_card_into_another_vault_as_a_creation_and_a_removal(): void
    {
        $scranton = $this->vault('Scranton');
        $utica = $this->vault('Utica');
        Structure::asTheDefaultOf($this->user, $utica);
        $contact = Contact::factory()->create($this->inVault($scranton, [
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]));

        $response = $this->call('MOVE', $this->book($scranton).$contact->uuid.'.vcf', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->user->email.':'.$this->key),
            'HTTP_DESTINATION' => url($this->book($utica).$contact->uuid.'.vcf'),
        ]);

        $response->assertStatus(201);
        $this->assertSoftDeleted($contact);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $utica->id,
            'first_name' => 'Dwight',
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_refuses_to_move_the_contact_representing_the_person(): void
    {
        $scranton = $this->vault('Scranton');
        $utica = $this->vault('Utica');
        Structure::asTheDefaultOf($this->user, $utica);
        $me = Contact::factory()->create($this->inVault($scranton, ['is_me' => true, 'first_name' => 'Michael']));

        $response = $this->call('MOVE', $this->book($scranton).$me->uuid.'.vcf', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->user->email.':'.$this->key),
            'HTTP_DESTINATION' => url($this->book($utica).$me->uuid.'.vcf'),
        ]);

        $response->assertStatus(403);
        $this->assertNotSoftDeleted($me);
        $this->assertDatabaseMissing('contacts', ['vault_id' => $utica->id]);
    }
}
