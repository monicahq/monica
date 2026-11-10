<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_form(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        $this->actingAs($user)->get(route('groups.reminders.new', ['vault' => $vault, 'group' => $group]))
            ->assertOk()
            ->assertSee('Add a reminder')
            ->assertSee('action="'.route('groups.reminders.create', ['vault' => $vault, 'group' => $group]).'"', false);
    }

    #[Test]
    public function it_adds_a_reminder_about_the_group(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        $this->actingAs($user)->post(route('groups.reminders.create', ['vault' => $vault, 'group' => $group]), [
            'name' => 'Plan the Christmas party',
            'starts_on' => '2027-05-20',
            'cadence' => 'yearly',
            'time' => '09:00',
            'timezone' => 'UTC',
        ])->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertDatabaseHas('reminders', ['remindable_type' => Group::class, 'remindable_id' => $group->id, 'name' => 'Plan the Christmas party']);
    }

    #[Test]
    public function it_refuses_a_reminder_without_a_name(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();

        $this->actingAs($user)->post(route('groups.reminders.create', ['vault' => $vault, 'group' => $group]), [
            'name' => '',
            'starts_on' => '2027-05-20',
            'cadence' => 'once',
            'time' => '09:00',
            'timezone' => 'UTC',
        ])->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_deletes_a_reminder_about_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $reminder = Reminder::factory()->manual($group, '2027-05-20')->create();

        $this->actingAs($user)->delete(route('groups.reminders.destroy', ['vault' => $vault, 'group' => $group, 'reminder' => $reminder]))
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]));

        $this->assertSoftDeleted($reminder);
    }

    #[Test]
    public function it_does_not_find_a_reminder_about_another_group(): void
    {
        [$user, $vault, $group] = $this->partyPlanningCommittee();
        $reminder = Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), '2027-05-20')->create();

        $this->actingAs($user)->get(route('groups.reminders.edit', ['vault' => $vault, 'group' => $group, 'reminder' => $reminder]))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function partyPlanningCommittee(): array
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee'])];
    }
}
