<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_contact_on_another_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$vault, $contact] = $this->contactOn($user, $this->template($user, 'Friend'));
        $coworker = $this->template($user, 'Coworker');

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template", [
            'template' => $coworker->uuid,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.structure_version_id', $coworker->structure->published_version_id);
        $response->assertJsonPath('data.attributes.template_name', 'Coworker');
    }

    #[Test]
    public function it_refuses_a_template_that_is_turned_off_with_the_reason(): void
    {
        $user = User::factory()->create();
        [$vault, $contact] = $this->contactOn($user, $this->template($user, 'Friend'));
        $retired = $this->template($user, 'Coworker');
        $retired->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template", [
            'template' => $retired->uuid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['template' => 'This template is turned off, so no contact can be put on it.']);
    }

    #[Test]
    public function it_requires_a_template(): void
    {
        $user = User::factory()->create();
        [$vault, $contact] = $this->contactOn($user, $this->template($user, 'Friend'));

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('template');
    }

    #[Test]
    public function it_answers_not_found_for_a_template_or_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [$vault, $contact] = $this->contactOn($user, $this->template($user, 'Friend'));
        [$strangerVault, $strangerContact] = $this->contactOn($stranger, $this->template($stranger, 'Friend'));

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/template", [
            'template' => $this->template($stranger, 'Coworker')->uuid,
        ])->assertNotFound();

        $this->json('PUT', "/api/v1/vaults/{$strangerVault->uuid}/contacts/{$strangerContact->uuid}/template", [
            'template' => $this->template($user, 'Coworker')->uuid,
        ])->assertNotFound();
    }

    private function template(User $user, string $name): Template
    {
        return Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => $name])->refresh();
    }

    /** @return array{Vault, Contact} */
    private function contactOn(User $user, Template $template): array
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$vault, Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $template->structure->published_version_id,
        ])];
    }
}
