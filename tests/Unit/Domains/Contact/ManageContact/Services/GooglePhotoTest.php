<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Domains\Contact\ManagePhotos\Services\GooglePhoto;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GooglePhotoTest extends TestCase
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
        Http::fake([
            GooglePhoto::GOOGLE_SEARCH_URL.'/*' => Http::response('', 200),
        ]);

        $service = new GooglePhoto();

        $imageUrls = $service->execute('Laravel');
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
