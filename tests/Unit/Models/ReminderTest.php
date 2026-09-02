<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_and_to_a_vault(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertTrue($reminder->account()->exists());
        $this->assertTrue($reminder->vault()->exists());
    }

    #[Test]
    public function it_reads_one_field_and_one_exact_value(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertTrue($reminder->field()->exists());
        $this->assertTrue($reminder->fieldValue()->exists());
        $this->assertEquals($reminder->fieldValue->field_id, $reminder->field_id);
    }

    #[Test]
    public function it_is_about_a_contact(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertInstanceOf(Contact::class, $reminder->remindable);
        $this->assertEquals($reminder->remindable->vault_id, $reminder->vault_id);
    }

    #[Test]
    public function it_knows_who_created_it(): void
    {
        $user = User::factory()->create();

        $reminder = Reminder::factory()->create(['created_by_user_id' => $user->id]);

        $this->assertTrue($reminder->createdBy()->exists());
        $this->assertEquals($user->id, $reminder->createdBy->id);
    }

    #[Test]
    public function it_is_reached_from_the_value_it_reads(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertEquals($reminder->id, $reminder->fieldValue->reminder->id);
        $this->assertEquals(1, $reminder->field->reminders()->count());
        $this->assertEquals(1, $reminder->remindable->reminders()->count());
    }

    #[Test]
    public function it_separates_the_rules_still_firing_from_the_ones_that_have_gone_quiet(): void
    {
        $firing = Reminder::factory()->create();
        $quiet = Reminder::factory()->inactive(ReminderInactiveReasonEnum::FieldRemoved)->create();

        $this->assertEquals([$firing->id], Reminder::query()->active()->pluck('id')->all());
        $this->assertEquals([$quiet->id], Reminder::query()->inactive()->pluck('id')->all());
        $this->assertEquals(ReminderInactiveReasonEnum::FieldRemoved, $quiet->inactive_reason);
    }

    #[Test]
    public function it_is_only_ever_removed_softly(): void
    {
        $reminder = Reminder::factory()->create();

        $reminder->delete();

        $this->assertSoftDeleted($reminder);
        $this->assertEquals(0, Reminder::query()->count());
        $this->assertEquals(1, Reminder::query()->withTrashed()->count());
    }

    #[Test]
    public function it_carries_a_public_identifier_that_is_never_its_key(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertEquals('uuid', $reminder->getRouteKeyName());
        $this->assertNotEquals((string) $reminder->id, $reminder->getRouteKey());
    }

    #[Test]
    public function it_holds_a_cadence_and_the_moments_it_fires_at(): void
    {
        $reminder = Reminder::factory()->everyFewYears(3)->create();

        $this->assertEquals(ReminderCadenceEnum::Yearly, $reminder->cadence);
        $this->assertEquals(3, $reminder->frequency_interval);
        $this->assertCount(2, $reminder->moments);
        $this->assertEquals('before', $reminder->moments[0]['direction']);
        $this->assertEquals(7, $reminder->moments[0]['amount']);
        $this->assertEquals('days', $reminder->moments[0]['unit']);
        $this->assertEquals('on', $reminder->moments[1]['direction']);
        $this->assertNull($reminder->moments[1]['amount']);
    }
}
