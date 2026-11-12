<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\PageSize;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cookie;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageSizeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_each_choice_as_given(): void
    {
        foreach (PageSize::CHOICES as $choice) {
            $this->assertSame($choice, PageSize::from(Request::create('/people', 'GET', ['size' => (string) $choice]), default: 20));
        }

        $this->assertSame('all', PageSize::from(Request::create('/people', 'GET', ['size' => 'all']), default: 20));
    }

    #[Test]
    public function it_answers_the_default_for_anything_else(): void
    {
        foreach (['7', '0', '-1', 'abc', '', '10.0'] as $size) {
            $this->assertSame(20, PageSize::from(Request::create('/people', 'GET', ['size' => $size]), default: 20));
        }

        $this->assertSame(50, PageSize::from(Request::create('/people'), default: 50));
        $this->assertSame(50, PageSize::from(Request::create('/people', 'GET', ['size' => ['10']]), default: 50));
    }

    #[Test]
    public function it_remembers_the_size_the_address_names_for_that_screen(): void
    {
        $request = $this->requestOn('contacts.index', ['size' => '50'], ['admin.accounts.index' => 10]);

        $this->assertSame(50, PageSize::from($request, default: 20));

        $queued = Cookie::queued(PageSize::COOKIE);

        $this->assertNotNull($queued);
        $this->assertSame(['admin.accounts.index' => 10, 'contacts.index' => 50], json_decode((string) $queued->getValue(), true));
        $this->assertEqualsWithDelta(time() + 60 * 60 * 24 * 365, $queued->getExpiresTime(), 5);
    }

    #[Test]
    public function it_reads_the_remembered_size_when_the_address_names_none(): void
    {
        $this->assertSame(50, PageSize::from($this->requestOn('contacts.index', [], ['contacts.index' => 50]), default: 20));
        $this->assertSame('all', PageSize::from($this->requestOn('contacts.index', [], ['contacts.index' => 'all']), default: 20));
        $this->assertSame(20, PageSize::from($this->requestOn('entities.index', [], ['contacts.index' => 50]), default: 20));
        $this->assertNull(Cookie::queued(PageSize::COOKIE));
    }

    #[Test]
    public function it_lets_the_address_win_over_what_is_remembered(): void
    {
        $this->assertSame(10, PageSize::from($this->requestOn('contacts.index', ['size' => '10'], ['contacts.index' => 50]), default: 20));
    }

    #[Test]
    public function it_ignores_a_remembered_size_that_is_not_a_choice(): void
    {
        $this->assertSame(20, PageSize::from($this->requestOn('contacts.index', [], ['contacts.index' => 7]), default: 20));
        $this->assertSame(20, PageSize::from($this->requestOn('contacts.index', [], ['contacts.index' => 'everything']), default: 20));
        $this->assertSame(20, PageSize::from(Request::create('/people', 'GET', [], [PageSize::COOKIE => 'not json']), default: 20));
        $this->assertSame(20, PageSize::from(Request::create('/people', 'GET', [], [PageSize::COOKIE => '"a string"']), default: 20));
    }

    #[Test]
    public function it_remembers_nothing_for_a_request_that_names_no_screen(): void
    {
        $this->assertSame(50, PageSize::from(Request::create('/people', 'GET', ['size' => '50']), default: 20));
        $this->assertNull(Cookie::queued(PageSize::COOKIE));
    }

    #[Test]
    public function it_holds_everything_on_one_page_for_all(): void
    {
        Account::factory()->count(30)->create();

        $page = PageSize::paginate(Account::query()->orderBy('id'), 'all');

        $this->assertCount(30, $page);
        $this->assertFalse($page->hasPages());
    }

    #[Test]
    public function it_answers_an_empty_page_for_all_when_there_is_nothing(): void
    {
        $page = PageSize::paginate(Account::query(), 'all');

        $this->assertCount(0, $page);
        $this->assertSame(0, $page->total());
    }

    #[Test]
    public function it_lands_on_the_last_page_when_asked_for_one_past_it(): void
    {
        Account::factory()->count(30)->create();
        $this->app['request']->merge(['page' => 9]);

        $page = PageSize::paginate(Account::query()->orderBy('id'), 10);

        $this->assertSame(3, $page->currentPage());
        $this->assertCount(10, $page);
    }

    /**
     * @param  array<string, string>  $query
     * @param  array<string, int|string>  $remembered
     */
    private function requestOn(string $screen, array $query, array $remembered): Request
    {
        $request = Request::create('/people', 'GET', $query, [PageSize::COOKIE => json_encode($remembered)]);
        $request->setRouteResolver(fn (): Route => new Route('GET', 'people', [])->name($screen));

        return $request;
    }
}
