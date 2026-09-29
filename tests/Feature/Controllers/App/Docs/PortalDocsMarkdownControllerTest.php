<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalDocsMarkdownControllerTest extends TestCase
{
    #[Test]
    public function it_serves_every_kind_of_page_as_markdown_to_anybody(): void
    {
        foreach ([
            '/docs/en.md' => '# Welcome to the Monica guide',
            '/docs/en/start-here.md' => '# Start here',
            '/docs/en/start-here/create-your-account.md' => '# Create your account',
        ] as $url => $heading) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
            $this->assertStringStartsWith($heading, $response->getContent());
        }
    }

    #[Test]
    public function it_answers_not_found_for_a_page_that_does_not_exist(): void
    {
        $this->get('/docs/en/start-here/the-warehouse.md')->assertNotFound();
        $this->get('/docs/de.md')->assertNotFound();
    }
}
