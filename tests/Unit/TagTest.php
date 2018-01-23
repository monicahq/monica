<?php

namespace Tests\Unit;

use App\Tag;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TagTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_the_slug()
    {
        $tag = factory(Tag::class)->create(['name' => 'this is great']);

        $tag->updateSlug();

        $this->assertEquals(
            'this-is-great',
            $tag->name_slug
        );
    }
}
