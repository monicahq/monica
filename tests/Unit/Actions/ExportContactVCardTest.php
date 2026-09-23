<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportContactVCard;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ExportContactVCardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_contact_out_as_a_card(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $card = new ExportContactVCard(user: $user, contact: $contact)->execute();

        $this->assertStringContainsString('BEGIN:VCARD', $card);
        $this->assertStringContainsString('VERSION:4.0', $card);
        $this->assertStringContainsString('N:Schrute;Dwight;;;', $card);
        $this->assertStringContainsString('UID:'.$contact->uuid, $card);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactExported);
    }

    #[Test]
    public function it_carries_a_sensitive_value_when_the_reader_reaches_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = $this->contactHolding($user, $vault, $version, '+15705551212');

        $card = new ExportContactVCard(
            user: $user,
            contact: $contact,
            reachesSensitiveFields: true,
        )->execute();

        $this->assertStringContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_leaves_a_sensitive_value_out_when_the_reader_does_not_reach_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = $this->contactHolding($user, $vault, $version, '+15705551212');

        $card = new ExportContactVCard(
            user: $user,
            contact: $contact,
            reachesSensitiveFields: false,
        )->execute();

        $this->assertStringNotContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ExportContactVCard(user: $user, contact: $contact)->execute();
    }

    private function contactHolding(User $user, Vault $vault, StructureVersion $version, string $value): Contact
    {
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
        ]);

        $field = $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->first();

        FieldValue::factory()->create([
            'value_string' => $value,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field instanceof Field ? $field->id : null,
            'field_key' => $field instanceof Field ? $field->key : null,
        ]);

        return $contact;
    }
}
