<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_serves_the_sitemap_to_anybody(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $sitemap = simplexml_load_string((string) $response->getContent());

        $this->assertNotFalse($sitemap);
        $this->assertSame(route('docs.index'), (string) $sitemap->url[0]->loc);
    }

    #[Test]
    public function it_has_no_sitemap_when_search_engines_are_kept_out(): void
    {
        config()->set('monica.search_engine_indexing', false);

        $this->get('/sitemap.xml')->assertNotFound();
    }
}
