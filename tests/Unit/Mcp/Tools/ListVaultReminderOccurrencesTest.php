<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\DefaultFieldEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListVaultReminderOccurrences;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListVaultReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_dates_of_the_vault(): void
    {
        [$user, $vault, $stanley] = $this->stanleyWithAVault();
        $reminder = Reminder::factory()->manual($stanley, now()->subDays(3)->toDateString())->create(['timezone' => 'UTC']);
        $overdue = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        MonicaServer::actingAs($user)->tool(ListVaultReminderOccurrences::class, [
            'vault' => $vault->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee($overdue->uuid)->assertSee('Stanley Hudson');
    }

    #[Test]
    public function it_lists_the_birthdays(): void
    {
        [$user, $vault, $stanley] = $this->stanleyWithAVault();
        $field = Field::factory()->recurringDate()->create(['key' => DefaultFieldEnum::Birthday->value]);
        FieldValue::factory()->recurringDate(now()->addDay()->subYears(58)->toDateString())->create([
            'valuable_id' => $stanley->id,
            'field_id' => $field->id,
        ]);

        MonicaServer::actingAs($user)->tool(ListVaultReminderOccurrences::class, [
            'vault' => $vault->uuid,
            'kind' => 'birthdays',
        ])->assertOk()->assertHasNoErrors()->assertSee('"type":"birthday"')->assertSee('"age":58');
    }

    #[Test]
    public function it_refuses_a_category_it_does_not_know(): void
    {
        [$user, $vault] = $this->stanleyWithAVault();

        MonicaServer::actingAs($user)->tool(ListVaultReminderOccurrences::class, [
            'vault' => $vault->uuid,
            'category' => 'dismissed',
        ])->assertHasErrors();
    }

    #[Test]
    public function it_does_not_find_the_vault_of_another_account(): void
    {
        [, $vault] = $this->stanleyWithAVault();

        MonicaServer::actingAs(User::factory()->create())->tool(ListVaultReminderOccurrences::class, [
            'vault' => $vault->uuid,
        ])->assertHasErrors(['That vault does not exist.']);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function stanleyWithAVault(): array
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $stanley = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Stanley', 'last_name' => 'Hudson']);

        return [$user, $vault, $stanley];
    }
}
