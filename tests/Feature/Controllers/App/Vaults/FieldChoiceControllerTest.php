<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\Field;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldChoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_with_the_records_the_relation_could_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Kelly', 'last_name' => 'Kapoor']);

        $this->actingAs($user)->get(route('fields.choices.index', ['vault' => $vault, 'field' => $field, 'query' => 'kel']))
            ->assertStatus(200)
            ->assertSee('id="record-choices"', escape: false)
            ->assertSee('Kelly Kapoor');
    }

    #[Test]
    public function it_answers_not_found_for_a_field_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $theirs = Field::factory()->relation()->create();

        $this->actingAs($user)->get(route('fields.choices.index', ['vault' => $vault, 'field' => $theirs]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_a_field_that_points_at_nothing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $text = Field::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('fields.choices.index', ['vault' => $vault, 'field' => $text]))
            ->assertNotFound();
    }
}
