<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['name', 'contact', 'group', 'next_date'],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_every_reminder_of_the_vault_whatever_its_date(): void
    {
        [$user, $vault] = $this->dunderMifflin();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Pam’s birthday');
        $response->assertJsonPath('data.0.attributes.contact.name', 'Pam');
        $response->assertJsonPath('data.1.attributes.name', 'Book the conference room');
    }

    #[Test]
    public function it_lists_only_the_reminders_about_one_kind_of_record(): void
    {
        [$user, $vault] = $this->dunderMifflin();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders", ['about' => 'group']);

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.attributes.name', 'Book the conference room');
    }

    #[Test]
    public function it_leaves_out_group_reminders_while_groups_are_switched_off(): void
    {
        [$user, $vault] = $this->dunderMifflin();
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders");

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.attributes.name', 'Pam’s birthday');
    }

    #[Test]
    public function it_leaves_out_the_reminders_of_another_vault(): void
    {
        [$user, $vault] = $this->dunderMifflin();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id, 'first_name' => 'Toby']);
        Reminder::factory()->manual($toby, now()->addMonth()->toDateString())->create(['name' => 'Toby leaves for Costa Rica']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertDontSee('Toby leaves for Costa Rica');
    }

    #[Test]
    public function it_refuses_an_unknown_kind_of_record(): void
    {
        [$user, $vault] = $this->dunderMifflin();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders", ['about' => 'branch'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['about']);
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminders")->assertNotFound();
    }

    /** @return array{0: User, 1: Vault} */
    private function dunderMifflin(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => null]);
        $party = Group::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'name' => 'Party Planning Committee']);

        Reminder::factory()->manual($pam, now()->addMonths(3)->toDateString())->create(['name' => 'Pam’s birthday']);
        Reminder::factory()->manual($party, now()->addMonths(2)->toDateString())->create(['name' => 'Book the conference room']);

        return [$user, $vault];
    }
}
