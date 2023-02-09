<?php

namespace Tests\Unit\Helpers;

use App\Helpers\WikipediaHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WikipediaHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_about_the_city_or_country(): void
    {
        $city = 'Montreal';

        $array = WikipediaHelper::getInformation($city);

        $this->assertCount(3, $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('thumbnail', $array);
        $this->assertArrayHasKey('url', $array);
    }
}
