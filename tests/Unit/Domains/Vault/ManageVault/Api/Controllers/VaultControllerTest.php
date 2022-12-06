<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Api\Controllers;

use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\ApiTestCase;

class VaultControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_a_list_of_vaults(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['read']);

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'This is a vault',
            'description' => 'this is a description',
        ]);

        $response = $this->get('/api/vaults');

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                0 => [
                    'id' => $vault->id,
                    'name' => 'This is a vault',
                    'description' => 'this is a description',
                    'created_at' => '2018-01-01T00:00:00Z',
                    'updated_at' => '2018-01-01T00:00:00Z',
                    'links' => [
                        'self' => env('APP_URL').'/api/vaults/'.$vault->id,
                    ],
                ],
            ],
            'links' => $this->links('/api/vaults'),
            'meta' => $this->meta('/api/vaults'),
        ]);
    }

    /** @test */
    public function it_stores_a_vault(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->createUser(['write']);

        $form = [
            'name' => 'this is a name',
            'description' => 'this is a description',
        ];
        $response = $this->post('/api/vaults', $form);
        $response->assertStatus(201);

        $vault = Vault::latest()->first();
        $response->assertExactJson([
            'data' => [
                'id' => $vault->id,
                'name' => 'this is a name',
                'description' => 'this is a description',
                'created_at' => '2018-01-01T00:00:00Z',
                'updated_at' => '2018-01-01T00:00:00Z',
                'links' => [
                    'self' => env('APP_URL').'/api/vaults/'.$vault->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_a_vault(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['read']);

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'This is a vault',
            'description' => 'this is a description',
        ]);

        $response = $this->get('/api/vaults/'.$vault->id);

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                'id' => $vault->id,
                'name' => 'This is a vault',
                'description' => 'this is a description',
                'created_at' => '2018-01-01T00:00:00Z',
                'updated_at' => '2018-01-01T00:00:00Z',
                'links' => [
                    'self' => env('APP_URL').'/api/vaults/'.$vault->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_an_exception_getting_unexisting_vault(): void
    {
        $this->createUser(['read']);

        $vault = Vault::factory()->create();

        $response = $this->get('/api/vaults/'.$vault->id);

        $response->assertResourceNotFound();
    }

    /** @test */
    public function it_updates_a_vault(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['write']);

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'This is a vault',
            'description' => 'this is a description',
        ]);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $form = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'name' => 'this is a name',
            'description' => 'this is a cool description',
        ];

        Carbon::setTestNow(Carbon::create(2020, 1, 1));

        $response = $this->put('/api/vaults/'.$vault->id, $form);
        $response->assertStatus(200);

        $response->assertExactJson([
            'data' => [
                'id' => $vault->id,
                'name' => 'this is a name',
                'description' => 'this is a cool description',
                'created_at' => '2018-01-01T00:00:00Z',
                'updated_at' => '2020-01-01T00:00:00Z',
                'links' => [
                    'self' => env('APP_URL').'/api/vaults/'.$vault->id,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_an_exception_updating_unexisting_vault(): void
    {
        $this->createUser(['write']);

        $vault = Vault::factory()->create();

        $response = $this->put('/api/vaults/'.$vault->id, [
            'name' => 'this is a name',
            'description' => 'this is a cool description',
        ]);

        $response->assertResourceNotFound();
    }

    /** @test */
    public function it_destroys_a_vault(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['write']);

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);

        $form = [
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
        ];

        $response = $this->delete('/api/vaults/'.$vault->id, $form);
        $response->assertStatus(200);

        $response->assertExactJson([
            'deleted' => true,
            'id' => $vault->id,
        ]);
    }

    /** @test */
    public function it_gets_an_exception_deleting_unexisting_vault(): void
    {
        $this->createUser(['write']);

        $vault = Vault::factory()->create();

        $response = $this->delete('/api/vaults/'.$vault->id);

        $response->assertResourceNotFound();
    }
}
