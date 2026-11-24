<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Errors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('failing-request/{status}', fn (string $status) => abort((int) $status));
        Route::middleware('web')->get('refused-expense', fn () => abort(403, 'Only Michael Scott can approve an expense this large.'));
        Route::middleware('web')->get('failing-server', fn () => throw new RuntimeException('SELECT * FROM dunder_mifflin_payroll'));
        Route::middleware('web')->post('failing-submission', fn () => throw new RuntimeException('The paper order could not be saved.'));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function failures(): array
    {
        return [
            'not allowed' => [403],
            'not found' => [404],
            'expired' => [419],
            'too many attempts' => [429],
            'server error' => [500],
        ];
    }

    #[Test]
    #[DataProvider('failures')]
    public function it_answers_with_a_page_carrying_the_assets_turbo_compares(int $status): void
    {
        $this->get("failing-request/{$status}")
            ->assertStatus($status)
            ->assertSee('data-turbo-track="reload"', escape: false);
    }

    #[Test]
    public function it_says_why_something_is_not_allowed(): void
    {
        $this->get('refused-expense')
            ->assertForbidden()
            ->assertSee('Only Michael Scott can approve an expense this large.');
    }

    #[Test]
    public function it_leads_back_to_the_page_the_request_came_from(): void
    {
        $preferences = url('settings/account/preferences');

        $this->from($preferences)
            ->get('failing-request/419')
            ->assertStatus(419)
            ->assertSee("href=\"{$preferences}\"", escape: false);
    }

    #[Test]
    public function it_says_the_fault_is_on_the_instance_side_without_revealing_it(): void
    {
        $this->get('failing-server')
            ->assertInternalServerError()
            ->assertSee('We’re sorry, something went wrong on our end.')
            ->assertSee('Please try again in a moment.')
            ->assertDontSee('dunder_mifflin_payroll');
    }

    #[Test]
    public function it_offers_to_ask_for_the_same_address_again(): void
    {
        $failingAddress = url('failing-server?branch=scranton');

        $this->get('failing-server?branch=scranton')
            ->assertInternalServerError()
            ->assertSeeInOrder([
                "href=\"{$failingAddress}\"",
                'Try again',
                'href="'.route('home.index').'"',
                'Return home',
            ], escape: false);
    }

    #[Test]
    public function it_never_offers_to_submit_a_failed_form_again(): void
    {
        $form = url('settings/account/preferences');

        $this->from($form)
            ->post('failing-submission')
            ->assertInternalServerError()
            ->assertSee("href=\"{$form}\"", escape: false)
            ->assertDontSee('href="'.url('failing-submission').'"', escape: false);
    }

    #[Test]
    public function it_answers_without_reaching_the_database(): void
    {
        config(['database.default' => 'unreachable']);
        DB::purge();

        $this->get('failing-server')
            ->assertInternalServerError()
            ->assertSee('We’re sorry, something went wrong on our end.');
    }
}
