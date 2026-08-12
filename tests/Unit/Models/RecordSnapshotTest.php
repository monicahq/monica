<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SnapshotReasonEnum;
use App\Exceptions\SnapshotIsImmutable;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\RecordSnapshot;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $snapshot = RecordSnapshot::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($snapshot->account()->exists());
        $this->assertTrue($account->is($snapshot->account));
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $snapshot = RecordSnapshot::factory()->create([
            'snapshotable_id' => $contact->id,
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($snapshot->vault()->exists());
        $this->assertTrue($vault->is($snapshot->vault));
    }

    #[Test]
    public function it_belongs_to_the_record_it_captured(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        $snapshot = RecordSnapshot::factory()->create([
            'snapshotable_type' => Entity::class,
            'snapshotable_id' => $entity->id,
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'template_version_id' => $entity->template_version_id,
        ]);

        $this->assertTrue($entity->is($snapshot->snapshotable));
    }

    #[Test]
    public function it_belongs_to_the_structure_the_record_was_on(): void
    {
        $version = TemplateVersion::factory()->published()->create();
        $contact = Contact::factory()->create(['template_version_id' => $version->id]);
        $snapshot = RecordSnapshot::factory()->create(['snapshotable_id' => $contact->id]);

        $this->assertTrue($snapshot->templateVersion()->exists());
        $this->assertTrue($version->is($snapshot->templateVersion));
    }

    #[Test]
    public function it_belongs_to_whoever_triggered_it(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $snapshot = RecordSnapshot::factory()->create([
            'snapshotable_id' => $contact->id,
            'created_by_user_id' => $user->id,
        ]);

        $this->assertTrue($snapshot->createdBy()->exists());
        $this->assertTrue($user->is($snapshot->createdBy));
    }

    #[Test]
    public function it_belongs_to_nobody_when_the_product_took_it_on_its_own(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $this->assertNull($snapshot->created_by_user_id);
        $this->assertNull($snapshot->createdBy);
    }

    #[Test]
    public function it_casts_the_reason_the_payload_and_the_dates(): void
    {
        $snapshot = RecordSnapshot::factory()->create([
            'reason' => SnapshotReasonEnum::TemplateChange,
            'payload' => ['record' => ['first_name' => 'Michael']],
            'file_ids' => [4, 8],
            'restored_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $snapshot->refresh();

        $this->assertEquals(SnapshotReasonEnum::TemplateChange, $snapshot->reason);
        $this->assertEquals(['record' => ['first_name' => 'Michael']], $snapshot->payload);
        $this->assertEquals([4, 8], $snapshot->file_ids);
        $this->assertInstanceOf(Carbon::class, $snapshot->restored_at);
        $this->assertInstanceOf(Carbon::class, $snapshot->expires_at);
    }

    #[Test]
    public function it_refuses_to_have_the_state_it_captured_rewritten(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $this->expectException(SnapshotIsImmutable::class);

        $snapshot->payload = ['record' => ['first_name' => 'Somebody else']];
        $snapshot->save();
    }

    #[Test]
    public function it_lets_the_note_of_when_it_was_used_to_restore_be_written(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $snapshot->restored_at = now();
        $snapshot->save();

        $this->assertNotNull($snapshot->fresh()->restored_at);
    }

    #[Test]
    public function it_refuses_to_be_deleted(): void
    {
        $snapshot = RecordSnapshot::factory()->create();

        $this->expectException(SnapshotIsImmutable::class);

        $snapshot->delete();
    }

    #[Test]
    public function it_names_every_file_a_live_snapshot_still_points_at(): void
    {
        RecordSnapshot::factory()->holding([4, 8])->create();
        RecordSnapshot::factory()->holding([8, 15])->create();

        $this->assertEqualsCanonicalizing([4, 8, 15], RecordSnapshot::fileIdsInUse());
    }

    #[Test]
    public function it_names_no_file_when_no_snapshot_points_at_one(): void
    {
        RecordSnapshot::factory()->create();

        $this->assertEquals([], RecordSnapshot::fileIdsInUse());
    }
}
