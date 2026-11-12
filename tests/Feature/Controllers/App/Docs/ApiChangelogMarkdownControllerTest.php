<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\ApiChangelogViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiChangelogMarkdownControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_changelog_over_as_markdown(): void
    {
        $response = $this->get(route('docs.api.changelog.markdown.index'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('# API changelog');
    }

    #[Test]
    public function it_hands_one_entry_over_as_markdown(): void
    {
        $this->app->instance(ApiChangelogViewModel::class, new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/Valid')));

        $response = $this->get(route('docs.api.changelog.markdown.show', ['entry' => 'vault-colour']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('# Adds colour to the vault object');
    }

    #[Test]
    public function it_finds_nothing_for_an_entry_that_does_not_exist(): void
    {
        $this->get('/docs/api/changelog/nothing-of-the-sort.md')->assertNotFound();
    }
}
