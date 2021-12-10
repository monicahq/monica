<?php

namespace Tests\Unit\Services\Account\Template;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Attribute;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateAttributeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_attribute(): void
    {
        $ross = $this->createAdministrator();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $information);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Assistant to the regional manager',
        ];

        $this->expectException(ValidationException::class);
        (new \App\Services\Account\Template\CreateAttribute)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $information);
    }

    /** @test */
    public function it_fails_if_information_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $information = Information::factory()->create([]);
        $this->executeService($ross, $account, $information);
    }

    private function executeService(User $author, Account $account, Information $information): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'information_id' => $information->id,
            'name' => 'Assistant to the regional manager',
            'type' => 'text',
        ];

        $attribute = (new \App\Services\Account\Template\CreateAttribute)->execute($request);

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'information_id' => $information->id,
            'name' => 'Assistant to the regional manager',
            'type' => 'text',
            'unit' => null,
            'unit_placement_after' => false,
        ]);

        $this->assertInstanceOf(
            Attribute::class,
            $attribute
        );
    }
}
