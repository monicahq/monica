<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactLifeEventFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_the_custom_fields_of_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $harvest = $this->lifeEventTypeAsking($user, 'Harvest', ['Crop' => FieldTypeEnum::Text]);

        $this->actingAs($user)
            ->get(route('contacts.lifeEventFields.index', ['vault' => $vault, 'contact' => $dwight, 'lifeEventType' => $harvest]))
            ->assertOk()
            ->assertSee('id="life-event-custom-fields"', escape: false)
            ->assertSee('Crop');
    }

    #[Test]
    public function it_says_when_a_type_asks_for_nothing_more(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $birth = $this->lifeEventTypeAsking($user, 'Birth');

        $this->actingAs($user)
            ->get(route('contacts.lifeEventFields.index', ['vault' => $vault, 'contact' => $dwight, 'lifeEventType' => $birth]))
            ->assertOk()
            ->assertSee('Birth asks for nothing beyond the date and the people.');
    }
}
