<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Actions\CreateDefaultValueLists;
use App\Actions\CreateGroupMembership;
use App\Actions\ExportContactVCard;
use App\Enums\ContactNameKindEnum;
use App\Enums\FieldTypeEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Log;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Reader;
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
    public function it_hands_a_card_carrying_the_name_the_nickname_and_the_values(): void
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
        $this->assertStringContainsString('+15705551212', (string) $card);
        $this->assertStringNotContainsString('4000', (string) preg_replace('/^(UID|REV):.*$/m', '', (string) $card));
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

    private function dav(string $method, string $uri, string $depth = '0', string $body = '', ?string $accept = null, string $contentType = 'application/xml', ?string $ifMatch = null, ?string $prefer = null): TestResponse
    {
        return $this->call($method, $uri, [], [], [], array_filter([
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($this->user->email.':'.$this->key),
            'HTTP_DEPTH' => $depth,
            'CONTENT_TYPE' => $contentType,
            'HTTP_ACCEPT' => $accept,
            'HTTP_IF_MATCH' => $ifMatch,
            'HTTP_PREFER' => $prefer,
        ]), $body);
    }

    #[Test]
    public function it_hands_back_every_way_a_name_is_written_to_a_client_reading_vcard_4(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $this->dav('PUT', $this->book($vault).'minji.vcf', body: (string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf')), contentType: 'text/vcard')
            ->assertStatus(201);

        $read = (string) $this->dav('GET', $this->book($vault).'minji.vcf', accept: 'text/vcard; version=4.0')->getContent();
        $unfolded = str_replace("\r\n ", '', $read);

        $this->assertSame(4, substr_count($unfolded, "\r\nN;"));
        $this->assertStringContainsString('김민지', $unfolded);
        $this->assertStringContainsString('Kim Min-ji', $unfolded);
        $this->assertStringContainsString('金敏智', $unfolded);
        $this->assertStringContainsString('PHONETIC=x-rr', $unfolded);
    }

    #[Test]
    public function a_device_writing_one_name_takes_no_other_away(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $this->dav('PUT', $this->book($vault).'minji.vcf', body: (string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf')), contentType: 'text/vcard')
            ->assertStatus(201);
        $this->dav('PUT', $this->book($vault).'minji.vcf', body: (string) file_get_contents(base_path('tests/Fixtures/Dav/single-name-client.vcf')), contentType: 'text/vcard')
            ->assertSuccessful();

        $contact = Contact::query()->where('vault_id', $vault->id)->sole();
        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('Minnie Kim', $contact->name);
    }

    #[Test]
    public function a_device_saving_every_name_as_json_updates_the_contact(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $this->dav('PUT', $this->book($vault).'minji.vcf', body: (string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf')), contentType: 'text/vcard')
            ->assertStatus(201);

        $read = (string) $this->dav('GET', $this->book($vault).'minji.vcf', accept: 'text/vcard; version=4.0')->getContent();
        $changed = str_replace('Kim Min-ji', 'Kim Minji', str_replace("\r\n ", '', $read));

        $this->dav('PUT', $this->book($vault).'minji.vcf', body: (string) json_encode(Reader::read($changed)->jsonSerialize()), contentType: 'application/vcard+json')
            ->assertSuccessful();

        $contact = Contact::query()->where('vault_id', $vault->id)->sole();
        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('Kim Minji', $contact->nameFor(kind: ContactNameKindEnum::Transliteration)?->displayText());
        $this->assertSame('김민지', $contact->name);
    }

    #[Test]
    public function it_accepts_two_ways_of_writing_a_name_without_a_full_name(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:michael-scott\r\nN;ALTID=1:Scott;Michael;;;\r\nN;ALTID=1;LANGUAGE=ja:スコット;マイケル;;;\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'michael.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(201);

        $contact = Contact::query()->where('vault_id', $vault->id)->sole();
        $this->assertSame(2, $contact->nameRepresentations()->count());
        $this->assertSame('Michael Scott', $contact->name);
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
    public function it_says_so_when_the_vault_has_no_template_for_new_contacts(): void
    {
        $vault = $this->vault();

        $response = $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $this->card(), contentType: 'text/vcard');

        $response->assertStatus(403);
        $this->assertDatabaseCount('contacts', 0);
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
    public function a_contact_a_device_created_keeps_the_uid_the_device_gave_it_and_not_its_uuid(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $this->card(), contentType: 'text/vcard')
            ->assertStatus(201);

        $contact = Contact::query()->where('vault_id', $vault->id)->sole();
        $this->assertNotSame('dwight', $contact->uuid);

        $served = $this->dav('GET', $this->book($vault).'dwight.vcf');
        $this->assertStringContainsString("UID:dwight\r\n", (string) $served->getContent());

        $this->dav('PUT', $this->book($vault).'dwight-again.vcf', body: $this->card(), contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(2, Contact::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_refuses_a_card_with_no_name_at_all(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'nobody.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:nobody\r\nEND:VCARD\r\n");

        $response->assertStatus(400);
        $this->assertDatabaseCount('contacts', 0);
    }

    #[Test]
    public function it_names_a_card_holding_only_a_phone_number_after_the_number(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'unknown.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:3.0\r\nTEL:+15705550100\r\nEND:VCARD\r\n");

        $response->assertStatus(201);
        $this->assertSame('+15705550100', Contact::query()->where('vault_id', $vault->id)->sole()->name);
    }

    #[Test]
    public function it_refuses_a_body_that_is_not_a_card(): void
    {
        $vault = $this->vault();

        $response = $this->dav('PUT', $this->book($vault).'nonsense.vcf', contentType: 'text/vcard', body: 'this is not a card');

        $response->assertStatus(415);
    }

    #[Test]
    public function it_accepts_a_vcard_2_1_card(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'pam.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:2.1\r\nN:Beesly;Pam;;;\r\nTEL;CELL:+15705550200\r\nEND:VCARD\r\n");

        $response->assertStatus(201);
        $this->assertSame('Pam', Contact::query()->where('vault_id', $vault->id)->sole()->first_name);
    }

    #[Test]
    public function it_hands_back_a_line_it_could_not_read_as_it_was_sent(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $this->dav('PUT', $this->book($vault).'angela.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nthis line has no colon\r\nEND:VCARD\r\n")
            ->assertStatus(201);

        $response = $this->dav('GET', $this->book($vault).'angela.vcf', accept: 'text/vcard; version=4.0');

        $response->assertOk();
        $this->assertStringContainsString("\r\nthis line has no colon\r\n", (string) $response->getContent());
    }

    #[Test]
    public function it_writes_every_email_address_phone_number_and_address_on_the_default_template(): void
    {
        $vault = $this->vault();
        new CreateDefaultValueLists(user: $this->user)->execute();
        new CreateDefaultTemplate(user: $this->user)->handle();

        $response = $this->dav('PUT', $this->book($vault).'dwight.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:3.0\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\n"
            ."EMAIL;TYPE=work:dwight@dundermifflin.test\r\nEMAIL;TYPE=home:dwight@schrutefarms.test\r\n"
            ."TEL;TYPE=work:+15705551212\r\nTEL;TYPE=home:+15705550100\r\n"
            ."ADR;TYPE=work:;;1725 Slough Avenue;Scranton;PA;18505;USA\r\nADR;TYPE=home:;;Schrute Farms;Honesdale;PA;18431;USA\r\nEND:VCARD\r\n");

        $response->assertStatus(201);
        $this->assertSame(6, Contact::query()->where('vault_id', $vault->id)->sole()->fieldValues()->count());
    }

    #[Test]
    public function it_still_refuses_a_vcard_2_1_card_when_the_client_asks_for_strict_handling(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault);

        $response = $this->dav('PUT', $this->book($vault).'pam.vcf', contentType: 'text/vcard', body: "BEGIN:VCARD\r\nVERSION:2.1\r\nN:Beesly;Pam;;;\r\nFN:Pam Beesly\r\nEND:VCARD\r\n", prefer: 'handling=strict');

        $response->assertStatus(415);
        $this->assertDatabaseCount('contacts', 0);
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

    #[Test]
    public function it_finds_a_card_under_the_name_a_device_chose_and_not_under_its_uuid(): void
    {
        $vault = $this->vault();
        $named = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Dwight', 'dav_uri' => 'dwight.vcf']));

        $this->dav('GET', $this->book($vault).'dwight.vcf')->assertOk();
        $this->dav('GET', $this->book($vault).$named->uuid.'.vcf')->assertNotFound();
    }

    #[Test]
    public function it_hands_several_cards_at_once_and_only_those_asked_for(): void
    {
        $vault = $this->vault();
        $jim = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        Contact::factory()->create($this->inVault($vault, ['first_name' => 'Pam', 'dav_uri' => 'pam.vcf']));
        Contact::factory()->create($this->inVault($vault, ['first_name' => 'Toby']));
        $archived = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Devon', 'is_archived' => true]));

        $response = $this->multiget($vault, [$jim->uuid.'.vcf', 'pam.vcf', $archived->uuid.'.vcf']);

        $response->assertStatus(207);
        $response->assertSee('FN:Jim', escape: false);
        $response->assertSee('FN:Pam', escape: false);
        $response->assertDontSee('FN:Toby', escape: false);
        $response->assertDontSee('FN:Devon', escape: false);
    }

    #[Test]
    public function it_reads_a_batch_of_cards_in_a_number_of_queries_that_does_not_grow_with_the_batch(): void
    {
        $vault = $this->vault();
        $version = Structure::withFields($this->user, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Work email'],
        ]);
        $make = fn (int $count): array => Contact::factory()->count($count)
            ->create($this->inVault($vault, ['structure_version_id' => $version->id]))
            ->map(fn (Contact $contact): string => $contact->uuid.'.vcf')
            ->all();

        $few = $make(2);
        $many = $make(10);

        $this->multiget($vault, $few);
        DB::enableQueryLog();
        $this->multiget($vault, $few)->assertStatus(207);
        $forFew = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->multiget($vault, $many)->assertStatus(207);
        $forMany = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($forFew, $forMany);
    }

    /** @param  array<int, string>  $cardUris */
    private function multiget(Vault $vault, array $cardUris): TestResponse
    {
        $hrefs = implode('', array_map(
            fn (string $uri): string => '<d:href>/'.$this->book($vault).$uri.'</d:href>',
            $cardUris,
        ));

        return $this->dav('REPORT', $this->book($vault), depth: '1', body: <<<XML
            <?xml version="1.0"?>
            <c:addressbook-multiget xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav">
              <d:prop><d:getetag/><c:address-data/></d:prop>
              $hrefs
            </c:addressbook-multiget>
            XML);
    }

    #[Test]
    public function it_hands_a_group_card_naming_its_current_members(): void
    {
        $vault = $this->vault();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
        $jim = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id]);

        $this->dav('PROPFIND', $this->book($vault), depth: '1')->assertSee($group->uuid.'.vcf', escape: false);

        $fourOh = $this->dav('GET', $this->book($vault).$group->uuid.'.vcf', accept: 'text/vcard; version=4.0');

        $fourOh->assertOk();
        $this->assertStringContainsString('KIND:group', (string) $fourOh->getContent());
        $this->assertStringContainsString('FN:Halpert household', (string) $fourOh->getContent());
        $this->assertStringContainsString("MEMBER:urn:uuid:{$jim->uuid}", (string) $fourOh->getContent());

        $threeOh = $this->dav('GET', $this->book($vault).$group->uuid.'.vcf');

        $threeOh->assertOk();
        $this->assertSame(1, substr_count((string) $threeOh->getContent(), "X-ADDRESSBOOKSERVER-MEMBER:urn:uuid:{$jim->uuid}"));
        $this->assertSame(1, substr_count(mb_strtolower((string) $threeOh->getContent()), 'x-addressbookserver-kind:group'));
    }

    #[Test]
    public function a_group_card_a_device_saves_makes_a_group_and_no_contact(): void
    {
        $vault = $this->vault();
        $jim = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        $pam = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Pam']));

        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:halperts-from-the-phone\r\nKIND:group\r\nFN:Halpert household\r\nMEMBER:urn:uuid:{$jim->uuid}\r\nMEMBER:urn:uuid:{$pam->uuid}\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'halperts.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(2, Contact::query()->where('vault_id', $vault->id)->count());
        $group = Group::query()->where('vault_id', $vault->id)->sole();
        $this->assertSame('Halpert household', $group->name);
        $this->assertSame('halperts.vcf', $group->dav_uri);
        $this->assertEqualsCanonicalizing([$jim->id, $pam->id], $group->currentMembers()->pluck('contact_id')->all());
        $this->dav('GET', $this->book($vault).'halperts.vcf')->assertOk();
    }

    #[Test]
    public function a_group_card_in_the_apple_form_makes_a_group_too(): void
    {
        $vault = $this->vault();
        $dwight = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Dwight']));

        $card = "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:farm\r\nN:Schrute farm;;;;\r\nFN:Schrute farm\r\nX-ADDRESSBOOKSERVER-KIND:group\r\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:{$dwight->uuid}\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'farm.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
        $group = Group::query()->where('vault_id', $vault->id)->sole();
        $this->assertSame([$dwight->id], $group->currentMembers()->pluck('contact_id')->all());
    }

    #[Test]
    public function a_member_a_device_removes_from_a_group_card_leaves_the_group_that_day(): void
    {
        $this->travelTo('2026-10-29 10:00:00');
        $vault = $this->vault();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
        $jim = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        $pam = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Pam']));
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id]);
        $leaving = GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $pam->id, 'role' => 'Mother']);

        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:{$group->uuid}\r\nKIND:group\r\nFN:The Halperts\r\nMEMBER:urn:uuid:{$jim->uuid}\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).$group->uuid.'.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(204);

        $this->assertSame('The Halperts', $group->refresh()->name);
        $this->assertSame('2026-10-29', $leaving->refresh()->left_on?->format('Y-m-d'));
        $this->assertSame('Mother', $leaving->role);
    }

    #[Test]
    public function it_deletes_a_group_whose_card_a_device_deletes_and_leaves_it_restorable(): void
    {
        $vault = $this->vault();
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $jim = Contact::factory()->create($this->inVault($vault));
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id]);

        $this->dav('DELETE', $this->book($vault).$group->uuid.'.vcf')->assertStatus(204);

        $this->assertSoftDeleted($group);
        $this->assertNotSoftDeleted($jim);
        $this->assertSame(1, GroupMember::query()->where('group_id', $group->id)->count());
    }

    #[Test]
    public function it_moves_the_sync_token_when_somebody_joins_a_group(): void
    {
        $vault = $this->vault();
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $jim = Contact::factory()->create($this->inVault($vault));
        $before = $this->syncToken($vault);

        $this->travel(5)->seconds();
        new CreateGroupMembership(user: $this->user, vault: $vault, group: $group, contact: $jim)->execute();

        $this->assertNotSame($before, $this->syncToken($vault));
    }

    #[Test]
    public function it_never_reads_categories_on_a_contact_card_as_a_membership(): void
    {
        $vault = $this->vault();
        Structure::asTheDefaultOf($this->user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm']);

        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nCATEGORIES:Schrute farm\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'dwight.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(201);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
        $this->assertSame(1, Group::query()->where('vault_id', $vault->id)->count());
        $this->assertSame(0, GroupMember::query()->count());
    }

    #[Test]
    public function it_serves_and_accepts_no_group_card_while_groups_are_off(): void
    {
        $vault = $this->vault();
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $jim = Contact::factory()->create($this->inVault($vault));
        $this->user->account->features()->deactivate('groups');
        Feature::flushCache();

        $this->dav('PROPFIND', $this->book($vault), depth: '1')->assertDontSee($group->uuid.'.vcf', escape: false);
        $this->dav('GET', $this->book($vault).$group->uuid.'.vcf')->assertNotFound();

        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:halperts\r\nKIND:group\r\nFN:Halpert household\r\nMEMBER:urn:uuid:{$jim->uuid}\r\nEND:VCARD\r\n";

        $this->dav('PUT', $this->book($vault).'halperts.vcf', body: $card, contentType: 'text/vcard')
            ->assertStatus(403);

        $this->assertSame(1, Contact::query()->where('vault_id', $vault->id)->count());
        $this->assertSame(1, Group::query()->where('vault_id', $vault->id)->count());
    }
}
