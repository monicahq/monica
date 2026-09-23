<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\ApiDocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenApiDocumentControllerTest extends TestCase
{
    #[Test]
    public function it_hands_the_document_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('docs.api.openapi'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function it_answers_with_a_document_a_tool_can_read(): void
    {
        $response = $this->get(route('docs.api.openapi'));

        $response->assertJsonPath('openapi', '3.2.0');
        $response->assertJsonPath('servers.0.url', ApiDocsViewModel::baseUrl());
        $response->assertJsonStructure(['openapi', 'info', 'servers', 'tags', 'paths', 'components']);
    }

    #[Test]
    public function it_serves_the_document_at_an_address_naming_the_api_version(): void
    {
        $this->get('/docs/api/v1/openapi')->assertOk();
    }
}
