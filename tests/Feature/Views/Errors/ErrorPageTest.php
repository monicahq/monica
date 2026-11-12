<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Errors;

use Illuminate\Support\Facades\Route;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('failing-request/{status}', fn (string $status) => abort((int) $status));
        Route::middleware('web')->get('refused-expense', fn () => abort(403, 'Only Michael Scott can approve an expense this large.'));
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
}
