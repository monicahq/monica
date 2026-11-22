<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [['id', 'name', 'is_partial', 'is_archived']],
        'meta' => ['page', 'per_page', 'total'],
    ];

    private User $user;

    private Vault $vault;

    private Contact $pam;

    private Contact $jim;

    private Contact $astrid;

    private Contact $roy;

    #[Test]
    public function it_lists_full_and_partial_contacts_without_the_archived_or_deleted_ones(): void
    {
        $this->dunderMifflin();

        $response = $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0', ['id' => $this->pam->uuid, 'name' => 'Pam Beesly', 'is_partial' => false, 'is_archived' => false]);
        $response->assertJsonPath('data.1', ['id' => $this->jim->uuid, 'name' => 'Jim Halpert', 'is_partial' => false, 'is_archived' => false]);
        $response->assertJsonPath('data.2', ['id' => $this->astrid->uuid, 'name' => 'Astrid Levinson', 'is_partial' => true, 'is_archived' => false]);
        $response->assertJsonPath('meta.per_page', 100);
    }

    #[Test]
    public function it_pages_the_list_up_to_five_hundred_entries(): void
    {
        $this->dunderMifflin();

        $secondPage = $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?per_page=2&page=2");

        $secondPage->assertOk();
        $secondPage->assertJsonCount(1, 'data');
        $secondPage->assertJsonPath('data.0.name', 'Astrid Levinson');
        $secondPage->assertJsonPath('meta.total', 3);

        $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?per_page=900")
            ->assertJsonPath('meta.per_page', 500);
    }

    #[Test]
    public function it_keeps_only_the_partial_or_only_the_full_contacts(): void
    {
        $this->dunderMifflin();

        $partial = $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?filter=partial");

        $partial->assertOk();
        $partial->assertJsonCount(1, 'data');
        $partial->assertJsonPath('data.0.name', 'Astrid Levinson');

        $full = $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?filter=full");

        $full->assertOk();
        $full->assertJsonCount(2, 'data');
        $full->assertJsonPath('data.0.name', 'Pam Beesly');
        $full->assertJsonPath('data.1.name', 'Jim Halpert');
    }

    #[Test]
    public function it_adds_the_archived_contacts_when_asked(): void
    {
        $this->dunderMifflin();

        $response = $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?archived=included");

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
        $response->assertJsonPath('data.0', ['id' => $this->roy->uuid, 'name' => 'Roy Anderson', 'is_partial' => false, 'is_archived' => true]);
        $this->assertSame([true, false, false, false], array_column($response->json('data'), 'is_archived'));
    }

    #[Test]
    public function it_refuses_a_filter_or_an_archived_value_it_does_not_know(): void
    {
        $this->dunderMifflin();

        $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?filter=favourites")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['filter']);

        $this->json('GET', "/api/v1/vaults/{$this->vault->uuid}/contact-summaries?archived=only")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['archived']);
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $this->dunderMifflin();
        $stamford = Vault::factory()->create();

        $this->json('GET', "/api/v1/vaults/{$stamford->uuid}/contact-summaries")
            ->assertNotFound();
    }

    private function dunderMifflin(): void
    {
        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->pam = $this->contact(firstName: 'Pam', lastName: 'Beesly');
        $this->jim = $this->contact(firstName: 'Jim', lastName: 'Halpert');
        $this->astrid = Contact::factory()->partial()->create(['vault_id' => $this->vault->id, 'account_id' => $this->user->account_id, 'first_name' => 'Astrid', 'last_name' => 'Levinson']);
        $this->roy = Contact::factory()->archived()->create(['vault_id' => $this->vault->id, 'account_id' => $this->user->account_id, 'first_name' => 'Roy', 'last_name' => 'Anderson']);
        $this->contact(firstName: 'Todd', lastName: 'Packer')->delete();

        Sanctum::actingAs($this->user);
    }

    private function contact(string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create(['vault_id' => $this->vault->id, 'account_id' => $this->user->account_id, 'first_name' => $firstName, 'last_name' => $lastName]);
    }
}
