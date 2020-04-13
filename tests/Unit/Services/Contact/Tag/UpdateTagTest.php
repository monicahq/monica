<?php

namespace Tests\Unit\Services\Contact\Tag;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Services\Contact\Tag\UpdateTag;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateTagTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_tag()
    {
        $tag = factory(Tag::class)->create([]);

        $request = [
            'account_id' => $tag->account_id,
            'tag_id' => $tag->id,
            'name' => 'Central Perk',
        ];

        $tag = app(UpdateTag::class)->execute($request);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Central Perk',
            'name_slug' => 'central-perk',
        ]);

        $this->assertInstanceOf(
            Tag::class,
            $tag
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'tag_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(UpdateTag::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_tag_does_not_exist()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'tag_id' => 1232322,
            'name' => 'Central Perk',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateTag::class)->execute($request);
    }
}
