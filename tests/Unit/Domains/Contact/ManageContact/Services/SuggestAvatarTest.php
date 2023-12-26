<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuggestAvatarTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group google-search
     *
     * @throws Exception
     */
    public function it_searches_google(): void
    {

    }

    /**
     * @test
     *
     * @group google-fetch
     */
    public function it_fetches_image_urls(): void
    {

    }

    /**
     * @test
     *
     * @group google-parse
     */
    public function it_parses_html(): void
    {

    }
}
