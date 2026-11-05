<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeadMetadataTest extends TestCase
{
    use RefreshDatabase;

    private const string TOKEN = 'ScottsTotsScottsTotsScottsTotsScottsTotsScottsTotsScottsTots1234';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::for(null)->activate('marketplace');
    }

    /** @return array<string, array{string, string, int}> */
    public static function pages(): array
    {
        return [
            'a vault screen' => ['vault', 'noindex', 200],
            'the settings' => ['settings', 'noindex', 200],
            'the admin space' => ['admin', 'noindex', 200],
            'the sign in screen' => ['sign in', 'noindex', 200],
            'a private link' => ['private link', 'noindex, nofollow', 200],
            'the password form of a private link' => ['password form', 'noindex, nofollow', 200],
            'a missing page under the documentation' => ['missing documentation', 'noindex', 404],
            'the documentation' => ['documentation', 'all', 200],
            'an API section' => ['api section', 'all', 200],
            'the guide' => ['guide', 'all', 200],
            'the marketplace' => ['marketplace', 'all', 200],
        ];
    }

    /** @return array<string, array{string, string}> */
    public static function layouts(): array
    {
        return [
            'the shell' => ['vault', 'Dunder Mifflin'],
            'the account layout' => ['settings', 'Settings'],
            'the admin layout' => ['admin', 'Overview'],
            'the guest layout' => ['sign in', 'Welcome back'],
            'the marketplace layout' => ['marketplace', 'Marketplace'],
            'a documentation page' => ['documentation', 'Documentation'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function publicPages(): array
    {
        return [
            'the documentation' => ['documentation'],
            'an API section' => ['api section'],
            'the guide' => ['guide'],
            'the marketplace' => ['marketplace'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function it_tells_crawlers_whether_they_may_index_a_page(string $page, string $robots, int $status): void
    {
        $this->get($this->addressOf($page))
            ->assertStatus($status)
            ->assertSee("<meta name=\"robots\" content=\"{$robots}\">", escape: false);
    }

    #[Test]
    #[DataProvider('publicPages')]
    public function it_keeps_every_page_out_of_search_engines_once_indexing_is_switched_off(string $page): void
    {
        config()->set('monica.search_engine_indexing', false);

        $this->get($this->addressOf($page))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', escape: false)
            ->assertDontSee('rel="canonical"', escape: false);
    }

    #[Test]
    #[DataProvider('layouts')]
    public function it_keeps_the_title_and_the_tags_every_layout_opened_its_head_with(string $page, string $title): void
    {
        $this->get($this->addressOf($page))
            ->assertOk()
            ->assertSee("<title>{$title}</title>", escape: false)
            ->assertSee('<meta name="csrf-token"', escape: false)
            ->assertSee('<meta name="turbo-cache-control" content="no-cache" />', escape: false)
            ->assertSee('rel="manifest"', escape: false);
    }

    #[Test]
    public function it_titles_a_screen_that_names_nothing_after_the_application(): void
    {
        $this->blade("@include('partials.meta')")
            ->assertSee('<title>'.config('app.name').'</title>', escape: false);
    }

    #[Test]
    public function it_repeats_the_title_and_description_of_a_public_page_in_its_link_preview(): void
    {
        $description = 'Learn how Monica works, and connect it to your scripts, your assistant and your devices.';

        $this->get(route('docs.index'))
            ->assertSee("<meta name=\"description\" content=\"{$description}\">", escape: false)
            ->assertSee('<meta property="og:title" content="Documentation">', escape: false)
            ->assertSee("<meta property=\"og:description\" content=\"{$description}\">", escape: false)
            ->assertSee('<meta property="og:site_name" content="', escape: false);
    }

    #[Test]
    public function it_names_a_public_page_by_its_address_without_the_query_string(): void
    {
        $this->get(route('docs.api.show', ['section' => 'vaults-index', 'utm_source' => 'dunder-mifflin']))
            ->assertSee('<link rel="canonical" href="http://localhost/docs/api/vaults-index">', escape: false);
    }

    #[Test]
    public function it_names_no_canonical_address_for_a_missing_page_under_the_documentation(): void
    {
        $this->get(route('docs.api.show', ['section' => 'threat-level-midnight']))
            ->assertNotFound()
            ->assertDontSee('rel="canonical"', escape: false);
    }

    #[Test]
    public function it_names_no_canonical_address_for_a_signed_in_screen(): void
    {
        $this->get($this->signedIn(route('vaults.index')))
            ->assertOk()
            ->assertDontSee('rel="canonical"', escape: false);
    }

    private function addressOf(string $page): string
    {
        return match ($page) {
            'vault' => route('vaults.show', Vault::factory()->create(['account_id' => $this->signIn()->account_id, 'name' => 'Dunder Mifflin'])),
            'settings' => $this->signedIn(route('settings.index')),
            'admin' => $this->asAdministrator(route('admin.overview.index')),
            'sign in' => route('auth.login.new'),
            'private link' => $this->sharedLink(passwordHash: null),
            'password form' => $this->sharedLink(passwordHash: Hash::make('Bears. Beets. Battlestar Galactica.')),
            'missing documentation' => route('docs.api.show', ['section' => 'threat-level-midnight']),
            'documentation' => route('docs.index'),
            'api section' => route('docs.api.show', ['section' => 'vaults-index']),
            'guide' => route('docs.portal.index', ['locale' => 'en']),
            'marketplace' => route('marketplace.index'),
        };
    }

    private function signIn(): User
    {
        $michael = User::factory()->create();

        $this->actingAs($michael);

        return $michael;
    }

    private function signedIn(string $address): string
    {
        $this->signIn();

        return $address;
    }

    private function asAdministrator(string $address): string
    {
        Administrator::factory()->create(['user_id' => $this->signIn()->id]);

        return $address;
    }

    private function sharedLink(?string $passwordHash): string
    {
        ShareLink::factory()->create([
            'token' => hash('sha256', self::TOKEN),
            'password_hash' => $passwordHash,
        ]);

        return route('share.show', ['token' => self::TOKEN]);
    }
}
