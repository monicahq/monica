<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactNameRepresentations;
use App\Enums\AccountImportStatusEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\UserActionEnum;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\LogUserAction;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactNameRepresentationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_updates_and_deletes_the_ways_a_name_is_written_in_one_go(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');
        $displayed = $contact->displayNameRepresentation;
        $removed = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id, 'formatted_name' => 'Kimmy']);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: [
                ['id' => $displayed->uuid, 'kind' => 'transliteration', 'language' => 'ko', 'script' => 'Latn', 'formatted_name' => 'Kim Min-ji', 'given_name' => 'Min-ji', 'family_name' => 'Kim'],
                ['kind' => 'written', 'language' => 'ko', 'script' => 'Hang', 'formatted_name' => '김민지', 'given_name' => '민지', 'family_name' => '김', 'name_order' => 'family_first'],
            ],
            display: 1,
            nickname: 'Minnie',
            maidenName: null,
        )->execute();

        $this->assertModelMissing($removed);
        $this->assertDatabaseHas('contact_name_representations', ['id' => $displayed->id, 'kind' => 'transliteration', 'script_code' => 'Latn', 'position' => 1]);
        $this->assertDatabaseHas('contact_name_representations', ['contact_id' => $contact->id, 'formatted_name' => '김민지', 'language_tag' => 'ko', 'position' => 2]);

        $contact->refresh();
        $this->assertSame('김민지', $contact->name);
        $this->assertSame('Minnie', $contact->nickname);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactNameUpdate
            && $job->changes['names'] === ['before' => 'Min-ji Kim, Kimmy', 'after' => 'Kim Min-ji, 김민지']);
        Queue::assertPushedOn('low', DetectDuplicateContacts::class);
    }

    #[Test]
    public function it_shows_the_first_name_written_for_a_contact_that_had_none(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed(null, null);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: [['kind' => 'written', 'given_name' => 'Holly', 'family_name' => 'Flax']],
            display: 0,
            nickname: null,
            maidenName: null,
        )->execute();

        $this->assertSame('Holly Flax', $contact->refresh()->name);
    }

    #[Test]
    public function it_leaves_a_contact_nameless_when_every_way_of_writing_its_name_is_taken_away(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Todd', 'Packer');

        new UpdateContactNameRepresentations(user: $user, contact: $contact, nameRepresentations: [], display: null, nickname: null, maidenName: null)->execute();

        $contact->refresh();
        $this->assertNull($contact->display_name_representation_id);
        $this->assertNull($contact->first_name);
        $this->assertTrue($contact->isNameless());
    }

    /** @return array<string, array{0: array<string, mixed>, 1: int|null}> */
    public static function refusedLists(): array
    {
        return [
            'a row holding nothing' => [['kind' => 'written'], 0],
            'a pronunciation with no system' => [['kind' => 'phonetic', 'given_name' => 'Minji'], 0],
            'a pronunciation shown as the name' => [['kind' => 'phonetic', 'given_name' => 'Minji', 'phonetic_system' => 'x-rr'], 0],
            'a system on a written name' => [['kind' => 'written', 'formatted_name' => 'Kim', 'phonetic_system' => 'ipa'], 0],
            'an unknown kind' => [['kind' => 'nickname', 'formatted_name' => 'Kim'], 0],
            'a malformed language' => [['kind' => 'written', 'formatted_name' => 'Kim', 'language' => 'korean language'], 0],
            'a malformed script' => [['kind' => 'written', 'formatted_name' => 'Kim', 'script' => 'LATN'], 0],
            'a display outside the list' => [['kind' => 'written', 'formatted_name' => 'Kim'], 3],
            'no display at all' => [['kind' => 'written', 'formatted_name' => 'Kim'], null],
        ];
    }

    /** @param array<string, mixed> $row */
    #[Test]
    #[DataProvider('refusedLists')]
    public function it_refuses_a_list_it_cannot_keep(array $row, ?int $display): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactNameRepresentations(user: $user, contact: $contact, nameRepresentations: [$row], display: $display, nickname: null, maidenName: null)->execute();
    }

    #[Test]
    public function it_refuses_a_way_of_writing_that_belongs_to_another_contact(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');
        $other = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: [['id' => $other->displayNameRepresentation->uuid, 'kind' => 'written', 'formatted_name' => 'Stolen']],
            display: 0,
            nickname: null,
            maidenName: null,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_pronunciation_holding_a_full_name(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactNameRepresentations(user: $user, contact: $contact, nameRepresentations: [
            ['kind' => 'written', 'given_name' => 'Min-ji', 'family_name' => 'Kim'],
            ['kind' => 'phonetic', 'phonetic_system' => 'x-rr', 'formatted_name' => 'Gim Minji'],
        ], display: 0, nickname: null, maidenName: null)->execute();
    }

    #[Test]
    public function it_refuses_the_same_way_of_writing_sent_twice(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');
        $uuid = $contact->displayNameRepresentation->uuid;

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactNameRepresentations(user: $user, contact: $contact, nameRepresentations: [
            ['id' => $uuid, 'kind' => 'written', 'given_name' => 'Min-ji', 'family_name' => 'Kim'],
            ['id' => $uuid, 'kind' => 'written', 'formatted_name' => '김민지'],
        ], display: 0, nickname: null, maidenName: null)->execute();
    }

    #[Test]
    public function it_keeps_what_a_device_named_a_way_of_writing_when_the_caller_does_not_say(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');
        $displayed = $contact->displayNameRepresentation;
        $displayed->update(['vcard_alt_id' => 'phone', 'vcard_property_id' => 'abc-123']);

        new UpdateContactNameRepresentations(user: $user, contact: $contact, nameRepresentations: [
            ['id' => $displayed->uuid, 'kind' => 'written', 'given_name' => 'Min-ji', 'family_name' => 'Kim'],
        ], display: 0, nickname: 'Minnie', maidenName: null)->execute();

        $this->assertDatabaseHas('contact_name_representations', ['id' => $displayed->id, 'vcard_alt_id' => 'phone', 'vcard_property_id' => 'abc-123']);
    }

    #[Test]
    public function it_refuses_more_than_twenty_ways_of_writing_a_name(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Kim', 'Min-ji');

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: array_fill(0, 21, ['kind' => 'written', 'formatted_name' => 'Kim']),
            display: 0,
            nickname: null,
            maidenName: null,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_account(): void
    {
        [, $contact] = $this->contactNamed('Kim', 'Min-ji');
        $stranger = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactNameRepresentations(user: $stranger, contact: $contact, nameRepresentations: [], display: null, nickname: null, maidenName: null)->execute();
    }

    #[Test]
    public function it_stores_the_kind_as_its_enum(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed(null, null);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: [
                ['kind' => 'written', 'formatted_name' => '김민지'],
                ['kind' => 'phonetic', 'phonetic_system' => 'x-rr', 'given_name' => 'Minji', 'family_name' => 'Gim'],
            ],
            display: 0,
            nickname: null,
            maidenName: null,
        )->execute();

        $this->assertSame(ContactNameKindEnum::Phonetic, $contact->nameRepresentations()->get()->last()->kind);
    }

    #[Test]
    public function it_looks_for_no_duplicate_while_an_import_writes_into_the_vault(): void
    {
        Queue::fake();

        [$user, $contact] = $this->contactNamed('Halpert', 'Jim');
        AccountImport::factory()->create(['user_id' => $user->id, 'status' => AccountImportStatusEnum::Writing, 'vault_id' => $contact->vault_id]);

        new UpdateContactNameRepresentations(
            user: $user,
            contact: $contact,
            nameRepresentations: [
                ['kind' => 'written', 'language' => 'en', 'formatted_name' => 'Jim Halpert', 'given_name' => 'Jim', 'family_name' => 'Halpert'],
            ],
            display: 0,
            nickname: null,
            maidenName: null,
        )->execute();

        Queue::assertNotPushed(DetectDuplicateContacts::class);
    }

    /** @return array{0: User, 1: Contact} */
    private function contactNamed(?string $familyName, ?string $givenName): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => $givenName,
            'last_name' => $familyName,
        ]);

        return [$user, $contact];
    }
}
