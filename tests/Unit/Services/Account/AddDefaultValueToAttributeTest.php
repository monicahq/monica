<?php

namespace Tests\Unit\Services\Account;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Attribute;
use App\Models\Information;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Services\Account\AddDefaultValueToAttribute;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddDefaultValueToAttributeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_default_value_to_attribute(): void
    {
        $ross = $this->createAdministrator();
        $information = Information::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $attribute = Attribute::factory()->create([
            'information_id' => $information->id,
        ]);
        $this->executeService($ross, $ross->account, $attribute);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Male',
        ];

        $this->expectException(ValidationException::class);
        (new AddDefaultValueToAttribute)->execute($request);
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
        $attribute = Attribute::factory()->create([
            'information_id' => $information->id,
        ]);
        $this->executeService($ross, $account, $attribute);
    }

    /** @test */
    public function it_fails_if_attribute_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $attribute = Attribute::factory()->create();
        $this->executeService($ross, $ross->account, $attribute);
    }

    private function executeService(User $author, Account $account, Attribute $attribute): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'attribute_id' => $attribute->id,
            'value' => 'Male',
            'type' => 'text',
        ];

        $attribute = (new AddDefaultValueToAttribute)->execute($request);

        $this->assertDatabaseHas('attribute_default_values', [
            'attribute_id' => $attribute->id,
            'value' => 'Male',
        ]);

        $this->assertInstanceOf(
            Attribute::class,
            $attribute
        );
    }
}
