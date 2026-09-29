<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\CarddavDocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CarddavDocsControllerTest extends TestCase
{
    #[Test]
    public function it_offers_a_public_guide_and_markdown_with_the_instance_connection_details(): void
    {
        config(['app.url' => 'https://contacts.example.com/monica/']);

        $page = $this->get('/docs/carddav')->assertOk();
        $markdown = $this->get('/docs/carddav.md')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        foreach ([$page, $markdown] as $response) {
            $response->assertSee('https://contacts.example.com/monica/dav', false);
            $response->assertSee('Disconnect a device');
            $response->assertSee('Personal script');
            $response->assertSee('CalDAV tasks (VTODO)', false);
        }

        $page->assertSee('role="tablist"', false);
        $page->assertSee(route('docs.carddav.markdown.index'), false);
        $page->assertSee(route('settings.security.index').'#api-keys', false);

        foreach (CarddavDocsViewModel::SECTIONS as $section) {
            $page->assertSee('id="'.$section['id'].'"', false);
        }

        foreach (new CarddavDocsViewModel()->clients() as $client) {
            $page->assertSee('id="client-panel-'.$client['key'].'"', false);
            $markdown->assertSee('### '.$client['label'], false);
        }
    }

    #[Test]
    public function it_is_discoverable_without_remaining_in_the_api_reference(): void
    {
        $this->get('/docs')->assertOk()->assertSee(route('docs.carddav.index'), false);
        $this->get('/llms.txt')->assertOk()->assertSee(route('docs.carddav.markdown.index'), false);
        $page = $this->get('/docs/api/me-show')->assertOk();
        $markdown = $this->get('/docs/api.md')->assertOk();

        $page->assertDontSee('/docs/api/devices-', false);

        $markdown->assertDontSee('What a device needs');
        $markdown->assertSee('# Get your profile', false);
    }
}
