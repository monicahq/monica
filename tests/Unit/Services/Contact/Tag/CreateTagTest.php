<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Tag;
use App\Services\Contact\Tag\CreateTag;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateTagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_tag()
    {
        $tag = factory(Tag::class)->create([]);

        $request = [
            'account_id' => $tag->account->id,
            'name' => 'Central Perk',
        ];

        $createTagService = new CreateTag;
        $tag = $createTagService->execute($request);

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

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'tag_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        $createTagService = new CreateTag;
        $tag = $createTagService->execute($request);
    }
}
