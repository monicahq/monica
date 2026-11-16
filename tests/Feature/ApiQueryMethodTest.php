<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiQueryMethodTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_api_route_that_answers_get_also_answers_query(): void
    {
        $missing = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/') && in_array('GET', $route->methods(), true) && ! in_array('QUERY', $route->methods(), true)) {
                $missing[] = $route->uri();
            }
        }

        $this->assertSame([], $missing);
    }

    #[Test]
    public function it_refuses_query_on_a_path_that_only_writes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('QUERY', '/api/v1/logout')->assertMethodNotAllowed();
    }

    #[Test]
    public function it_answers_a_query_with_a_json_body_as_the_get_with_the_same_parameters(): void
    {
        $user = User::factory()->create();
        $vault = $this->dunderMifflinFor($user);
        Sanctum::actingAs($user);

        $asGet = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?page=2&per_page=2");
        $asQuery = $this->json('QUERY', "/api/v1/vaults/{$vault->uuid}/contacts", ['page' => 2, 'per_page' => 2]);

        $asQuery->assertOk();
        $asQuery->assertJsonPath('meta.page', 2);
        $asQuery->assertJsonPath('meta.per_page', 2);
        $this->assertSame($asGet->json(), $asQuery->json());
    }

    #[Test]
    public function it_reads_a_form_encoded_query_body(): void
    {
        $user = User::factory()->create();
        $vault = $this->dunderMifflinFor($user);
        Sanctum::actingAs($user);

        $response = $this->call('QUERY', "/api/v1/vaults/{$vault->uuid}/contacts", ['query' => 'Dwight'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.first_name', 'Dwight');
    }

    #[Test]
    public function it_refuses_a_parameter_sent_both_in_the_address_and_in_the_body(): void
    {
        $user = User::factory()->create();
        $vault = $this->dunderMifflinFor($user);
        Sanctum::actingAs($user);

        $this->json('QUERY', "/api/v1/vaults/{$vault->uuid}/contacts?page=1", ['page' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    #[Test]
    public function it_answers_an_unauthenticated_query_with_unauthorized(): void
    {
        $this->json('QUERY', '/api/v1/vaults?page=1', ['page' => 2])->assertUnauthorized();
    }

    #[Test]
    public function it_announces_the_query_body_formats_on_a_head_request(): void
    {
        $user = User::factory()->create();
        $vault = $this->dunderMifflinFor($user);
        Sanctum::actingAs($user);

        $this->call('HEAD', "/api/v1/vaults/{$vault->uuid}/contacts")
            ->assertOk()
            ->assertHeader('Accept-Query', 'application/json, application/x-www-form-urlencoded');
    }

    #[Test]
    public function it_does_not_announce_query_on_a_path_that_only_writes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/logout')->assertHeaderMissing('Accept-Query');
    }

    private function dunderMifflinFor(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach (['Michael', 'Dwight', 'Jim', 'Pam', 'Angela'] as $firstName) {
            Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => $firstName]);
        }

        return $vault;
    }
}
